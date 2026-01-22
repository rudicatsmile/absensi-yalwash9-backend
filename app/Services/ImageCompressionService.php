<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageCompressionService
{
    protected $manager;

    public function __construct()
    {
        // Initialize Intervention Image Manager with GD driver
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Compress and save the uploaded image.
     *
     * @param UploadedFile $file The uploaded file instance
     * @param string $directory The directory to store the file in
     * @param int $quality Compression quality (0-100), default 75
     * @param int $maxWidth Maximum width in pixels, default 1280
     * @return string The path to the stored file
     */
    public function compressAndSave(UploadedFile $file, string $directory, int $quality = 75, int $maxWidth = 1280): string
    {
        try {
            // Check if file is an image
            if (!str_starts_with($file->getMimeType(), 'image/')) {
                return $file->store($directory, 'public');
            }

            // Read image
            $image = $this->manager->read($file);

            // Resize if width is larger than maxWidth
            if ($image->width() > $maxWidth) {
                $image->scale(width: $maxWidth);
            }

            // Determine output format and compression based on extension
            $extension = strtolower($file->getClientOriginalExtension());
            $filename = $file->hashName(); // Generates random name with extension

            // Apply compression based on format
            if (in_array($extension, ['jpg', 'jpeg'])) {
                $encoded = $image->toJpeg(quality: $quality);
            } elseif ($extension === 'webp') {
                $encoded = $image->toWebp(quality: $quality);
            } elseif ($extension === 'png') {
                // PNG doesn't support quality parameter in toPng() in the same way as JPEG
                // Resizing already provides significant reduction.
                // We keep it as PNG to preserve transparency.
                $encoded = $image->toPng();
            } else {
                // Fallback for other image formats: convert to JPEG for better compatibility and compression
                $encoded = $image->toJpeg(quality: $quality);
                $filename = pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
            }

            $path = $directory . '/' . $filename;
            
            // Save the encoded image to storage
            Storage::disk('public')->put($path, $encoded);

            return $path;

        } catch (\Exception $e) {
            Log::error('Image compression failed: ' . $e->getMessage());
            // Fallback: store original file if compression fails
            return $file->store($directory, 'public');
        }
    }
}
