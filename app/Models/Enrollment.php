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

    // ── Relationships ─────────────────────────────────────────────────────────

    public function uploadBatch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(UploadBatch::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id', 'student_code');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeCurrent($query)
    {
        $semester     = session('current_semester', '1st Semester');
        $academicYear = session('current_academic_year', date('Y') . '-' . (date('Y') + 1));

        return $query->where('semester', $semester)
                     ->where('academic_year', $academicYear)
                     ->where('is_active', true);
    }

    public function scopeByCourse($query, $course)
    {
        return $query->where('course', $course);
    }

    public function scopeByYearLevel($query, $yearLevel)
    {
        return $query->where('year_level', $yearLevel);
    }

    public function scopeBySection($query, $section)
    {
        return $query->where('section', $section);
    }

    // ── Computed attributes ───────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        $middle = $this->middle_name ? ' ' . $this->middle_name : '';
        return "{$this->first_name}{$middle} {$this->last_name}";
    }

    public function getFullNameReverseAttribute(): string
    {
        return "{$this->last_name}, {$this->first_name}" . ($this->middle_name ? " {$this->middle_name}" : '');
    }

    /**
     * Get formatted student code with course
     */
    public function getStudentIdentifierAttribute(): string
    {
        return "{$this->student_code} - {$this->course}";
    }
}
