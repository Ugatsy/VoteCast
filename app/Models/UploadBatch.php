<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UploadBatch extends Model
{
    protected $fillable = [
        'filename', 'semester', 'academic_year',
        'total_records', 'imported_records', 'skipped_records',
        'statistics', 'errors', 'uploaded_by',
    ];

    protected $casts = [
        'statistics' => 'array',
        'errors'     => 'array',
    ];

    public function enrollments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function uploader(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
