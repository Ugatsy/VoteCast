# VoteCast — Complete Build Guide
### Laravel 11 + MySQL + Excel Enrollment Integration

---

## YOUR EXCEL FILE STRUCTURE (25-1.xlsx)

The system detected your Excel file has:
- **Row 1–2**: School header (Aldersgate College, Solano Nueva Vizcaya)
- **Row 4**: "Enrollment List" title
- **Row 5**: Period → `First Semester 2025-2026`
- **Row 6**: Course → `Bachelor of Science in Information Technology`
- **Row 8**: Column headers → `No | Code | Last Name | First Name | Middle Name | Sex | Course | Year | Units | Section`
- **Row 9+**: Student data (177 students)

The importer will **skip rows 1–7** and read from row 8 onward.

---

## PHASE 1: PROJECT SETUP

### 1.1 Requirements
- PHP 8.2+
- Composer 2.x
- MySQL 8.0+
- Node.js 18+

### 1.2 Create Laravel Project

```bash
composer create-project laravel/laravel votecast "^11.0"
cd votecast
```

### 1.3 Install Required Packages

```bash
# Excel import/export
composer require maatwebsite/excel "^3.1"

# Laravel UI (Bootstrap scaffolding)
composer require laravel/ui "^4.0"

# Generate Bootstrap auth scaffolding
php artisan ui bootstrap --auth

# Install NPM dependencies and compile
npm install && npm run build
```

### 1.4 Configure .env

```env
APP_NAME=VoteCast
APP_ENV=local
APP_KEY=base64:YOUR_KEY_HERE
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=votecast
DB_USERNAME=root
DB_PASSWORD=

FILESYSTEM_DISK=public
```

### 1.5 Create Database

```sql
CREATE DATABASE votecast CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

---

## PHASE 2: DATABASE MIGRATIONS

Create these migration files in order inside `database/migrations/`.

### Migration 1: Users Table
**File:** `2024_01_01_000001_create_users_table.php`

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('student_id', 50)->unique()->nullable()->comment('Only for students');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('full_name');
            $table->string('photo')->nullable();
            $table->string('department', 100)->nullable()->index();
            $table->integer('year_level')->nullable();
            $table->string('section', 20)->nullable();
            $table->enum('role', ['student','super_admin','election_admin','department_admin','view_only'])
                  ->default('student');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
```

### Migration 2: Courses Table
**File:** `2024_01_01_000002_create_courses_table.php`

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('department', 100)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
```

### Migration 3: Upload Batches Table
**File:** `2024_01_01_000003_create_upload_batches_table.php`

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('upload_batches', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('semester', 50);
            $table->string('academic_year', 20);
            $table->integer('total_records')->default(0);
            $table->integer('imported_records')->default(0);
            $table->integer('skipped_records')->default(0);
            $table->json('statistics')->nullable();
            $table->json('errors')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->index(['semester', 'academic_year'], 'idx_batch_semester');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upload_batches');
    }
};
```

### Migration 4: Enrollments Table
**File:** `2024_01_01_000004_create_enrollments_table.php`

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->string('student_code', 50);
            $table->string('last_name', 100);
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->enum('sex', ['M', 'F']);
            $table->string('course', 50);
            $table->integer('year_level');
            $table->string('section', 20);
            $table->integer('units')->default(0);
            $table->string('semester', 50);
            $table->string('academic_year', 20);
            $table->boolean('is_active')->default(true);
            $table->foreignId('upload_batch_id')->nullable()
                  ->constrained('upload_batches')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_code', 'semester', 'academic_year'], 'unique_enrollment');
            $table->index(['semester', 'academic_year', 'is_active'], 'idx_current_semester');
            $table->index(['course', 'year_level', 'section'], 'idx_course_year_section');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
```

### Migration 5: Voting Sessions Table
**File:** `2024_01_01_000005_create_voting_sessions_table.php`

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('voting_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('category', ['department', 'course', 'manual'])->default('course');
            $table->string('target_department', 100)->nullable();
            $table->string('target_course', 50)->nullable();
            $table->enum('status', ['scheduled', 'active', 'paused', 'completed', 'cancelled'])
                  ->default('scheduled');
            $table->datetime('start_date');
            $table->datetime('end_date');
            $table->boolean('allow_vote_changes')->default(false);
            $table->boolean('requires_release_code')->default(false);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index('status', 'idx_status');
            $table->index(['start_date', 'end_date'], 'idx_dates');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voting_sessions');
    }
};
```

### Migration 6: Positions Table
**File:** `2024_01_01_000006_create_positions_table.php`

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voting_session_id')->constrained('voting_sessions')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('max_winners')->default(1);
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
```

### Migration 7: Candidates Table
**File:** `2024_01_01_000007_create_candidates_table.php`

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->constrained('positions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->text('manifesto')->nullable();
            $table->string('photo')->nullable();
            $table->boolean('is_approved')->default(true);
            $table->timestamps();

            $table->unique(['position_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
```

### Migration 8: Votes Table
**File:** `2024_01_01_000008_create_votes_table.php`

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voting_session_id')->constrained('voting_sessions')->cascadeOnDelete();
            $table->foreignId('position_id')->constrained('positions')->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->foreignId('voter_id')->constrained('users')->cascadeOnDelete();
            $table->string('receipt_id', 64)->unique();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['voter_id', 'position_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
```

### Migration 9: Release Codes Table
**File:** `2024_01_01_000009_create_release_codes_table.php`

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('release_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voting_session_id')->constrained('voting_sessions')->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['voting_session_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('release_codes');
    }
};
```

### Migration 10: Manual Voters Table
**File:** `2024_01_01_000010_create_manual_voters_table.php`

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('manual_voters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voting_session_id')->constrained('voting_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('added_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['voting_session_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_voters');
    }
};
```

### Run All Migrations

```bash
php artisan migrate
```

---

## PHASE 3: MODELS

### 3.1 User Model
**File:** `app/Models/User.php`

```php
<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'student_id', 'email', 'password', 'full_name',
        'photo', 'department', 'year_level', 'section',
        'role', 'is_active', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'is_active'      => 'boolean',
        'last_login_at'  => 'datetime',
    ];

    // Relationships
    public function enrollment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Enrollment::class, 'student_code', 'student_id');
    }

    public function votes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Vote::class, 'voter_id');
    }

    public function candidates(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Candidate::class, 'student_id');
    }

    // Scopes
    public function scopeStudents($query)
    {
        return $query->where('role', 'student');
    }

    public function scopeAdmins($query)
    {
        return $query->whereIn('role', ['super_admin', 'election_admin', 'department_admin', 'view_only']);
    }

    // Helpers
    public function isAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'election_admin', 'department_admin', 'view_only']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function getFullNameAttribute($value): string
    {
        return $value ?? '';
    }
}
```

### 3.2 Enrollment Model
**File:** `app/Models/Enrollment.php`

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    protected $fillable = [
        'student_code', 'last_name', 'first_name', 'middle_name',
        'sex', 'course', 'year_level', 'section', 'units',
        'semester', 'academic_year', 'is_active', 'upload_batch_id',
    ];

    protected $casts = ['is_active' => 'boolean'];

    // Relationships
    public function uploadBatch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(UploadBatch::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id', 'student_code');
    }

    // Scopes
    public function scopeCurrent($query)
    {
        $semester     = session('current_semester', '1st Semester');
        $academicYear = session('current_academic_year', date('Y') . '-' . (date('Y') + 1));
        return $query->where('semester', $semester)
                     ->where('academic_year', $academicYear)
                     ->where('is_active', true);
    }

    // Computed attribute
    public function getFullNameAttribute(): string
    {
        $middle = $this->middle_name ? ' ' . $this->middle_name : '';
        return "{$this->first_name}{$middle} {$this->last_name}";
    }
}
```

### 3.3 UploadBatch Model
**File:** `app/Models/UploadBatch.php`

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UploadBatch extends Model
{
    protected $fillable = [
        'filename', 'semester', 'academic_year',
        'total_records', 'imported_records', 'skipped_records',
        'statistics', 'errors', 'uploaded_by',
    ];

    protected $casts = [
        'statistics' => 'array',
        'errors'     => 'array',
    ];

    public function enrollments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function uploader(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
```

### 3.4 VotingSession Model
**File:** `app/Models/VotingSession.php`

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VotingSession extends Model
{
    protected $fillable = [
        'title', 'description', 'category',
        'target_department', 'target_course',
        'status', 'start_date', 'end_date',
        'allow_vote_changes', 'requires_release_code', 'created_by',
    ];

    protected $casts = [
        'start_date'            => 'datetime',
        'end_date'              => 'datetime',
        'allow_vote_changes'    => 'boolean',
        'requires_release_code' => 'boolean',
    ];

    public function positions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Position::class)->orderBy('display_order');
    }

    public function votes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function releaseCodes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ReleaseCode::class);
    }

    public function manualVoters(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ManualVoter::class);
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->where('start_date', '<=', now())
                     ->where('end_date', '>=', now());
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->start_date <= now()
            && $this->end_date >= now();
    }

    public function isEligible(User $user): bool
    {
        return match($this->category) {
            'course'     => $this->target_course === $user->department,
            'department' => $this->target_department === $user->department,
            'manual'     => $this->manualVoters()->where('user_id', $user->id)->exists(),
            default      => false,
        };
    }
}
```

### 3.5 Position Model
**File:** `app/Models/Position.php`

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    protected $fillable = [
        'voting_session_id', 'title', 'description',
        'max_winners', 'display_order',
    ];

    public function votingSession(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(VotingSession::class);
    }

    public function candidates(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    public function votes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Vote::class);
    }
}
```

### 3.6 Candidate Model
**File:** `app/Models/Candidate.php`

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    protected $fillable = [
        'position_id', 'student_id', 'manifesto', 'photo', 'is_approved',
    ];

    protected $casts = ['is_approved' => 'boolean'];

    public function position(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function student(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function votes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function getPhotoUrlAttribute(): string
    {
        return $this->photo
            ? asset('storage/' . $this->photo)
            : asset('images/default-avatar.png');
    }
}
```

### 3.7 Vote Model
**File:** `app/Models/Vote.php`

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'voting_session_id', 'position_id', 'candidate_id',
        'voter_id', 'receipt_id', 'ip_address', 'user_agent',
    ];

    protected $casts = ['created_at' => 'datetime'];

    public function candidate(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function voter(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'voter_id');
    }

    public function position(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function votingSession(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(VotingSession::class);
    }
}
```

### 3.8 ReleaseCode Model
**File:** `app/Models/ReleaseCode.php`

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReleaseCode extends Model
{
    protected $fillable = [
        'voting_session_id', 'code', 'description', 'is_active', 'expires_at',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function votingSession(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(VotingSession::class);
    }

    public function isValid(): bool
    {
        return $this->is_active && (!$this->expires_at || $this->expires_at > now());
    }
}
```

### 3.9 ManualVoter Model
**File:** `app/Models/ManualVoter.php`

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualVoter extends Model
{
    public $timestamps = false;
    protected $fillable = ['voting_session_id', 'user_id', 'added_by'];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

---

## PHASE 4: EXCEL IMPORTER

### 4.1 Install & Configure

```bash
# Publish maatwebsite/excel config
php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider" --tag=config
```

### 4.2 Create the Importer
**File:** `app/Imports/EnrollmentImport.php`

```php
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
    private int $imported  = 0;
    private int $skipped   = 0;
    private array $errors  = [];

    public function __construct(UploadBatch $batch, string $semester, string $academicYear)
    {
        $this->batch        = $batch;
        $this->semester     = $semester;
        $this->academicYear = $academicYear;
    }

    // Skip header rows (1-7) + column header row (8) = start at row 9
    public function startRow(): int
    {
        return 9;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            // Skip empty rows
            if (empty($row[1])) continue;

            // Row columns: 0=No, 1=Code, 2=LastName, 3=FirstName,
            //              4=MiddleName, 5=Sex, 6=Course, 7=Year, 8=Units, 9=Section
            $studentCode = trim((string) $row[1]);
            $lastName    = trim((string) $row[2]);
            $firstName   = trim((string) $row[3]);
            $middleName  = trim((string) ($row[4] ?? ''));
            $sex         = strtoupper(trim((string) $row[5]));
            $course      = trim((string) $row[6]);
            $yearLevel   = (int) $row[7];
            $units       = (int) ($row[8] ?? 0);
            $section     = trim((string) $row[9]);

            // Basic validation
            if (empty($studentCode) || empty($lastName) || empty($firstName)) {
                $this->skipped++;
                $this->errors[] = "Skipped row - missing required fields: {$studentCode}";
                continue;
            }

            if (!in_array($sex, ['M', 'F'])) {
                $sex = 'M'; // Default
            }

            try {
                // 1. Create or update enrollment
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

                // 2. Create or update user account
                $fullName = "{$firstName} {$lastName}";
                User::updateOrCreate(
                    ['student_id' => $studentCode],
                    [
                        'email'      => strtolower($studentCode) . '@student.votecast.edu',
                        'password'   => Hash::make($studentCode), // Default password = student code
                        'full_name'  => $fullName,
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

        // Update batch statistics
        $this->batch->update([
            'imported_records' => $this->imported,
            'skipped_records'  => $this->skipped,
            'errors'           => $this->errors,
        ]);
    }

    public function getImported(): int  { return $this->imported; }
    public function getSkipped(): int   { return $this->skipped; }
    public function getErrors(): array  { return $this->errors; }
}
```

---

## PHASE 5: MIDDLEWARE

### 5.1 Admin Middleware
**File:** `app/Http/Middleware/AdminMiddleware.php`

```php
<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'Access denied. Admins only.');
        }
        return $next($request);
    }
}
```

### 5.2 Student Middleware
**File:** `app/Http/Middleware/StudentMiddleware.php`

```php
<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StudentMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || auth()->user()->role !== 'student') {
            return redirect()->route('student.login');
        }
        return $next($request);
    }
}
```

### 5.3 Register Middleware
**File:** `bootstrap/app.php` — add inside `withMiddleware`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'admin'   => \App\Http\Middleware\AdminMiddleware::class,
        'student' => \App\Http\Middleware\StudentMiddleware::class,
    ]);
})
```

---

## PHASE 6: CONTROLLERS

### 6.1 Admin Auth Controller
**File:** `app/Http/Controllers/Admin/AuthController.php`

```php
<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            if (!Auth::user()->isAdmin()) {
                Auth::logout();
                return back()->withErrors(['email' => 'Not authorized as admin.']);
            }
            Auth::user()->update(['last_login_at' => now()]);
            $request->session()->regenerate();
            return redirect()->route('admin.dashboard');
        }

        return back()->withErrors(['email' => 'Invalid credentials.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
```

### 6.2 Admin Dashboard Controller
**File:** `app/Http/Controllers/Admin/DashboardController.php`

```php
<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\Vote;
use App\Models\VotingSession;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_students'  => User::students()->count(),
            'total_sessions'  => VotingSession::count(),
            'active_sessions' => VotingSession::active()->count(),
            'total_votes'     => Vote::count(),
            'enrollments'     => Enrollment::current()->count(),
        ];

        $recentSessions = VotingSession::with('creator')
            ->latest()
            ->take(5)
            ->get();

        $activeSessions = VotingSession::active()->with('positions')->get();

        return view('admin.dashboard', compact('stats', 'recentSessions', 'activeSessions'));
    }
}
```

### 6.3 Enrollment Controller
**File:** `app/Http/Controllers/Admin/EnrollmentController.php`

```php
<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\EnrollmentImport;
use App\Models\Enrollment;
use App\Models\UploadBatch;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class EnrollmentController extends Controller
{
    public function index()
    {
        $batches     = UploadBatch::with('uploader')->latest()->get();
        $enrollments = Enrollment::current()->paginate(50);

        $currentSemester     = session('current_semester', '1st Semester');
        $currentAcademicYear = session('current_academic_year', date('Y') . '-' . (date('Y') + 1));

        return view('admin.enrollment.index', compact(
            'batches', 'enrollments', 'currentSemester', 'currentAcademicYear'
        ));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'excel_file'   => 'required|file|mimes:xlsx,xls|max:10240',
            'semester'     => 'required|string',
            'academic_year'=> 'required|string',
        ]);

        $file     = $request->file('excel_file');
        $filename = time() . '_' . $file->getClientOriginalName();
        $file->storeAs('uploads/enrollment', $filename);

        // Create the upload batch record
        $batch = UploadBatch::create([
            'filename'       => $filename,
            'semester'       => $request->semester,
            'academic_year'  => $request->academic_year,
            'total_records'  => 0,
            'uploaded_by'    => auth()->id(),
        ]);

        // Run the import
        $import = new EnrollmentImport($batch, $request->semester, $request->academic_year);
        Excel::import($import, $file);

        // Update total records count
        $batch->update(['total_records' => $import->getImported() + $import->getSkipped()]);

        // Set active semester in session
        session([
            'current_semester'     => $request->semester,
            'current_academic_year'=> $request->academic_year,
        ]);

        return redirect()->route('admin.enrollment.index')
            ->with('success', "Import complete! {$import->getImported()} students imported, {$import->getSkipped()} skipped.");
    }

    public function setSemester(Request $request)
    {
        $request->validate([
            'semester'      => 'required|string',
            'academic_year' => 'required|string',
        ]);

        session([
            'current_semester'     => $request->semester,
            'current_academic_year'=> $request->academic_year,
        ]);

        return back()->with('success', 'Active semester updated.');
    }
}
```

### 6.4 Voting Session Controller
**File:** `app/Http/Controllers/Admin/VotingSessionController.php`

```php
<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Position;
use App\Models\ReleaseCode;
use App\Models\User;
use App\Models\Vote;
use App\Models\VotingSession;
use Illuminate\Http\Request;

class VotingSessionController extends Controller
{
    public function index()
    {
        $sessions = VotingSession::with('creator', 'positions')->latest()->paginate(10);
        return view('admin.sessions.index', compact('sessions'));
    }

    public function create()
    {
        $courses  = Enrollment::current()->select('course')->distinct()->pluck('course');
        $students = User::students()->active()->get();
        return view('admin.sessions.create', compact('courses', 'students'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'category'    => 'required|in:department,course,manual',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after:start_date',
        ]);

        $session = VotingSession::create([
            'title'                 => $request->title,
            'description'           => $request->description,
            'category'              => $request->category,
            'target_course'         => $request->target_course,
            'target_department'     => $request->target_department,
            'status'                => 'scheduled',
            'start_date'            => $request->start_date,
            'end_date'              => $request->end_date,
            'allow_vote_changes'    => $request->boolean('allow_vote_changes'),
            'requires_release_code' => $request->boolean('requires_release_code'),
            'created_by'            => auth()->id(),
        ]);

        return redirect()->route('admin.sessions.candidates', $session)
            ->with('success', 'Session created! Now add positions and candidates.');
    }

    public function show(VotingSession $votingSession)
    {
        $votingSession->load('positions.candidates.student', 'creator');
        return view('admin.sessions.show', compact('votingSession'));
    }

    public function updateStatus(Request $request, VotingSession $votingSession)
    {
        $request->validate(['status' => 'required|in:scheduled,active,paused,completed,cancelled']);
        $votingSession->update(['status' => $request->status]);
        return back()->with('success', 'Session status updated.');
    }

    public function candidates(VotingSession $votingSession)
    {
        $votingSession->load('positions.candidates.student');
        $students = User::students()->get();
        return view('admin.sessions.candidates', compact('votingSession', 'students'));
    }

    public function addPosition(Request $request, VotingSession $votingSession)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'max_winners' => 'integer|min:1',
        ]);

        $order = $votingSession->positions()->count();
        $votingSession->positions()->create([
            'title'         => $request->title,
            'description'   => $request->description,
            'max_winners'   => $request->max_winners ?? 1,
            'display_order' => $order,
        ]);

        return back()->with('success', 'Position added.');
    }

    public function addCandidate(Request $request, Position $position)
    {
        $request->validate([
            'student_id' => 'required|exists:users,id',
            'manifesto'  => 'nullable|string',
            'photo'      => 'nullable|image|max:2048',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('candidates', 'public');
        }

        Candidate::create([
            'position_id' => $position->id,
            'student_id'  => $request->student_id,
            'manifesto'   => $request->manifesto,
            'photo'       => $photoPath,
            'is_approved' => true,
        ]);

        return back()->with('success', 'Candidate added.');
    }

    public function results(VotingSession $votingSession)
    {
        $votingSession->load('positions.candidates.student');

        $results = $votingSession->positions->map(function ($position) {
            $totalVotes = $position->votes()->count();
            $candidates = $position->candidates->map(function ($candidate) use ($totalVotes) {
                $voteCount  = $candidate->votes()->count();
                $percentage = $totalVotes > 0 ? round(($voteCount / $totalVotes) * 100, 2) : 0;
                return [
                    'candidate'  => $candidate,
                    'vote_count' => $voteCount,
                    'percentage' => $percentage,
                ];
            })->sortByDesc('vote_count')->values();

            return [
                'position'    => $position,
                'total_votes' => $totalVotes,
                'candidates'  => $candidates,
            ];
        });

        $totalVoters = User::students()->count();
        $totalVoted  = $votingSession->votes()->distinct('voter_id')->count();

        return view('admin.sessions.results', compact('votingSession', 'results', 'totalVoters', 'totalVoted'));
    }
}
```

### 6.5 Student Auth Controller
**File:** `app/Http/Controllers/Student/AuthController.php`

```php
<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLanding()
    {
        if (auth()->check() && auth()->user()->role === 'student') {
            return redirect()->route('student.dashboard');
        }
        return view('student.landing');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'student_id' => 'required|string',
        ]);

        $studentId = trim($request->student_id);

        // Check if enrolled in current semester
        $enrollment = Enrollment::current()
            ->where('student_code', $studentId)
            ->first();

        if (!$enrollment) {
            return back()->withErrors([
                'student_id' => 'Student ID not found in current enrollment list.',
            ])->withInput();
        }

        // Get or create user account
        $user = User::updateOrCreate(
            ['student_id' => $studentId],
            [
                'email'      => strtolower($studentId) . '@student.votecast.edu',
                'password'   => Hash::make($studentId),
                'full_name'  => $enrollment->full_name,
                'department' => $enrollment->course,
                'year_level' => $enrollment->year_level,
                'section'    => $enrollment->section,
                'role'       => 'student',
                'is_active'  => true,
            ]
        );

        if (!$user->is_active) {
            return back()->withErrors(['student_id' => 'Your account has been deactivated.']);
        }

        Auth::login($user);
        $user->update(['last_login_at' => now()]);
        $request->session()->regenerate();

        return redirect()->route('student.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('student.landing');
    }
}
```

### 6.6 Student Dashboard Controller
**File:** `app/Http/Controllers/Student/DashboardController.php`

```php
<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Vote;
use App\Models\VotingSession;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Find all sessions the student is eligible for
        $allActiveSessions = VotingSession::active()->get();

        $availableSessions = $allActiveSessions->filter(fn($s) => $s->isEligible($user));

        // Get sessions the student already voted in
        $votedSessionIds = Vote::where('voter_id', $user->id)
            ->distinct('voting_session_id')
            ->pluck('voting_session_id');

        $votedSessions = VotingSession::whereIn('id', $votedSessionIds)->get();

        // Separate available from already voted
        $pendingSessions = $availableSessions->whereNotIn('id', $votedSessionIds->toArray());

        return view('student.dashboard', compact(
            'user', 'pendingSessions', 'votedSessions'
        ));
    }
}
```

### 6.7 Voting Ballot Controller
**File:** `app/Http/Controllers/Student/VotingBallotController.php`

```php
<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Vote;
use App\Models\VotingSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VotingBallotController extends Controller
{
    public function show(VotingSession $votingSession)
    {
        $user = auth()->user();

        // Check eligibility
        if (!$votingSession->isEligible($user)) {
            abort(403, 'You are not eligible to vote in this election.');
        }

        // Check if already voted
        $alreadyVoted = Vote::where('voter_id', $user->id)
            ->where('voting_session_id', $votingSession->id)
            ->exists();

        if ($alreadyVoted && !$votingSession->allow_vote_changes) {
            return redirect()->route('student.dashboard')
                ->with('info', 'You have already voted in this election.');
        }

        if (!$votingSession->isActive()) {
            return redirect()->route('student.dashboard')
                ->with('error', 'This election is not currently active.');
        }

        $votingSession->load('positions.candidates.student');

        return view('student.ballot', compact('votingSession', 'alreadyVoted'));
    }

    public function submit(Request $request, VotingSession $votingSession)
    {
        $user = auth()->user();

        // Re-validate eligibility
        if (!$votingSession->isEligible($user) || !$votingSession->isActive()) {
            abort(403);
        }

        // Validate release code if required
        if ($votingSession->requires_release_code) {
            $code = $votingSession->releaseCodes()
                ->where('code', $request->release_code)
                ->first();

            if (!$code || !$code->isValid()) {
                return back()->withErrors(['release_code' => 'Invalid or expired release code.']);
            }
        }

        // Validate votes submitted
        $positions = $votingSession->positions;
        $votes     = $request->input('votes', []);

        $request->validate(
            $positions->mapWithKeys(fn($p) => ["votes.{$p->id}" => 'required|exists:candidates,id'])->toArray()
        );

        // Generate one receipt ID for this voting session
        $receiptId = strtoupper(Str::random(8)) . '-' . time();

        // Delete previous votes if vote changes are allowed
        if ($votingSession->allow_vote_changes) {
            Vote::where('voter_id', $user->id)
                ->where('voting_session_id', $votingSession->id)
                ->delete();
        }

        // Save votes
        foreach ($positions as $position) {
            Vote::create([
                'voting_session_id' => $votingSession->id,
                'position_id'       => $position->id,
                'candidate_id'      => $votes[$position->id],
                'voter_id'          => $user->id,
                'receipt_id'        => $receiptId . '-' . $position->id,
                'ip_address'        => $request->ip(),
                'user_agent'        => $request->userAgent(),
            ]);
        }

        return redirect()->route('student.confirmation', [
            'session'   => $votingSession->id,
            'receipt'   => $receiptId,
        ]);
    }

    public function confirmation(Request $request)
    {
        $sessionId = $request->session;
        $receiptId = $request->receipt;

        $votingSession = VotingSession::with('positions.candidates.student')
            ->findOrFail($sessionId);

        $votes = Vote::where('voter_id', auth()->id())
            ->where('voting_session_id', $sessionId)
            ->with('candidate.student', 'position')
            ->get();

        return view('student.confirmation', compact('votingSession', 'votes', 'receiptId'));
    }
}
```

---

## PHASE 7: ROUTES

**File:** `routes/web.php`

```php
<?php
use App\Http\Controllers\Admin;
use App\Http\Controllers\Student;
use Illuminate\Support\Facades\Route;

// === STUDENT ROUTES ===
Route::get('/', [Student\AuthController::class, 'showLanding'])->name('student.landing');
Route::post('/verify', [Student\AuthController::class, 'verify'])->name('student.verify');
Route::post('/student/logout', [Student\AuthController::class, 'logout'])->name('student.logout');

Route::middleware('student')->group(function () {
    Route::get('/dashboard', [Student\DashboardController::class, 'index'])->name('student.dashboard');
    Route::get('/vote/{votingSession}', [Student\VotingBallotController::class, 'show'])->name('student.ballot');
    Route::post('/vote/{votingSession}', [Student\VotingBallotController::class, 'submit'])->name('student.vote');
    Route::get('/confirmation', [Student\VotingBallotController::class, 'confirmation'])->name('student.confirmation');
});

// === ADMIN ROUTES ===
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [Admin\AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [Admin\AuthController::class, 'login']);
    Route::post('/logout', [Admin\AuthController::class, 'logout'])->name('logout');

    Route::middleware('admin')->group(function () {
        Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

        // Enrollment
        Route::prefix('enrollment')->name('enrollment.')->group(function () {
            Route::get('/', [Admin\EnrollmentController::class, 'index'])->name('index');
            Route::post('/upload', [Admin\EnrollmentController::class, 'upload'])->name('upload');
            Route::post('/semester', [Admin\EnrollmentController::class, 'setSemester'])->name('semester');
        });

        // Sessions
        Route::resource('sessions', Admin\VotingSessionController::class)->except(['edit', 'update', 'destroy']);
        Route::post('/sessions/{votingSession}/status', [Admin\VotingSessionController::class, 'updateStatus'])->name('sessions.status');
        Route::get('/sessions/{votingSession}/candidates', [Admin\VotingSessionController::class, 'candidates'])->name('sessions.candidates');
        Route::post('/sessions/{votingSession}/positions', [Admin\VotingSessionController::class, 'addPosition'])->name('sessions.positions.add');
        Route::post('/positions/{position}/candidates', [Admin\VotingSessionController::class, 'addCandidate'])->name('positions.candidates.add');
        Route::get('/sessions/{votingSession}/results', [Admin\VotingSessionController::class, 'results'])->name('sessions.results');
    });
});
```

---

## PHASE 8: DATABASE SEEDER

**File:** `database/seeders/DatabaseSeeder.php`

```php
<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin
        User::create([
            'email'     => 'admin@votecast.edu',
            'password'  => Hash::make('admin123'),
            'full_name' => 'System Administrator',
            'role'      => 'super_admin',
            'is_active' => true,
        ]);

        // Election Admin
        User::create([
            'email'     => 'election@votecast.edu',
            'password'  => Hash::make('election123'),
            'full_name' => 'Election Officer',
            'role'      => 'election_admin',
            'is_active' => true,
        ]);

        $this->command->info('Admin accounts created!');
        $this->command->info('Super Admin: admin@votecast.edu / admin123');
        $this->command->info('Election Admin: election@votecast.edu / election123');
    }
}
```

```bash
php artisan db:seed
```

---

## PHASE 9: VIEWS

All views use Bootstrap 5. Create the following layout and page files.

### 9.1 Admin Layout
**File:** `resources/views/layouts/admin.blade.php`

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VoteCast Admin — @yield('title', 'Dashboard')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --vc-primary: #1a56db;
            --vc-dark: #111827;
            --sidebar-width: 260px;
        }
        body { background: #f3f4f6; font-family: 'Segoe UI', sans-serif; }
        .sidebar {
            width: var(--sidebar-width); position: fixed;
            top: 0; left: 0; height: 100vh;
            background: var(--vc-dark); overflow-y: auto; z-index: 100;
        }
        .sidebar .brand {
            padding: 1.5rem; font-size: 1.3rem;
            font-weight: 700; color: #fff;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar .brand span { color: var(--vc-primary); }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.7);
            padding: 0.75rem 1.5rem;
            display: flex; align-items: center; gap: 0.75rem;
            transition: all 0.2s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: #fff; background: rgba(255,255,255,0.1);
        }
        .main-content { margin-left: var(--sidebar-width); padding: 2rem; }
        .topbar {
            background: #fff; padding: 1rem 2rem;
            margin: -2rem -2rem 2rem;
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="brand">Vote<span>Cast</span> <small class="text-muted fs-6">Admin</small></div>
        <nav class="mt-3">
            <a href="{{ route('admin.dashboard') }}" class="nav-link @if(request()->routeIs('admin.dashboard')) active @endif">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="{{ route('admin.enrollment.index') }}" class="nav-link @if(request()->routeIs('admin.enrollment.*')) active @endif">
                <i class="bi bi-file-earmark-spreadsheet"></i> Enrollment
            </a>
            <a href="{{ route('admin.sessions.index') }}" class="nav-link @if(request()->routeIs('admin.sessions.*')) active @endif">
                <i class="bi bi-ballot"></i> Elections
            </a>
        </nav>
        <div class="mt-auto p-3" style="position:absolute;bottom:0;width:100%">
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button class="btn btn-sm btn-outline-secondary w-100 text-white">
                    <i class="bi bi-box-arrow-left"></i> Logout
                </button>
            </form>
        </div>
    </aside>
    <main class="main-content">
        <div class="topbar">
            <h5 class="mb-0">@yield('title', 'Dashboard')</h5>
            <span class="text-muted">{{ auth()->user()->full_name }}</span>
        </div>
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @yield('content')
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
```

### 9.2 Admin Login
**File:** `resources/views/admin/auth/login.blade.php`

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>VoteCast Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #111827; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .login-card { background: #fff; border-radius: 12px; padding: 2.5rem; width: 420px; }
        .brand { font-size: 2rem; font-weight: 800; color: #111827; }
        .brand span { color: #1a56db; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand text-center mb-4">Vote<span>Cast</span></div>
        <h5 class="text-center text-muted mb-4">Admin Portal</h5>
        <form method="POST" action="{{ route('admin.login') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email') }}" required>
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
    </div>
</body>
</html>
```

### 9.3 Admin Dashboard
**File:** `resources/views/admin/dashboard.blade.php`

```html
@extends('layouts.admin')
@section('title', 'Dashboard')
@section('content')
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Total Students</div>
                <div class="fs-2 fw-bold">{{ number_format($stats['total_students']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Current Enrollment</div>
                <div class="fs-2 fw-bold text-primary">{{ number_format($stats['enrollments']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Active Elections</div>
                <div class="fs-2 fw-bold text-success">{{ $stats['active_sessions'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Total Votes Cast</div>
                <div class="fs-2 fw-bold text-info">{{ number_format($stats['total_votes']) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between">
                <strong>Recent Elections</strong>
                <a href="{{ route('admin.sessions.create') }}" class="btn btn-sm btn-primary">+ New Election</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr>
                        <th>Title</th><th>Status</th><th>Start</th><th>End</th><th></th>
                    </tr></thead>
                    <tbody>
                    @forelse($recentSessions as $s)
                    <tr>
                        <td>{{ $s->title }}</td>
                        <td><span class="badge bg-{{ $s->status === 'active' ? 'success' : ($s->status === 'completed' ? 'secondary' : 'warning') }}">
                            {{ ucfirst($s->status) }}</span></td>
                        <td>{{ $s->start_date->format('M d, Y') }}</td>
                        <td>{{ $s->end_date->format('M d, Y') }}</td>
                        <td><a href="{{ route('admin.sessions.show', $s) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No elections yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><strong>Quick Actions</strong></div>
            <div class="card-body d-grid gap-2">
                <a href="{{ route('admin.enrollment.index') }}" class="btn btn-outline-primary">
                    <i class="bi bi-upload"></i> Upload Enrollment
                </a>
                <a href="{{ route('admin.sessions.create') }}" class="btn btn-outline-success">
                    <i class="bi bi-plus-circle"></i> Create Election
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
```

### 9.4 Enrollment Management
**File:** `resources/views/admin/enrollment/index.blade.php`

```html
@extends('layouts.admin')
@section('title', 'Enrollment Management')
@section('content')

<!-- Set Active Semester -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white"><strong>Active Semester</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.enrollment.semester') }}" class="row g-3">
            @csrf
            <div class="col-md-4">
                <select name="semester" class="form-select">
                    <option value="1st Semester" @selected($currentSemester === '1st Semester')>1st Semester</option>
                    <option value="2nd Semester" @selected($currentSemester === '2nd Semester')>2nd Semester</option>
                    <option value="Summer" @selected($currentSemester === 'Summer')>Summer</option>
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" name="academic_year" class="form-control"
                       value="{{ $currentAcademicYear }}" placeholder="e.g. 2025-2026">
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary">Set Active Semester</button>
            </div>
        </form>
    </div>
</div>

<!-- Upload Form -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white"><strong>Upload Enrollment Excel</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.enrollment.upload') }}" enctype="multipart/form-data" class="row g-3">
            @csrf
            <div class="col-md-4">
                <label class="form-label">Semester</label>
                <select name="semester" class="form-select" required>
                    <option value="1st Semester">1st Semester</option>
                    <option value="2nd Semester">2nd Semester</option>
                    <option value="Summer">Summer</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Academic Year</label>
                <input type="text" name="academic_year" class="form-control"
                       value="{{ $currentAcademicYear }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Excel File (.xlsx)</label>
                <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button class="btn btn-success w-100">Upload</button>
            </div>
            @error('excel_file')
                <div class="col-12"><div class="alert alert-danger py-2">{{ $message }}</div></div>
            @enderror
        </form>
        <div class="mt-3 text-muted small">
            <strong>Expected format:</strong> Rows 1-7 = header info (school name, semester, course).
            Row 8 = column headers. Row 9+ = student data (No, Code, Last Name, First Name, Middle Name, Sex, Course, Year, Units, Section)
        </div>
    </div>
</div>

<!-- Upload History -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white"><strong>Upload History</strong></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>File</th><th>Semester</th><th>Imported</th><th>Skipped</th><th>Uploaded By</th><th>Date</th></tr>
            </thead>
            <tbody>
            @forelse($batches as $batch)
            <tr>
                <td class="text-truncate" style="max-width:200px">{{ $batch->filename }}</td>
                <td>{{ $batch->semester }} {{ $batch->academic_year }}</td>
                <td><span class="text-success fw-bold">{{ $batch->imported_records }}</span></td>
                <td><span class="text-warning">{{ $batch->skipped_records }}</span></td>
                <td>{{ $batch->uploader->full_name ?? '—' }}</td>
                <td>{{ $batch->created_at->format('M d, Y H:i') }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No uploads yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Current Enrollment List -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <strong>Current Enrollment</strong>
        <span class="badge bg-primary ms-2">{{ $enrollments->total() }} students</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr><th>Code</th><th>Name</th><th>Sex</th><th>Course</th><th>Year</th><th>Section</th></tr>
            </thead>
            <tbody>
            @foreach($enrollments as $e)
            <tr>
                <td><code>{{ $e->student_code }}</code></td>
                <td>{{ $e->full_name }}</td>
                <td>{{ $e->sex }}</td>
                <td>{{ $e->course }}</td>
                <td>{{ $e->year_level }}</td>
                <td>{{ $e->section }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $enrollments->links() }}</div>
</div>
@endsection
```

### 9.5 Student Landing Page
**File:** `resources/views/student/landing.blade.php`

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VoteCast — Student Voting Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0f172a 100%);
            min-height: 100vh; display: flex; align-items: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .vote-card {
            background: rgba(255,255,255,0.05); backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px; padding: 3rem; width: 100%; max-width: 440px;
            color: #fff;
        }
        .brand { font-size: 2.5rem; font-weight: 900; letter-spacing: -1px; }
        .brand span { color: #3b82f6; }
        .form-control {
            background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);
            color: #fff; border-radius: 10px; padding: 0.875rem 1.25rem; font-size: 1.1rem;
        }
        .form-control::placeholder { color: rgba(255,255,255,0.4); }
        .form-control:focus {
            background: rgba(255,255,255,0.15); border-color: #3b82f6;
            color: #fff; box-shadow: 0 0 0 3px rgba(59,130,246,0.25);
        }
        .btn-vote {
            background: #3b82f6; border: none; color: #fff;
            border-radius: 10px; padding: 0.875rem; font-size: 1rem;
            font-weight: 600; transition: all 0.2s;
        }
        .btn-vote:hover { background: #2563eb; transform: translateY(-1px); }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center">
        <div class="vote-card">
            <div class="brand text-center mb-1">Vote<span>Cast</span></div>
            <p class="text-center mb-4" style="color:rgba(255,255,255,0.6)">Enter your Student ID to vote</p>

            @if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('student.verify') }}">
                @csrf
                <div class="mb-4">
                    <input type="text" name="student_id"
                           class="form-control text-center"
                           placeholder="e.g. 25-10194"
                           value="{{ old('student_id') }}"
                           autofocus required>
                </div>
                <button type="submit" class="btn btn-vote w-100">Access Ballot →</button>
            </form>

            <hr style="border-color:rgba(255,255,255,0.1)" class="my-4">
            <p class="text-center mb-0" style="color:rgba(255,255,255,0.4);font-size:0.875rem">
                <a href="{{ route('admin.login') }}" style="color:rgba(255,255,255,0.4)">Admin Login</a>
            </p>
        </div>
    </div>
</body>
</html>
```

### 9.6 Student Dashboard
**File:** `resources/views/student/dashboard.blade.php`

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>VoteCast — My Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f0f4ff; font-family: 'Segoe UI', sans-serif; }
        .topnav { background: #1a56db; padding: 1rem 2rem; color: #fff;
                  display: flex; justify-content: space-between; align-items: center; }
        .election-card { border-radius: 12px; border: none; transition: transform 0.2s; }
        .election-card:hover { transform: translateY(-2px); }
    </style>
</head>
<body>
    <nav class="topnav">
        <div>
            <strong style="font-size:1.3rem">VoteCast</strong>
            <span class="ms-3 opacity-75">{{ $user->full_name }}</span>
        </div>
        <form method="POST" action="{{ route('student.logout') }}">
            @csrf
            <button class="btn btn-sm btn-outline-light">Logout</button>
        </form>
    </nav>

    <div class="container py-4">
        <!-- Student Info -->
        <div class="card election-card shadow-sm mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col"><strong>{{ $user->full_name }}</strong><br><span class="text-muted">{{ $user->student_id }}</span></div>
                    <div class="col text-center"><div class="text-muted small">Course</div><strong>{{ $user->department }}</strong></div>
                    <div class="col text-center"><div class="text-muted small">Year</div><strong>{{ $user->year_level }}</strong></div>
                    <div class="col text-center"><div class="text-muted small">Section</div><strong>{{ $user->section }}</strong></div>
                </div>
            </div>
        </div>

        @if(session('info'))
            <div class="alert alert-info">{{ session('info') }}</div>
        @endif

        <!-- Available Elections -->
        <h5 class="mb-3"><i class="bi bi-ballot text-primary"></i> Available Elections</h5>
        @forelse($pendingSessions as $session)
            <div class="card election-card shadow-sm mb-3">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">{{ $session->title }}</h6>
                        <small class="text-muted">Ends {{ $session->end_date->format('M d, Y H:i') }}</small>
                    </div>
                    <a href="{{ route('student.ballot', $session) }}" class="btn btn-primary">
                        Vote Now →
                    </a>
                </div>
            </div>
        @empty
            <div class="text-center text-muted py-4">No elections available right now.</div>
        @endforelse

        <!-- Already Voted -->
        @if($votedSessions->count() > 0)
            <h5 class="mb-3 mt-4"><i class="bi bi-check-circle text-success"></i> Already Voted</h5>
            @foreach($votedSessions as $session)
                <div class="card election-card shadow-sm mb-3 border-success border-opacity-25">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">{{ $session->title }}</h6>
                            <span class="badge bg-success">Voted</span>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</body>
</html>
```

### 9.7 Ballot Page
**File:** `resources/views/student/ballot.blade.php`

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>VoteCast — Ballot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f4ff; font-family: 'Segoe UI', sans-serif; }
        .ballot-header { background: #1a56db; color: #fff; padding: 1.5rem 2rem; }
        .position-card { border-radius: 12px; border: none; margin-bottom: 1.5rem; }
        .candidate-option {
            border: 2px solid #e5e7eb; border-radius: 10px;
            padding: 1rem; cursor: pointer; transition: all 0.2s;
            display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;
        }
        .candidate-option:has(input:checked) { border-color: #1a56db; background: #eff6ff; }
        .candidate-option input { display: none; }
        .candidate-avatar {
            width: 56px; height: 56px; border-radius: 50%;
            object-fit: cover; background: #e5e7eb;
        }
        .progress-bar-custom { height: 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="ballot-header">
        <div class="container">
            <h4 class="mb-1">{{ $votingSession->title }}</h4>
            <small class="opacity-75">Select one candidate per position</small>
        </div>
    </div>

    <div class="container py-4">
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('student.vote', $votingSession) }}" id="ballotForm">
            @csrf

            @if($votingSession->requires_release_code)
            <div class="card position-card shadow-sm mb-4">
                <div class="card-body">
                    <label class="form-label fw-bold">Release Code</label>
                    <input type="text" name="release_code" class="form-control" required
                           placeholder="Enter your release code">
                    @error('release_code')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            @endif

            @foreach($votingSession->positions as $position)
            <div class="card position-card shadow-sm">
                <div class="card-header bg-white">
                    <strong>{{ $position->title }}</strong>
                </div>
                <div class="card-body">
                    @foreach($position->candidates as $candidate)
                    <label class="candidate-option w-100">
                        <input type="radio" name="votes[{{ $position->id }}]"
                               value="{{ $candidate->id }}" required>
                        <img src="{{ $candidate->photo_url }}" class="candidate-avatar" alt="">
                        <div>
                            <div class="fw-bold">{{ $candidate->student->full_name }}</div>
                            <div class="text-muted small">{{ $candidate->student->section }}</div>
                            @if($candidate->manifesto)
                                <div class="text-muted small mt-1">{{ Str::limit($candidate->manifesto, 80) }}</div>
                            @endif
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>
            @endforeach

            <div class="d-flex gap-3 mt-4">
                <a href="{{ route('student.dashboard') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary flex-grow-1 py-3 fs-5 fw-bold">
                    Submit My Votes →
                </button>
            </div>
        </form>
    </div>
</body>
</html>
```

### 9.8 Confirmation Page
**File:** `resources/views/student/confirmation.blade.php`

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>VoteCast — Vote Confirmed!</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f4ff; display:flex; align-items:center; min-height:100vh; font-family:'Segoe UI',sans-serif; }
        .receipt-card { background:#fff; border-radius:16px; padding:2.5rem; max-width:560px; width:100%; }
        .receipt-id { font-family:monospace; font-size:1.5rem; font-weight:700; color:#1a56db;
                      background:#eff6ff; padding:0.75rem 1.5rem; border-radius:8px; letter-spacing:2px; }
        .vote-item { padding:0.75rem; border-bottom:1px solid #f3f4f6; }
        .checkmark { font-size:3rem; }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center">
        <div class="receipt-card shadow-sm">
            <div class="text-center mb-4">
                <div class="checkmark">✅</div>
                <h3 class="fw-bold mt-2">Vote Submitted!</h3>
                <p class="text-muted">Your votes have been recorded.</p>
                <div class="receipt-id">{{ $receiptId }}</div>
                <div class="text-muted small mt-1">Receipt ID — save this for your records</div>
            </div>

            <h6 class="text-muted mb-3">YOUR VOTES</h6>
            @foreach($votes as $vote)
            <div class="vote-item d-flex justify-content-between">
                <span class="text-muted">{{ $vote->position->title }}</span>
                <span class="fw-bold">{{ $vote->candidate->student->full_name }}</span>
            </div>
            @endforeach

            <div class="d-grid gap-2 mt-4">
                <button onclick="window.print()" class="btn btn-outline-primary">🖨 Print Receipt</button>
                <a href="{{ route('student.dashboard') }}" class="btn btn-primary">Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>
```

---

## PHASE 10: STORAGE SETUP

```bash
# Link storage for candidate photos
php artisan storage:link

# Create default avatar placeholder
mkdir -p public/images
# Drop a default-avatar.png in public/images/
```

---

## FINAL SETUP & RUN

```bash
# Run all migrations
php artisan migrate

# Seed admin accounts
php artisan db:seed

# Clear and cache config
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Start the development server
php artisan serve
```

---

## ACCESS POINTS

| URL | Description |
|-----|-------------|
| `http://localhost:8000` | Student voting portal |
| `http://localhost:8000/admin/login` | Admin login |

**Admin credentials:**
- Super Admin: `admin@votecast.edu` / `admin123`
- Election Admin: `election@votecast.edu` / `election123`

---

## WORKFLOW SUMMARY

```
1. Admin logs in → /admin/login
2. Upload enrollment Excel → /admin/enrollment (upload 25-1.xlsx)
3. Create an election → /admin/sessions/create
4. Add positions (President, VP, etc.)
5. Add candidates to each position
6. Set status to "Active"
7. Student enters ID at / → sees ballot → votes → gets receipt
8. Admin views results at /admin/sessions/{id}/results
```

---

## TROUBLESHOOTING

**Excel not importing?**
- Check that `maatwebsite/excel` is installed: `composer show maatwebsite/excel`
- Verify the file is `.xlsx` (not `.xls` or `.csv`)
- The importer skips rows 1–8 (school name, period, course, blank, headers) and reads from row 9

**Student can't log in?**
- Must have an enrollment record in the active semester
- Check `session('current_semester')` matches your upload's semester value

**Photos not showing?**
- Run `php artisan storage:link`
- Ensure `public/images/default-avatar.png` exists

**Votes not saving?**
- Unique constraint on `voter_id + position_id` — student already voted in that position
- Check if `allow_vote_changes` is enabled on the session
