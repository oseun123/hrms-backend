<?php

namespace App\Services\Upload;

interface UploadDriverInterface
{
    /**
     * Upload a file
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return array ['url' => string, 'path' => string, 'metadata' => array]
     */
    public function upload($file, string $folder, array $options = []): array;

    /**
     * Delete a file
     */
    public function delete(string $path): bool;

    /**
     * Get public URL for a file
     */
    public function getUrl(string $path): string;

    /**
     * Check if file exists
     */
    public function exists(string $path): bool;
}
