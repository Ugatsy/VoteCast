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

    // ── Computed attributes ───────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        $middle = $this->middle_name ? ' ' . $this->middle_name : '';
        return "{$this->first_name}{$middle} {$this->last_name}";
    }
}
