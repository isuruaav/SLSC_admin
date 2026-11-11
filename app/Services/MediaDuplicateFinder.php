<?php

namespace App\Services;

use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

final class MediaDuplicateFinder
{
    public function find(UploadedFile $file, string $visibility, User $actor): ?MediaAsset
    {
        if (! $file->isValid() || $file->getSize() > 50 * 1024 * 1024) {
            return null;
        }
        $checksum = hash_file('sha256', $file->getPathname());
        if (! is_string($checksum)) {
            return null;
        }
        $disk = $visibility === 'public' ? config('media.disks.public', 'public') : config('media.disks.private', 'local');
        $query = MediaAsset::query()->where('checksum', $checksum)->where('visibility', $visibility)->where('source', 'upload')->where('disk', $disk);
        if (! Gate::forUser($actor)->allows('media.view')) {
            $query->where('uploaded_by', $actor->id);
        }
        foreach ($query->latest('id')->get() as $media) {
            if (is_string($media->disk) && is_string($media->path) && Storage::disk($media->disk)->exists($media->path)) {
                return $media;
            }
        }

        return null;
    }
}
