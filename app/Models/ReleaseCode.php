<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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

    public static function generateCode($length = 6): string
    {
        return strtoupper(Str::random($length));
    }

    public static function createForSession($sessionId, $description = null, $expiresAt = null): self
    {
        $code = self::generateCode();

        return self::create([
            'voting_session_id' => $sessionId,
            'code' => $code,
            'description' => $description,
            'expires_at' => $expiresAt,
            'is_active' => true,
        ]);
    }

    public function getQRCodeBase64Attribute(): string
    {
        $qrCode = QrCode::format('png')
            ->size(200)
            ->errorCorrection('H')
            ->generate($this->code);

        return 'data:image/png;base64,' . base64_encode($qrCode);
    }

    public static function verifyCode($sessionId, $code): bool
    {
        $releaseCode = self::where('voting_session_id', $sessionId)
            ->where('code', strtoupper(trim($code)))
            ->where('is_active', true)
            ->first();

        if (!$releaseCode) {
            return false;
        }

        return $releaseCode->isValid();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    public function markAsUsed(): void
    {
        $this->update(['is_active' => false]);
    }

}
