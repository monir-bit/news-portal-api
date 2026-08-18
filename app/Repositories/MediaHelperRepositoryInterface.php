<?php

namespace App\Repositories;

use Illuminate\Http\UploadedFile;

interface MediaHelperRepositoryInterface
{
    /* ================= UPLOAD ================= */

    public function upload(
        UploadedFile $file,
        string $path,
        ?string $disk = null,
        bool $watermark = false
    ): string;

    public function uploadMultiple(
        array $files,
        string $path,
        ?string $disk = null,
        bool $watermark = false
    ): array;

    public function get(string $path, ?string $disk = null): ?string;

    public function all(string $path, ?string $disk = null): array;

    public function delete(string $path, ?string $disk = null): bool;

    public function deleteFile(string $filePath, ?string $disk = null): bool;

    public function exists(string $path, ?string $disk = null): bool;

    public function count(string $path, ?string $disk = null): int;

    /**
     * Convert stored relative path to a public URL.
     */
    public function url(string $path, ?string $disk = null): string;
}
