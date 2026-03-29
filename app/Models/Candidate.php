<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    protected $fillable = [
        'position_id', 'student_id', 'manifesto', 'photo', 'is_approved', 'display_order',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'display_order' => 'integer',
    ];

    public function position(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function student(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        // student_id references the users table's id column
        return $this->belongsTo(User::class, 'student_id');
    }

    public function votes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Vote::class);
    }

    /**
     * Get vote count for this candidate
     */
    public function getVoteCountAttribute(): int
    {
        return $this->votes()->count();
    }

    /**
     * Get vote percentage for this candidate within their position
     */
    public function getVotePercentageAttribute(): float
    {
        $positionTotalVotes = $this->position->candidates->sum(function ($candidate) {
            return $candidate->vote_count;
        });

        if ($positionTotalVotes === 0) {
            return 0;
        }

        return round(($this->vote_count / $positionTotalVotes) * 100, 2);
    }

    /**
     * Get formatted photo URL
     * Priority: 1. Candidate's uploaded photo, 2. Student's profile photo, 3. Default avatar
     */
/**
 * Get formatted photo URL for card display (200x200 optimized)
 */
public function getPhotoUrlAttribute(): string
{
    // First, check if candidate has a specific photo uploaded
    if ($this->photo) {
        if (filter_var($this->photo, FILTER_VALIDATE_URL)) {
            // If it's a Cloudinary URL, add transformations for card size
            if (strpos($this->photo, 'cloudinary') !== false) {
                return $this->photo . '?w=200&h=200&c=fill&g=face';
            }
            return $this->photo;
        }
        return asset('storage/' . $this->photo);
    }

    // Second, check if the student has a profile photo
    if ($this->student && $this->student->photo) {
        $photoUrl = $this->student->photo;
        if (strpos($photoUrl, 'cloudinary') !== false) {
            return $photoUrl . '?w=200&h=200&c=fill&g=face';
        }
        return $photoUrl;
    }

    // Third, generate default avatar from student's name
    $name = $this->student ? $this->student->full_name : 'Candidate';
    $initials = $this->getInitials($name);
    return "https://ui-avatars.com/api/?name=" . urlencode($initials) . "&background=1a56db&color=fff&size=200";
}

    private function getInitials(string $name): string
    {
        $words = explode(' ', $name);
        $initials = '';
        foreach (array_slice($words, 0, 2) as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }
        return $initials ?: '?';
    }

    /**
     * Scope for approved candidates only
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Check if candidate is approved
     */
    public function isApproved(): bool
    {
        return $this->is_approved;
    }

    /**
     * Get candidate's manifesto summary (shortened)
     */
    public function getManifestoSummaryAttribute(): string
    {
        if (!empty($this->manifesto)) {
            return strlen($this->manifesto) > 150
                ? substr($this->manifesto, 0, 150) . '...'
                : $this->manifesto;
        }

        // Check if student has personal manifesto
        if ($this->student && $this->student->manifesto) {
            $manifesto = $this->student->manifesto;
            return strlen($manifesto) > 150
                ? substr($manifesto, 0, 150) . '...'
                : $manifesto;
        }

        return 'No manifesto provided.';
    }

    /**
     * Get candidate's full manifesto (for detailed view)
     */
    public function getFullManifestoAttribute(): string
    {
        if (!empty($this->manifesto)) {
            return $this->manifesto;
        }

        if ($this->student && $this->student->manifesto) {
            return $this->student->manifesto;
        }

        return '';
    }

    /**
     * Get candidate's full platform
     */
    public function getPlatformAttribute(): string
    {
        return $this->student ? $this->student->platform : '';
    }

    /**
     * Get candidate's full name
     */
    public function getFullNameAttribute(): string
    {
        return $this->student ? $this->student->full_name : 'Unknown Candidate';
    }

    /**
     * Get candidate's section
     */
    public function getSectionAttribute(): string
    {
        return $this->student ? $this->student->section : 'N/A';
    }

    /**
     * Get candidate's year level
     */
    public function getYearLevelAttribute(): int
    {
        return $this->student ? ($this->student->year_level ?? 0) : 0;
    }

    /**
     * Get candidate's department/course
     */
    public function getDepartmentAttribute(): string
    {
        return $this->student ? ($this->student->department ?? 'N/A') : 'N/A';
    }

    /**
     * Get candidate's student ID number
     */
    public function getStudentIdNumberAttribute(): string
    {
        return $this->student ? ($this->student->student_id ?? 'N/A') : 'N/A';
    }

    /**
     * Check if candidate has a manifesto
     */
    public function hasManifesto(): bool
    {
        return !empty($this->manifesto) || ($this->student && !empty($this->student->manifesto));
    }
}
