<?php

namespace App\Support;

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
        $this->manager = new ImageManager(new Driver);
        $this->disk = config('filesystems.default');
    }

    public function upload(
        UploadedFile $file,
        string $path,
        ?string $disk = null,
        bool $watermark = false
    ): string {
        $disk ??= $this->disk;

        $filename = Str::uuid()->toString().'.webp';
        $image = $this->manager->read($file->getPathname());

        if ($image->width() > 2000) {
            $image->scaleDown(2000);
        }

        $image->sharpen(5);

        Storage::disk($disk)->put(
            "{$path}/{$filename}",
            $image->encode(new WebpEncoder(quality: 95))->toString(),
            [
                'visibility' => 'public',
                'CacheControl' => 'public, max-age=31536000, immutable',
            ]
        );

        return "{$path}/{$filename}";
    }

    public function url(string $path, ?string $disk = null): string
    {
        $disk ??= $this->disk;

        return Storage::disk($disk)->url($path);
    }
}
