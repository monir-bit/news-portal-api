<?php

namespace App\Repositories;

use Illuminate\Http\UploadedFile;

interface MediaHelperRepositoryInterface
{
    public function upload(
        UploadedFile $file,
        string $path,
        ?string $disk = null,
        bool $watermark = false
    ): string;

    /**
     * Convert stored relative path to a public URL.
     */
    public function url(string $path, ?string $disk = null): string;
}
