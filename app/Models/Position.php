<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    protected $fillable = [
        'voting_session_id', 'title', 'description',
        'max_winners', 'display_order',
    ];

    protected $casts = [
        'max_winners' => 'integer',
        'display_order' => 'integer',
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

    /**
     * Get total votes for this position
     */
    public function getTotalVotesAttribute(): int
    {
        return $this->votes()->count();
    }

    /**
     * Get candidates with their vote counts
     */
    public function getCandidatesWithVotesAttribute()
    {
        return $this->candidates()->withCount('votes')->get();
    }

    /**
     * Check if position has winners
     */
    public function hasWinners(): bool
    {
        return $this->candidates()->where('is_approved', true)->count() > 0;
    }

    /**
     * Get winners for this position (based on max_winners)
     */
    public function getWinnersAttribute()
    {
        return $this->candidates()
            ->withCount('votes')
            ->orderBy('votes_count', 'desc')
            ->take($this->max_winners)
            ->get();
    }

    /**
     * Get vote statistics for this position
     */
    public function getStatisticsAttribute(): array
    {
        $candidates = $this->candidates()->withCount('votes')->get();
        $totalVotes = $candidates->sum('votes_count');

        return [
            'total_votes' => $totalVotes,
            'candidates' => $candidates->map(function ($candidate) use ($totalVotes) {
                return [
                    'id' => $candidate->id,
                    'name' => $candidate->full_name,
                    'vote_count' => $candidate->votes_count,
                    'percentage' => $totalVotes > 0
                        ? round(($candidate->votes_count / $totalVotes) * 100, 2)
                        : 0,
                ];
            }),
        ];
    }
}
