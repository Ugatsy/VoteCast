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

class EnrollmentImport implements ToCollection, WithStartRow, WithChunkReading
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

    public function __construct(UploadBatch $batch, string $semester, string $academicYear)
    {
        $this->batch        = $batch;
        $this->semester     = $semester;
        $this->academicYear = $academicYear;

        // Pre-load existing enrollments for this semester/year to detect true duplicates.
        // NOTE: this is intentionally loaded AFTER parsePeriodRow() has run in collection(),
        // so if the semester changes mid-import we reload it (see Step 1 below).
        $this->existingEnrollments = Enrollment::where('semester', $semester)
            ->where('academic_year', $academicYear)
            ->pluck('student_code')
            ->flip()
            ->toArray();
    }

    public function startRow(): int  { return 1; }
    public function chunkSize(): int { return 200; }

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
        if (str_starts_with($lower, 'third')  || str_starts_with($lower, '3rd')) return '2nd Semester';
        if (str_starts_with($lower, 'summer'))                                    return 'Summer';

        return trim($raw);
    }

    /**
     * Parse Row 5 of the Excel into [semester, academicYear].
     *
     * THE BUG THIS FIXES:
     * Some files put the label and the value in SEPARATE cells:
     *   col 0 = "Period"   col 1 = "Second Semester 2025-2026"
     *
     * The old code stopped at the first non-empty cell (col 0 = "Period"),
     * failed to parse it as a semester string, and fell back to the constructor
     * value — so "2nd Semester" was never detected.
     *
     * THE FIX:
     * Collect ALL non-empty cells in the row, try each one individually,
     * and also try concatenating adjacent pairs so that label+value spread
     * across two cells is handled correctly.
     *
     * Handles all known formats:
     *   col0="Period"  col1="Second Semester 2025-2026"  ← your actual file
     *   col0="First Semester 2025-2026"                  ← single-cell
     *   col0="Period: 1st Semester 2025-2026"            ← colon-label in same cell
     *   col0="Period"  col1="Summer 2025-2026"
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

        $semesterPattern = '/^((?:First|Second|Third|Summer|1st|2nd|3rd)(?:\s+Semester)?)\s+(\d{4}-\d{4})$/i';

        // Pass 1: try each cell individually
        // Strip an optional "Label:" prefix (e.g. "Period: First Semester 2025-2026")
        foreach ($cells as $cell) {
            $cleaned = trim(preg_replace('/^[^:]+:\s*/i', '', $cell));
            if (preg_match($semesterPattern, $cleaned, $m)) {
                return [$this->normaliseSemesterName($m[1]), $m[2]];
            }
        }

        // Pass 2: try concatenating adjacent cell pairs
        // Catches: col0="Period"  col1="Second Semester 2025-2026"
        for ($i = 0; $i < count($cells) - 1; $i++) {
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
     * Sync the semester record to the database
     * This creates or updates the semester and sets it as active
     */
    private function syncSemesterRecord(): void
    {
        try {
            // First, deactivate all existing semesters
            Semester::where('is_active', true)->update(['is_active' => false]);
            
            // Check if semester already exists with this name and academic year
            $semester = Semester::where('name', $this->semester)
                ->where('academic_year', $this->academicYear)
                ->first();
            
            if (!$semester) {
                // Create new semester record
                $semester = new Semester();
                $semester->name = $this->semester;
                $semester->academic_year = $this->academicYear;
                
                // Set sensible date ranges based on academic year and semester
                $year = explode('-', $this->academicYear)[0];
                
                if ($this->semester === '1st Semester') {
                    $semester->start_date = date('Y-m-d', strtotime("{$year}-08-01"));
                    $semester->end_date = date('Y-m-d', strtotime("{$year}-12-15"));
                } elseif ($this->semester === '2nd Semester') {
                    $semester->start_date = date('Y-m-d', strtotime(($year + 1) . "-01-01"));
                    $semester->end_date = date('Y-m-d', strtotime(($year + 1) . "-05-31"));
                } elseif ($this->semester === 'Summer') {
                    $semester->start_date = date('Y-m-d', strtotime(($year + 1) . "-06-01"));
                    $semester->end_date = date('Y-m-d', strtotime(($year + 1) . "-07-31"));
                }
            }
            
            $semester->is_active = true;
            $semester->save();
            
            \Log::info('Semester synced successfully', [
                'semester' => $this->semester,
                'academic_year' => $this->academicYear,
                'semester_id' => $semester->id
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to sync semester record: ' . $e->getMessage(), [
                'semester' => $this->semester,
                'academic_year' => $this->academicYear
            ]);
        }
    }

    // ── Main collection handler ───────────────────────────────────────────────

    public function collection(Collection $rows): void
    {
        // ── Step 1: Parse Period from Row 5 (0-index 4) ──────────────────────
        $periodRow = $rows->get(4);
        if ($periodRow) {
            $previousSemester     = $this->semester;
            $previousAcademicYear = $this->academicYear;

            [$this->semester, $this->academicYear] = $this->parsePeriodRow($periodRow);

            // If the semester/year changed (Excel says something different from the
            // form's hidden fields), reload the duplicate-detection cache so it
            // matches the actual target semester.
            if ($this->semester !== $previousSemester || $this->academicYear !== $previousAcademicYear) {
                $this->existingEnrollments = Enrollment::where('semester', $this->semester)
                    ->where('academic_year', $this->academicYear)
                    ->pluck('student_code')
                    ->flip()
                    ->toArray();
            }
        }

        // ── Step 2: Student rows start at row 9 (0-index = 8) ────────────────
        $studentRows = $rows->slice(8);

        // ── Step 3: Chunk processing ──────────────────────────────────────────
        foreach ($studentRows->chunk(100) as $chunk) {

            $codesInChunk = $chunk
                ->filter(fn($row) => !empty(trim((string) ($row[1] ?? ''))))
                ->map(fn($row)    => trim((string) $row[1]))
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
                // columns 10+ (Email, Contact No, etc.) are intentionally ignored.
                $studentCode = trim((string) ($row[1] ?? ''));
                if (empty($studentCode)) continue;

                $lastName   = trim((string) ($row[2] ?? ''));
                $firstName  = trim((string) ($row[3] ?? ''));
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

        // ── Sync the semester record to database ──────────────────────────────
        $this->syncSemesterRecord();

        // ── Write final stats to the batch record ─────────────────────────────
        $this->batch->update([
            'imported_records' => $this->imported + $this->updated,
            'skipped_records'  => $this->skipped,
            'errors'           => $this->errors,
            'courses'          => array_values($this->courses),
        ]);
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