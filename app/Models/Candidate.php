<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    protected $fillable = [
        'position_id', 'student_id', 'manifesto', 'photo', 'is_approved',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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
     */
    public function getPhotoUrlAttribute(): string
    {
        if ($this->photo) {
            if (filter_var($this->photo, FILTER_VALIDATE_URL)) {
                return $this->photo;
            }
            return asset('storage/' . $this->photo);
        }

        $name = $this->student->full_name ?? 'Candidate';
        $initials = $this->getInitials($name);
        return "https://ui-avatars.com/api/?name=" . urlencode($initials) . "&background=1a56db&color=fff&size=40";
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
        if (empty($this->manifesto)) {
            return 'No manifesto provided.';
        }

        return strlen($this->manifesto) > 150
            ? substr($this->manifesto, 0, 150) . '...'
            : $this->manifesto;
    }

    /**
     * Get candidate's full name
     */
    public function getFullNameAttribute(): string
    {
        return $this->student->full_name ?? 'Unknown Candidate';
    }

    /**
     * Get candidate's section
     */
    public function getSectionAttribute(): string
    {
        return $this->student->section ?? 'N/A';
    }

    /**
     * Get candidate's year level
     */
    public function getYearLevelAttribute(): int
    {
        return $this->student->year_level ?? 0;
    }

    /**
     * Get candidate's department/course
     */
    public function getDepartmentAttribute(): string
    {
        return $this->student->department ?? 'N/A';
    }
}
