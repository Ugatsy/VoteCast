<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    public $timestamps = true;
    const UPDATED_AT = null;

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

    public function scopeForSession($query, $sessionId)
    {
        return $query->where('voting_session_id', $sessionId);
    }

    public function scopeForPosition($query, $positionId)
    {
        return $query->where('position_id', $positionId);
    }

    public function scopeForCandidate($query, $candidateId)
    {
        return $query->where('candidate_id', $candidateId);
    }

    public function scopeForVoter($query, $voterId)
    {
        return $query->where('voter_id', $voterId);
    }

    public static function getCandidateVoteCount($candidateId): int
    {
        return self::where('candidate_id', $candidateId)->count();
    }

    public static function getSessionVotes($sessionId)
    {
        return self::with(['candidate', 'voter', 'position'])
            ->where('voting_session_id', $sessionId)
            ->get()
            ->groupBy('position_id');
    }

    public static function hasVoted($sessionId, $voterId): bool
    {
        return self::where('voting_session_id', $sessionId)
            ->where('voter_id', $voterId)
            ->exists();
    }

    public static function getVoterVotes($sessionId, $voterId)
    {
        return self::with(['candidate', 'position'])
            ->where('voting_session_id', $sessionId)
            ->where('voter_id', $voterId)
            ->get();
    }

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
