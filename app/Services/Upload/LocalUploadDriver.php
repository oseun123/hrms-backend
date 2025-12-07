<?php

namespace App\Services\Upload;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LocalUploadDriver implements UploadDriverInterface
{
    /**
     * Upload a file to local storage
     */
    public function upload($file, string $folder, array $options = []): array
    {
        // Generate unique filename
        $filename = $this->generateFilename($file, $options);

        // Store file
        $path = $file->storeAs($folder, $filename, 'public');

        return [
            'url' => Storage::disk('public')->url($path),
            'path' => $path,
            'metadata' => [
                'driver' => 'local',
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
            ],
        ];
    }

    /**
     * Delete a file from local storage
     */
    public function delete(string $path): bool
    {
        return Storage::disk('public')->delete($path);
    }

    /**
     * Get public URL for a file
     */
    public function getUrl(string $path): string
    {
        return Storage::disk('public')->url($path);
    }

    /**
     * Check if file exists
     */
    public function exists(string $path): bool
    {
        return Storage::disk('public')->exists($path);
    }

    /**
     * Generate unique filename
     */
    protected function generateFilename($file, array $options): string
    {
        $extension = $file->getClientOriginalExtension();
        $basename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        // Sanitize filename
        $basename = Str::slug($basename);

        // Add timestamp for uniqueness
        $timestamp = time();

        return "{$basename}_{$timestamp}.{$extension}";
    }
}
