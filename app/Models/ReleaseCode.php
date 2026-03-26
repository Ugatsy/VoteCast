<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReleaseCode extends Model
{
    protected $fillable = [
        'voting_session_id', 'code', 'description', 'is_active', 'expires_at',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function votingSession(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(VotingSession::class);
    }

    public function isValid(): bool
    {
        return $this->is_active && (!$this->expires_at || $this->expires_at > now());
    }
}
