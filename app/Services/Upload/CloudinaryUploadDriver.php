<?php

namespace App\Services\Upload;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CloudinaryUploadDriver implements UploadDriverInterface
{
    /**
     * Upload a file to Cloudinary
     */
    public function upload($file, string $folder, array $options = []): array
    {
        // Generate unique public ID
        $publicId = $this->generatePublicId($file, $folder, $options);

        // Upload to Cloudinary
        $uploadedFile = Cloudinary::upload($file->getRealPath(), [
            'folder' => $folder,
            'public_id' => $publicId,
            'resource_type' => 'auto',
        ]);

        return [
            'url' => $uploadedFile->getSecurePath(),
            'path' => $uploadedFile->getPublicId(),
            'metadata' => [
                'driver' => 'cloudinary',
                'public_id' => $uploadedFile->getPublicId(),
                'original_name' => $file->getClientOriginalName(),
                'size' => $uploadedFile->getSize(),
                'mime_type' => $file->getMimeType(),
                'extension' => $uploadedFile->getExtension(),
                'width' => $uploadedFile->getWidth(),
                'height' => $uploadedFile->getHeight(),
                'format' => $uploadedFile->getFileType(),
            ],
        ];
    }

    /**
     * Delete a file from Cloudinary
     */
    public function delete(string $path): bool
    {
        try {
            Cloudinary::destroy($path);

            return true;
        } catch (\Exception $e) {
            Log::error('Cloudinary delete failed: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Get public URL for a file
     */
    public function getUrl(string $path): string
    {
        return Cloudinary::getUrl($path);
    }

    /**
     * Check if file exists (Cloudinary doesn't have a direct exists method)
     */
    public function exists(string $path): bool
    {
        try {
            // Try to get the resource info
            $result = Cloudinary::getResource($path);

            return ! empty($result);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generate unique public ID for Cloudinary
     */
    protected function generatePublicId($file, string $folder, array $options): string
    {
        $basename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        // Sanitize filename
        $basename = Str::slug($basename);

        // Add timestamp for uniqueness
        $timestamp = time();

        // Add employee ID if provided
        if (isset($options['employee_id'])) {
            return "{$basename}_emp{$options['employee_id']}_{$timestamp}";
        }

        return "{$basename}_{$timestamp}";
    }
}
