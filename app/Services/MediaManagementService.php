<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class MediaManagementService
{
    public static function uploadMedia($file, $path, $disk, $fileName)
    {
        try {
            return Storage::disk($disk)->putFileAs($path, $file, $fileName);
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    public static function removeMedia($path)
    {
        $fullPath = storage_path('app/public/' . $path);
        unlink($fullPath);
    }

    public static function mediaExist($path): bool
    {
        if ($path)
            return Storage::exists($path);
        else
            return false;
    }

    public static function checkDeleteUpload($imagePath, $file, $path, $disk, $fileName)
    {
        if (self::mediaExist($imagePath)) {
            self::removeMedia($imagePath);
        }

        return self::uploadMedia($file, $path, $disk, $fileName);
    }
}
