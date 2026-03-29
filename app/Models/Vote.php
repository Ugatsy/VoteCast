<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    // Enable timestamps since the table has created_at
    // Note: The table only has created_at, not updated_at
    // So we need to disable updated_at

    public $timestamps = true;
    const UPDATED_AT = null; // Disable updated_at since it doesn't exist

    protected $fillable = [
        'voting_session_id', 'position_id', 'candidate_id',
        'voter_id', 'receipt_id', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

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

    /**
     * Scope to get votes for a specific session
     */
    public function scopeForSession($query, $sessionId)
    {
        return $query->where('voting_session_id', $sessionId);
    }

    /**
     * Scope to get votes for a specific position
     */
    public function scopeForPosition($query, $positionId)
    {
        return $query->where('position_id', $positionId);
    }

    /**
     * Scope to get votes for a specific candidate
     */
    public function scopeForCandidate($query, $candidateId)
    {
        return $query->where('candidate_id', $candidateId);
    }

    /**
     * Scope to get votes for a specific voter
     */
    public function scopeForVoter($query, $voterId)
    {
        return $query->where('voter_id', $voterId);
    }

    /**
     * Get vote count for a specific candidate
     */
    public static function getCandidateVoteCount($candidateId): int
    {
        return self::where('candidate_id', $candidateId)->count();
    }

    /**
     * Get all votes for a session with relationships
     */
    public static function getSessionVotes($sessionId)
    {
        return self::with(['candidate', 'voter', 'position'])
            ->where('voting_session_id', $sessionId)
            ->get()
            ->groupBy('position_id');
    }

    /**
     * Check if a voter has already voted in a session
     */
    public static function hasVoted($sessionId, $voterId): bool
    {
        return self::where('voting_session_id', $sessionId)
            ->where('voter_id', $voterId)
            ->exists();
    }

    /**
     * Get voter's votes for a specific session
     */
    public static function getVoterVotes($sessionId, $voterId)
    {
        return self::with(['candidate', 'position'])
            ->where('voting_session_id', $sessionId)
            ->where('voter_id', $voterId)
            ->get();
    }

    /**
     * Get receipt data for a vote
     */
    public function getReceiptDataAttribute(): array
    {
        return [
            'receipt_id' => $this->receipt_id,
            'voter_name' => $this->voter->full_name ?? 'Unknown',
            'candidate_name' => $this->candidate->full_name ?? 'Unknown',
            'position_title' => $this->position->title ?? 'Unknown',
            'voted_at' => $this->created_at,
            'ip_address' => $this->ip_address,
        ];
    }
}
