<?php

namespace App\Applications\Helpers;

use App\Repositories\MediaHelperRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class MediaHelper implements MediaHelperRepositoryInterface
{
    protected ImageManager $manager;
    protected string $disk;
    public function __construct()
    {
        // GD driver (safe for almost all servers)
        $this->manager = new ImageManager(new Driver());
        $this->disk = config('filesystems.default');
    }

    /* ================= UPLOAD ================= */

    /**
     * Upload single image
     * Returns ONLY relative path (no domain)
     *
     * Available disks (configured in config/filesystems.php):
     * - 'public' (default): storage/app/public - publicly accessible via /storage URL
     * - 'local': storage/app/private - private, not publicly accessible
     * - 's3': AWS S3 cloud storage (requires AWS credentials in .env)
     * - 'r2': Cloudflare R2 storage (configured, uses https://media.agamirsomoy.com)
     * - 'gcs': Google Cloud Storage (requires Google credentials in .env)
     *
     * @param UploadedFile $file The uploaded file
     * @param string $path The directory path within the disk
     * @param string $disk The storage disk to use (default: 'public')
     * @param bool $watermark Whether to apply watermark (default: false)
     * @return string Relative path to the uploaded file
     */
    public function upload(
        UploadedFile $file,
        string $path,
        string $disk = null,
        bool $watermark = false
    ): string {
        $disk = $disk ?? $this->disk;


        $filename = Str::uuid()->toString() . '.webp';
        // Read image
        $image = $this->manager->read($file->getPathname());

        // Smart resize (max 2000px, aspect ratio safe)
        if ($image->width() > 2000) {
            $image->scaleDown(2000);
        }

        // Optional watermark
        if ($watermark) {
            $this->applyWatermark($image);
        }


        $image
            ->sharpen(5);


        // Save to disk (local / s3 / others)
        Storage::disk($disk)->put(
            "{$path}/{$filename}",
//            $image->encode()->toString(),
            $image->encode(new WebpEncoder(
                quality: 95,
            ))->toString(),

            [
                'visibility'   => 'public',
                'CacheControl' => 'public, max-age=31536000, immutable',
            ]
        );

        // ✅ Return RELATIVE PATH only
        return "{$path}/{$filename}";
    }

    /**
     * Upload multiple images
     */
    public function uploadMultiple(
        array $files,
        string $path,
        string $disk = null,
        bool $watermark = false
    ): array {

        $disk = $disk ?? $this->disk;
        $paths = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $paths[] = $this->upload($file, $path, $disk, $watermark);
            }
        }

        return $paths;
    }

    /* ================= GET ================= */

    /**
     * Get first image URL from a path
     */
    public function get(string $path, string $disk = null): ?string
    {
        $disk = $disk ?? $this->disk;
        $all = $this->all($path, $disk);
        return $all[0] ?? null;
    }

    /**
     * Get all image URLs from a path
     */
    public function all(string $path, string $disk = null): array
    {
        $disk = $disk ?? $this->disk;
        if (!Storage::disk($disk)->exists($path)) {
            return [];
        }

        return collect(Storage::disk($disk)->files($path))
            ->map(fn ($file) => Storage::disk($disk)->url($file))
            ->toArray();
    }

    /* ================= DELETE ================= */

    /**
     * Delete full directory
     */
    public function delete(string $path, string $disk = null): bool
    {
        $disk = $disk ?? $this->disk;
        if (!Storage::disk($disk)->exists($path)) {
            return false;
        }

        Storage::disk($disk)->deleteDirectory($path);
        return true;
    }

    /**
     * Delete single file
     */
    public function deleteFile(string $filePath, string $disk = null): bool
    {
        $disk = $disk ?? $this->disk;
        if (!Storage::disk($disk)->exists($filePath)) {
            return false;
        }

        Storage::disk($disk)->delete($filePath);
        return true;
    }

    /* ================= UTILITY ================= */

    public function exists(string $path, string $disk = null): bool
    {
        $disk = $disk ?? $this->disk;
        return Storage::disk($disk)->exists($path);
    }

    public function count(string $path, string $disk = null): int
    {
        $disk = $disk ?? $this->disk;
        if (!Storage::disk($disk)->exists($path)) {
            return 0;
        }

        return count(Storage::disk($disk)->files($path));
    }

    /**
     * Convert relative path to full URL (when needed)
     */
    public function url(string $path, string $disk = null): string
    {
        $disk = $disk ?? $this->disk;
        return Storage::disk($disk)->url($path);
    }

    /* ================= INTERNAL ================= */

    protected function uniqueName(UploadedFile $file): string
    {
        return Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
    }

    protected function applyWatermark($image): void
    {
        $watermarkPath = public_path('watermark.png');

        if (!file_exists($watermarkPath)) {
            return;
        }

        $watermark = $this->manager->read($watermarkPath);
        // OPTION 1: Fixed small width (e.g. 150px)
        $watermark->scale(width: 150);

        // OPTION 2 (Better): Image width এর 20% হিসেবে watermark
        // $watermark->scale(width: intval($image->width() * 0.20));

        $image->place(
            $watermark,
            'top-right',
            20,
            20,
            100, //opacity
        );
    }
}
