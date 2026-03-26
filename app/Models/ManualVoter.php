<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualVoter extends Model
{
    public $timestamps = false;

    protected $fillable = ['voting_session_id', 'user_id', 'added_by'];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function session(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(VotingSession::class, 'voting_session_id');
    }
}
