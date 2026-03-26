<?php
namespace App\Imports;

use App\Models\Enrollment;
use App\Models\UploadBatch;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class EnrollmentImport implements ToCollection, WithStartRow
{
    public UploadBatch $batch;
    private string $semester;
    private string $academicYear;
    private int $imported = 0;
    private int $skipped  = 0;
    private array $errors = [];

    public function __construct(UploadBatch $batch, string $semester, string $academicYear)
    {
        $this->batch        = $batch;
        $this->semester     = $semester;
        $this->academicYear = $academicYear;
    }

    /**
     * Skip rows 1-8 (school header + column headers).
     * Your Excel layout:
     *   Row 1 = School name
     *   Row 2 = Address
     *   Row 3 = blank
     *   Row 4 = "Enrollment List"
     *   Row 5 = Period
     *   Row 6 = Course
     *   Row 7 = blank
     *   Row 8 = Column headers (No, Code, Last Name, ...)
     *   Row 9+ = Student data  ← we start here
     */
    public function startRow(): int
    {
        return 9;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            // Skip completely empty rows
            if (empty($row[1])) {
                continue;
            }

            // Map columns (0-indexed):
            // 0=No, 1=Code, 2=LastName, 3=FirstName,
            // 4=MiddleName, 5=Sex, 6=Course, 7=Year, 8=Units, 9=Section
            $studentCode = trim((string) $row[1]);
            $lastName    = trim((string) $row[2]);
            $firstName   = trim((string) $row[3]);
            $middleName  = trim((string) ($row[4] ?? ''));
            $sex         = strtoupper(trim((string) ($row[5] ?? 'M')));
            $course      = trim((string) ($row[6] ?? ''));
            $yearLevel   = (int) ($row[7] ?? 1);
            $units       = (int) ($row[8] ?? 0);
            $section     = trim((string) ($row[9] ?? ''));

            // Validate required fields
            if (empty($studentCode) || empty($lastName) || empty($firstName)) {
                $this->skipped++;
                $this->errors[] = "Skipped row — missing required fields. Code: '{$studentCode}'";
                continue;
            }

            // Normalise sex value
            if (!in_array($sex, ['M', 'F'])) {
                $sex = 'M';
            }

            try {
                // 1. Upsert enrollment record
                Enrollment::updateOrCreate(
                    [
                        'student_code'  => $studentCode,
                        'semester'      => $this->semester,
                        'academic_year' => $this->academicYear,
                    ],
                    [
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
                    ]
                );

                // 2. Upsert user account (default password = student code)
                User::updateOrCreate(
                    ['student_id' => $studentCode],
                    [
                        'email'      => strtolower(str_replace([' ', '/'], '_', $studentCode))
                                        . '@student.votecast.edu',
                        'password'   => Hash::make($studentCode),
                        'full_name'  => "{$firstName} {$lastName}",
                        'department' => $course,
                        'year_level' => $yearLevel,
                        'section'    => $section,
                        'role'       => 'student',
                        'is_active'  => true,
                    ]
                );

                $this->imported++;
            } catch (\Exception $e) {
                $this->skipped++;
                $this->errors[] = "Error for {$studentCode}: " . $e->getMessage();
            }
        }

        // Write final statistics back to the batch record
        $this->batch->update([
            'imported_records' => $this->imported,
            'skipped_records'  => $this->skipped,
            'errors'           => $this->errors,
        ]);
    }

    public function getImported(): int { return $this->imported; }
    public function getSkipped(): int  { return $this->skipped; }
    public function getErrors(): array { return $this->errors; }
}
