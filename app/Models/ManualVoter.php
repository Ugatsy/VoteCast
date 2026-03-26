<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualVoter extends Model
{
    public $timestamps = false;

    protected $fillable = ['voting_session_id', 'user_id', 'added_by'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function session(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(VotingSession::class, 'voting_session_id');
    }

    public function addedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Scope to get manual voters for a specific session
     */
    public function scopeForSession($query, $sessionId)
    {
        return $query->where('voting_session_id', $sessionId);
    }

    /**
     * Check if a user is a manual voter for a session
     */
    public static function isManualVoter($sessionId, $userId): bool
    {
        return self::where('voting_session_id', $sessionId)
            ->where('user_id', $userId)
            ->exists();
    }
}
