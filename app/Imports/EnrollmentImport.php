<?php
namespace App\Imports;

use App\Models\Enrollment;
use App\Models\UploadBatch;
use App\Models\User;
use App\Models\Semester;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;

class EnrollmentImport implements ToCollection, WithStartRow, WithChunkReading, WithEvents
{
    public UploadBatch $batch;
    private string $semester;
    private string $academicYear;
    private int $imported = 0;  // brand-new enrollment records inserted
    private int $updated  = 0;  // existing students moved to a new semester
    private int $skipped  = 0;  // true duplicates / validation failures
    private array $errors  = [];
    private array $courses = [];

    // student_code => true  (all codes already present for THIS semester+year)
    private array $existingEnrollments = [];

    // Track whether we have already parsed the Period row from the first chunk.
    // Row 5 (0-index 4) only appears in the first chunk; subsequent chunks only
    // contain student rows, so we must not attempt to re-parse them.
    private bool $periodParsed = false;

    public function __construct(UploadBatch $batch, string $semester, string $academicYear)
    {
        $this->batch        = $batch;
        $this->semester     = $semester;
        $this->academicYear = $academicYear;

        // Pre-load existing enrollments for this semester/year to detect true duplicates.
        $this->existingEnrollments = Enrollment::where('semester', $semester)
            ->where('academic_year', $academicYear)
            ->pluck('student_code')
            ->flip()
            ->toArray();
    }

    public function startRow(): int  { return 1; }
    public function chunkSize(): int { return 200; }

    // ── Event registration ────────────────────────────────────────────────────

    public function registerEvents(): array
    {
        return [
            // AfterImport fires once when ALL chunks have been processed.
            // Syncing the semester here guarantees it runs exactly once,
            // regardless of how many chunks the file produces.
            AfterImport::class => function (AfterImport $event) {
                $this->syncSemesterRecord();
            },
        ];
    }

    // ── Semester helpers ──────────────────────────────────────────────────────

    /**
     * Normalise any semester name variant to the canonical stored form.
     *   "First Semester"  / "1st Semester"  → "1st Semester"
     *   "Second Semester" / "2nd Semester"  → "2nd Semester"
     *   "Summer"                            → "Summer"
     */
    private function normaliseSemesterName(string $raw): string
    {
        $lower = strtolower(trim($raw));

        if (str_starts_with($lower, 'first')  || str_starts_with($lower, '1st')) return '1st Semester';
        if (str_starts_with($lower, 'second') || str_starts_with($lower, '2nd')) return '2nd Semester';
        if (str_starts_with($lower, 'summer'))                                    return 'Summer';

        return trim($raw);
    }

    /**
     * Parse Row 5 of the Excel into [semester, academicYear].
     *
     * Handles all known formats:
     *   col A = "Period"  col B = "Second Semester 2025-2026"  ← label + value in separate cells
     *   col A = "First Semester 2025-2026"                     ← single-cell
     *   col A = "Period: 1st Semester 2025-2026"               ← colon-label in same cell
     *   col A = "Period"  col B = "Summer 2025-2026"
     *
     * Strategy:
     *   Pass 1 – try each non-empty cell on its own after stripping any "Label:" prefix.
     *            This catches "Second Semester 2025-2026" sitting in its own cell (col B).
     *   Pass 2 – try concatenating adjacent cell pairs.
     *            Fallback for unusual layouts where label and value are merged at read time.
     */
    private function parsePeriodRow($periodRow): array
    {
        // Collect all non-empty string values from the row
        $cells = [];
        foreach ($periodRow as $cell) {
            $val = trim((string) ($cell ?? ''));
            if ($val !== '') {
                $cells[] = $val;
            }
        }

        // Pattern: "First Semester 2025-2026", "2nd Semester 2024-2025", "Summer 2025-2026", etc.
        $semesterPattern = '/^((?:First|Second|Summer|1st|2nd)(?:\s+Semester)?)\s+(\d{4}-\d{4})$/i';

        // Pass 1: try each cell individually after stripping an optional "Label:" prefix.
        foreach ($cells as $cell) {
            $cleaned = trim(preg_replace('/^[^:]+:\s*/i', '', $cell));
            if (preg_match($semesterPattern, $cleaned, $m)) {
                return [$this->normaliseSemesterName($m[1]), $m[2]];
            }
        }

        // Pass 2: try concatenating adjacent cell pairs.
        // Example: cells = ["Period", "Second Semester 2025-2026"]
        //          combined = "Period Second Semester 2025-2026"
        //          after stripping non-colon label prefix this still won't match,
        //          BUT the adjacent-pair loop also tries col1 alone, so this is
        //          really just a belt-and-suspenders layer for merged-cell exports.
        for ($i = 0; $i < count($cells) - 1; $i++) {
            // Try the right-hand cell of the pair directly (covers "Period" | value splits)
            $right = trim($cells[$i + 1]);
            if (preg_match($semesterPattern, $right, $m)) {
                return [$this->normaliseSemesterName($m[1]), $m[2]];
            }
            // Also try the full concatenation with colon-strip
            $combined = $cells[$i] . ' ' . $cells[$i + 1];
            $cleaned  = trim(preg_replace('/^[^:]+:\s*/i', '', $combined));
            if (preg_match($semesterPattern, $cleaned, $m)) {
                return [$this->normaliseSemesterName($m[1]), $m[2]];
            }
        }

        // Fallback: keep whatever the constructor received
        return [$this->semester, $this->academicYear];
    }

    /**
     * Create or activate the semester record in the database.
     * Called once via AfterImport event after all chunks are processed.
     */
    private function syncSemesterRecord(): void
    {
        try {
            // Deactivate all existing semesters
            Semester::where('is_active', true)->update(['is_active' => false]);

            $semester = Semester::where('name', $this->semester)
                ->where('academic_year', $this->academicYear)
                ->first();

            if (!$semester) {
                $semester = new Semester();
                $semester->name          = $this->semester;
                $semester->academic_year = $this->academicYear;

                // Set sensible date ranges based on the academic year
                [$startYear] = explode('-', $this->academicYear);
                $startYear   = (int) $startYear;

                if ($this->semester === '1st Semester') {
                    $semester->start_date = date('Y-m-d', mktime(0, 0, 0, 8,  1, $startYear));
                    $semester->end_date   = date('Y-m-d', mktime(0, 0, 0, 12, 15, $startYear));
                } elseif ($this->semester === '2nd Semester') {
                    $semester->start_date = date('Y-m-d', mktime(0, 0, 0, 1, 1,  $startYear + 1));
                    $semester->end_date   = date('Y-m-d', mktime(0, 0, 0, 5, 31, $startYear + 1));
                } else { // Summer
                    $semester->start_date = date('Y-m-d', mktime(0, 0, 0, 6, 1,  $startYear + 1));
                    $semester->end_date   = date('Y-m-d', mktime(0, 0, 0, 7, 31, $startYear + 1));
                }
            }

            $semester->is_active = true;
            $semester->save();

            \Log::info('Semester synced', [
                'semester'      => $this->semester,
                'academic_year' => $this->academicYear,
                'id'            => $semester->id,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to sync semester: ' . $e->getMessage(), [
                'semester'      => $this->semester,
                'academic_year' => $this->academicYear,
            ]);
        }
    }

    // ── Main collection handler ───────────────────────────────────────────────

    public function collection(Collection $rows): void
    {
        // ── Step 1: Parse Period from Row 5 — first chunk only ────────────────
        // Row 5 (0-index 4) is only present in the first chunk. On subsequent
        // chunks index 4 would point to a student row, which must not be parsed
        // as a semester descriptor.
        if (!$this->periodParsed) {
            $periodRow = $rows->get(4);
            if ($periodRow) {
                $previousSemester     = $this->semester;
                $previousAcademicYear = $this->academicYear;

                [$this->semester, $this->academicYear] = $this->parsePeriodRow($periodRow);

                // If the parsed semester differs from the form value, reload the
                // duplicate-detection cache so it matches the actual target semester.
                if ($this->semester !== $previousSemester || $this->academicYear !== $previousAcademicYear) {
                    $this->existingEnrollments = Enrollment::where('semester', $this->semester)
                        ->where('academic_year', $this->academicYear)
                        ->pluck('student_code')
                        ->flip()
                        ->toArray();
                }
            }
            $this->periodParsed = true;
        }

        // ── Step 2: Student rows start at row 9 (0-index = 8) ────────────────
        // On the first chunk, rows 0–7 are header rows; slice from index 8.
        // On subsequent chunks, ALL rows are student data — slice(0) is a no-op.
        $studentRows = $this->periodParsed && $rows->count() > 8
            ? $rows->slice(8)   // first chunk: skip header rows
            : $rows;            // subsequent chunks: all rows are students
        // Note: after the first collection() call, periodParsed is true, so
        // subsequent chunks go to the else branch and process all their rows.
        // We detect "first chunk" by checking whether row 0 looks like a header.
        $firstCell = trim((string) ($rows->first()[0] ?? ''));
        $isHeaderChunk = in_array(strtolower($firstCell), [
            'aldersgate college', 'enrollment list', 'no', 'period', 'course',
        ]) || !is_numeric($firstCell);
        $studentRows = $isHeaderChunk ? $rows->slice(8) : $rows;

        // ── Step 3: Chunk processing ──────────────────────────────────────────
        foreach ($studentRows->chunk(100) as $chunk) {

            $codesInChunk = $chunk
                ->filter(fn($row) => !empty(trim((string) ($row[1] ?? ''))))
                ->map(fn($row)    => $this->cleanStudentCode(trim((string) $row[1])))
                ->filter()
                ->values()
                ->toArray();

            if (empty($codesInChunk)) {
                continue;
            }

            // Which codes already have a users row?
            $existingUserCodes = User::whereIn('student_id', $codesInChunk)
                ->pluck('student_id')
                ->flip()
                ->toArray();

            // Which codes have an enrollment row in ANY semester?
            $existingAnyEnrollment = Enrollment::whereIn('student_code', $codesInChunk)
                ->get(['student_code', 'semester', 'academic_year', 'id'])
                ->keyBy('student_code');

            $toInsertEnrollments = [];
            $toInsertUsers       = [];

            foreach ($chunk as $row) {
                // Column map (0-indexed) — works for 10-column AND 17-column files;
                // columns 10+ (Email, Contact No, Birth Date, etc.) are ignored.
                $rawCode     = trim((string) ($row[1] ?? ''));
                $studentCode = $this->cleanStudentCode($rawCode);
                if (empty($studentCode)) continue;

                $lastName   = $this->cleanName(trim((string) ($row[2] ?? '')));
                $firstName  = $this->cleanName(trim((string) ($row[3] ?? '')));
                $middleName = trim((string) ($row[4] ?? ''));
                $sex        = strtoupper(trim((string) ($row[5] ?? 'M')));
                $course     = trim((string) ($row[6] ?? ''));
                $yearLevel  = (int) ($row[7] ?? 1);
                $units      = (int) ($row[8] ?? 0);
                $section    = mb_substr(trim((string) ($row[9] ?? '')), 0, 100);

                // ── Validation ────────────────────────────────────────────────
                if (empty($lastName) || empty($firstName)) {
                    $this->skipped++;
                    $this->errors[] = "Skipped — missing name fields. Code: '{$studentCode}'";
                    continue;
                }

                if (strlen($studentCode) > 50) {
                    $this->skipped++;
                    $this->errors[] = "Skipped — student code too long: '{$studentCode}'";
                    continue;
                }

                if (!in_array($sex, ['M', 'F'])) {
                    $sex = 'M';
                }

                if (!empty($course) && !in_array($course, $this->courses)) {
                    $this->courses[] = $course;
                }

                $sectionShort = mb_substr($section, 0, 20); // users.section is varchar(20)

                // ── Route 1: duplicate within this same semester ──────────────
                if (isset($this->existingEnrollments[$studentCode])) {
                    $this->skipped++;

                    // Still sync the user row — section may have changed mid-semester
                    if (isset($existingUserCodes[$studentCode])) {
                        User::where('student_id', $studentCode)->update([
                            'semester'      => $this->semester,
                            'academic_year' => $this->academicYear,
                            'department'    => $course,
                            'year_level'    => $yearLevel,
                            'section'       => $sectionShort,
                            'updated_at'    => now(),
                        ]);
                    }
                    continue;
                }

                // ── Route 2: student exists from a previous semester ──────────
                if ($existingAnyEnrollment->has($studentCode)) {
                    try {
                        Enrollment::where('student_code', $studentCode)->update([
                            'semester'        => $this->semester,
                            'academic_year'   => $this->academicYear,
                            'course'          => $course,
                            'year_level'      => $yearLevel,
                            'section'         => $section,
                            'units'           => $units,
                            'upload_batch_id' => $this->batch->id,
                            'is_active'       => true,
                            'updated_at'      => now(),
                        ]);

                        User::where('student_id', $studentCode)->update([
                            'semester'      => $this->semester,
                            'academic_year' => $this->academicYear,
                            'department'    => $course,
                            'year_level'    => $yearLevel,
                            'section'       => $sectionShort,
                            'updated_at'    => now(),
                        ]);

                        $this->existingEnrollments[$studentCode] = true;
                        $this->updated++;

                    } catch (\Exception $e) {
                        $this->skipped++;
                        $this->errors[] = "Update error for {$studentCode}: " . $e->getMessage();
                    }

                // ── Route 3: brand-new student ────────────────────────────────
                } else {
                    try {
                        $toInsertEnrollments[] = [
                            'student_code'    => $studentCode,
                            'semester'        => $this->semester,
                            'academic_year'   => $this->academicYear,
                            'last_name'       => $lastName,
                            'first_name'      => $firstName,
                            'middle_name'     => $middleName ?: null,
                            'sex'             => $sex,
                            'course'          => $course,
                            'year_level'      => $yearLevel,
                            'section'         => $section,
                            'units'           => $units,
                            'is_active'       => true,
                            'upload_batch_id' => $this->batch->id,
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ];

                        if (!isset($existingUserCodes[$studentCode])) {
                            $toInsertUsers[] = [
                                'student_id'    => $studentCode,
                                'email'         => strtolower(str_replace([' ', '/'], '_', $studentCode))
                                                   . '@student.votecast.edu',
                                'password'      => Hash::make($studentCode),
                                'full_name'     => "{$firstName} {$lastName}",
                                'department'    => $course,
                                'year_level'    => $yearLevel,
                                'section'       => $sectionShort,
                                'role'          => 'student',
                                'is_active'     => true,
                                'semester'      => $this->semester,
                                'academic_year' => $this->academicYear,
                                'created_at'    => now(),
                                'updated_at'    => now(),
                            ];
                        } else {
                            User::where('student_id', $studentCode)->update([
                                'semester'      => $this->semester,
                                'academic_year' => $this->academicYear,
                                'department'    => $course,
                                'year_level'    => $yearLevel,
                                'section'       => $sectionShort,
                                'updated_at'    => now(),
                            ]);
                        }

                        $this->existingEnrollments[$studentCode] = true;
                        $this->imported++;

                    } catch (\Exception $e) {
                        $this->skipped++;
                        $this->errors[] = "Insert error for {$studentCode}: " . $e->getMessage();
                    }
                }
            }

            if (!empty($toInsertEnrollments)) {
                Enrollment::insert($toInsertEnrollments);
            }

            if (!empty($toInsertUsers)) {
                User::insert($toInsertUsers);
            }

            unset($toInsertEnrollments, $toInsertUsers, $codesInChunk, $existingUserCodes, $existingAnyEnrollment);
        }

        // ── Write final stats to the batch record ─────────────────────────────
        // (syncSemesterRecord is called once via AfterImport event, not here)
        $this->batch->update([
            'imported_records' => $this->imported + $this->updated,
            'skipped_records'  => $this->skipped,
            'errors'           => $this->errors,
            'courses'          => array_values($this->courses),
        ]);
    }

    // ── String cleaners ───────────────────────────────────────────────────────

    /**
     * Strip leading asterisks and whitespace from student codes.
     * Some exports mark irregular students with "****" prefixes e.g. "**** Aquino".
     * This is on the name column but occasionally bleeds into the code column too.
     */
    private function cleanStudentCode(string $raw): string
    {
        return trim(ltrim($raw, '* '));
    }

    /**
     * Strip leading asterisks from name fields (irregular student markers).
     */
    private function cleanName(string $raw): string
    {
        return trim(ltrim($raw, '* '));
    }

    // ── Getters ───────────────────────────────────────────────────────────────

    public function getImported(): int        { return $this->imported; }
    public function getUpdated(): int         { return $this->updated; }
    public function getSkipped(): int         { return $this->skipped; }
    public function getErrors(): array        { return $this->errors; }
    public function getCourses(): array       { return array_values($this->courses); }
    public function getSemester(): string     { return $this->semester; }
    public function getAcademicYear(): string { return $this->academicYear; }
}
