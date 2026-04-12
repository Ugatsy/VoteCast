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
     * Get photo URL for card/ballot display.
     *
     * Priority:
     *  1. Candidate-specific photo (admin-uploaded)
     *  2. Student profile photo (uploaded by student via profile page)
     *  3. Generated initials avatar
     *
     * Cloudinary URLs are built from photo_public_id so transforms sit in
     * the URL path — Cloudinary ignores query-string params like ?w=200.
     */
    public function getPhotoUrlAttribute(): string
    {
        // 1. Candidate has their own photo (admin-uploaded)
        if ($this->photo) {
            if (filter_var($this->photo, FILTER_VALIDATE_URL)) {
                return $this->photo; // already a full URL
            }
            return asset('storage/' . $this->photo); // local storage path
        }

        // 2. Fall back to student's profile photo
        if ($this->student && $this->student->photo_public_id) {
            // Build URL from public_id — avoids the stored URL's version segment
            // (/vXXXXXX/) which breaks path-injection-based transform approaches.
            return $this->buildCloudinaryUrl($this->student->photo_public_id, 200);
        }

        if ($this->student && $this->student->photo) {
            // Legacy / non-Cloudinary fallback: just use the stored URL directly
            return $this->student->photo;
        }

        // 3. Generated initials avatar
        $name     = $this->student ? $this->student->full_name : 'Candidate';
        $initials = $this->getInitials($name);
        return "https://ui-avatars.com/api/?name=" . urlencode($initials) . "&background=1a56db&color=fff&size=200";
    }

    /**
     * Build a Cloudinary delivery URL with face-crop transforms in the path.
     * Format: /image/upload/{transforms}/{public_id}
     * Version number and file extension are optional — Cloudinary handles both.
     */
    private function buildCloudinaryUrl(string $publicId, int $size): string
    {
        $cloudName = config('cloudinary.cloud_name');
        $transform = "c_fill,g_face,h_{$size},q_auto,f_auto,w_{$size}";
        return "https://res.cloudinary.com/{$cloudName}/image/upload/{$transform}/{$publicId}";
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
