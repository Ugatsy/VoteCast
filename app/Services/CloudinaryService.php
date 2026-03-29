<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class CloudinaryService
{
    protected $cloudinary;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => config('cloudinary.cloud_name'),
                'api_key'    => config('cloudinary.api_key'),
                'api_secret' => config('cloudinary.api_secret'),
            ],
        ]);
    }

    /**
     * Upload photo optimized for card display
     * Creates a 400x400 square image (good for profile and candidate cards)
     */
    public function uploadPhoto(UploadedFile $file, $folder = 'student_photos', $publicId = null)
    {
        try {
            $uploadOptions = [
                'folder' => $folder,
                'transformation' => [
                    [
                        'width' => 400,
                        'height' => 400,
                        'crop' => 'fill',      // Crops to exact square
                        'gravity' => 'face',   // Focus on face if detected
                        'quality' => 'auto',   // Automatic quality optimization
                        'fetch_format' => 'auto' // Automatic format (WebP when supported)
                    ]
                ]
            ];

            if ($publicId) {
                $uploadOptions['public_id'] = $publicId;
            }

            $result = $this->cloudinary->uploadApi()->upload($file->getRealPath(), $uploadOptions);

            return [
                'success' => true,
                'url' => $result['secure_url'],
                'public_id' => $result['public_id'],
            ];
        } catch (\Exception $e) {
            Log::error('Cloudinary upload failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete photo from Cloudinary
     */
    public function deletePhoto($publicId)
    {
        try {
            if ($publicId) {
                $this->cloudinary->uploadApi()->destroy($publicId);
            }
            return ['success' => true];
        } catch (\Exception $e) {
            Log::error('Cloudinary delete failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get optimized URL for card display
     * Returns a 200x200 square image (good for candidate cards in ballot)
     */
    public function getCardPhotoUrl($publicId, $width = 200, $height = 200)
    {
        return $this->cloudinary->image($publicId)
            ->resize('fill', $width, $height)
            ->gravity('face')
            ->quality('auto')
            ->format('auto')
            ->toUrl();
    }

    /**
     * Get optimized URL for profile display (larger)
     */
    public function getProfilePhotoUrl($publicId, $width = 400, $height = 400)
    {
        return $this->cloudinary->image($publicId)
            ->resize('fill', $width, $height)
            ->gravity('face')
            ->quality('auto')
            ->format('auto')
            ->toUrl();
    }
}
