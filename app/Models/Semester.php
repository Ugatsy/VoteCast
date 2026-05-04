<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    use HasFactory;

    protected $table = 'semesters';

    protected $fillable = [
        'name',
        'academic_year',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];
    
    /**
     * Get the current active semester
     */
    public static function getCurrent(): ?self
    {
        return static::where('is_active', true)->first();
    }
    
    /**
     * Check if a student is enrolled in this semester
     */
    public function hasStudent(string $studentCode): bool
    {
        return Enrollment::where('semester', $this->name)
            ->where('academic_year', $this->academic_year)
            ->where('student_code', $studentCode)
            ->exists();
    }
    
    /**
     * Get all enrollments for this semester
     */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'semester', 'name')
            ->where('academic_year', $this->academic_year);
    }
}