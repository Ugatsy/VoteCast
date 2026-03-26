<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

    /**
     * Generate a random release code
     */
    public static function generateCode($length = 8): string
    {
        return strtoupper(Str::random($length));
    }

    /**
     * Create a new release code for a session
     */
    public static function createForSession($sessionId, $description = null, $expiresInDays = null): self
    {
        $code = self::generateCode();

        return self::create([
            'voting_session_id' => $sessionId,
            'code' => $code,
            'description' => $description,
            'expires_at' => $expiresInDays ? now()->addDays($expiresInDays) : null,
            'is_active' => true,
        ]);
    }

    /**
     * Scope to get active codes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Mark code as used/inactive
     */
    public function markAsUsed(): void
    {
        $this->update(['is_active' => false]);
    }
}
