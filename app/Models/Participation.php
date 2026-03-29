<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participation extends Model
{
    protected $fillable = [
        'voting_session_id', 'user_id', 'receipt_id', 'has_votes', 'voted_at'
    ];

    protected $casts = [
        'has_votes' => 'boolean',
        'voted_at' => 'datetime',
    ];

    public function votingSession()
    {
        return $this->belongsTo(VotingSession::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
