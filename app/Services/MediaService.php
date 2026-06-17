<?php

namespace App\Services;

use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

class MediaService
{
    private array $thumbnailSizes = [
        'thumbnail' => [150, 150],
        'medium'    => [300, 300],
        'large'     => [1024, 1024],
    ];

    public function upload(UploadedFile $file, User $user, string $disk = 'public'): Media
    {
        $filename  = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $directory = 'uploads/' . date('Y/m');
        $path      = $file->storeAs($directory, $filename, $disk);
        $url       = Storage::disk($disk)->url($path);
        $thumbnails = [];

        if (str_starts_with($file->getMimeType(), 'image/')) {
            $thumbnails = $this->generateThumbnails($file, $directory, $disk);
        }

        return Media::create([
            'user_id'       => $user->id,
            'filename'      => $filename,
            'original_name' => $file->getClientOriginalName(),
            'mime_type'     => $file->getMimeType(),
            'size'          => $file->getSize(),
            'disk'          => $disk,
            'path'          => $path,
            'url'           => $url,
            'thumbnails'    => $thumbnails,
        ]);
    }

    public function delete(Media $media): void
    {
        Storage::disk($media->disk)->delete($media->path);

        foreach ($media->thumbnails ?? [] as $thumbPath) {
            Storage::disk($media->disk)->delete($thumbPath);
        }

        $media->delete();
    }

    private function generateThumbnails(UploadedFile $file, string $dir, string $disk): array
    {
        $thumbnails = [];
        $manager = ImageManager::gd();

        foreach ($this->thumbnailSizes as $name => [$w, $h]) {
            $thumbName = Str::uuid() . '_' . $name . '.' . $file->getClientOriginalExtension();
            $thumbPath = $dir . '/' . $thumbName;

            $image = $manager->read($file->getRealPath())
                ->cover($w, $h);

            Storage::disk($disk)->put($thumbPath, $image->toJpeg(85));
            $thumbnails[$name] = Storage::disk($disk)->url($thumbPath);
        }

        return $thumbnails;
    }
}
