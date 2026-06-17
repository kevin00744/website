<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MediaController extends Controller
{
    public function __construct(private MediaService $mediaService) {}

    public function index(Request $request)
    {
        $media = Media::with('uploader')
            ->when($request->search, fn($q) =>
                $q->where('original_name', 'like', "%{$request->search}%"))
            ->when($request->type, function ($q, $type) {
                $q->where('mime_type', 'like', $type === 'image' ? 'image/%' : 'application/%');
            })
            ->latest()
            ->paginate(24)
            ->withQueryString();

        return Inertia::render('Admin/Media/Index', ['media' => $media]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:65536|mimes:jpeg,png,gif,webp,pdf,zip,mp4',
        ]);

        $media = $this->mediaService->upload(
            $request->file('file'),
            $request->user()
        );

        return response()->json($media, 201);
    }

    public function update(Request $request, Media $media)
    {
        $request->validate([
            'alt'     => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:500',
        ]);

        $media->update($request->only('alt', 'caption'));

        return response()->json($media);
    }

    public function destroy(Media $media)
    {
        $this->mediaService->delete($media);

        return response()->json(['message' => '已刪除']);
    }
}
