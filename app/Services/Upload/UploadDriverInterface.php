<?php

namespace App\Services\Upload;

interface UploadDriverInterface
{
    /**
     * Upload a file
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $folder
     * @param array $options
     * @return array ['url' => string, 'path' => string, 'metadata' => array]
     */
    public function upload($file, string $folder, array $options = []): array;

    /**
     * Delete a file
     *
     * @param string $path
     * @return bool
     */
    public function delete(string $path): bool;

    /**
     * Get public URL for a file
     *
     * @param string $path
     * @return string
     */
    public function getUrl(string $path): string;

    /**
     * Check if file exists
     *
     * @param string $path
     * @return bool
     */
    public function exists(string $path): bool;
}
