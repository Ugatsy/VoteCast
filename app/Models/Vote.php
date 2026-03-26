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

    protected $casts = ['created_at' => 'datetime'];

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
}
