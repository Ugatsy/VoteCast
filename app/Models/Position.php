<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    protected $fillable = [
        'voting_session_id', 'title', 'description',
        'max_winners', 'display_order',
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
}
