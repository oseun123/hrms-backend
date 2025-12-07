<?php

namespace App\Services;

use App\Services\Upload\UploadDriverInterface;
use App\Services\Upload\LocalUploadDriver;
use App\Services\Upload\CloudinaryUploadDriver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

class FileUploadService
{
    protected $driver;

    public function __construct()
    {
        $this->driver = $this->resolveDriver();
    }

    /**
     * Upload a file
     *
     * @param UploadedFile $file
     * @param string $folder
     * @param array $options
     * @return array
     * @throws \Exception
     */
    public function upload(UploadedFile $file, string $folder, array $options = []): array
    {
        // Validate file if rules provided
        if (isset($options['validation'])) {
            $this->validateFile($file, $options['validation']);
        }

        return $this->driver->upload($file, $folder, $options);
    }

    /**
     * Delete a file
     *
     * @param string $path
     * @param string|null $driver Override driver (for deleting old files)
     * @return bool
     */
    public function delete(string $path, ?string $driver = null): bool
    {
        $uploadDriver = $driver ? $this->resolveDriver($driver) : $this->driver;
        return $uploadDriver->delete($path);
    }

    /**
     * Get public URL for a file
     *
     * @param string $path
     * @param string|null $driver Override driver
     * @return string
     */
    public function getUrl(string $path, ?string $driver = null): string
    {
        $uploadDriver = $driver ? $this->resolveDriver($driver) : $this->driver;
        return $uploadDriver->getUrl($path);
    }

    /**
     * Check if file exists
     *
     * @param string $path
     * @param string|null $driver Override driver
     * @return bool
     */
    public function exists(string $path, ?string $driver = null): bool
    {
        $uploadDriver = $driver ? $this->resolveDriver($driver) : $this->driver;
        return $uploadDriver->exists($path);
    }

    /**
     * Validate uploaded file
     *
     * @param UploadedFile $file
     * @param array $rules
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validateFile(UploadedFile $file, array $rules): void
    {
        $validator = Validator::make(
            ['file' => $file],
            ['file' => $rules]
        );

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }

    /**
     * Get current driver name
     *
     * @return string
     */
    public function getDriverName(): string
    {
        return config('filesystems.upload_driver', 'local');
    }

    /**
     * Resolve the upload driver based on config
     *
     * @param string|null $driverName
     * @return UploadDriverInterface
     */
    protected function resolveDriver(?string $driverName = null): UploadDriverInterface
    {
        $driver = $driverName ?? config('filesystems.upload_driver', 'local');

        switch ($driver) {
            case 'cloudinary':
                return new CloudinaryUploadDriver();
            case 'local':
            default:
                return new LocalUploadDriver();
        }
    }
}
