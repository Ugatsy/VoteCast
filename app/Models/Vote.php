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
}
