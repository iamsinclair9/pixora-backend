<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Image;
use Illuminate\Http\Request;
use App\Jobs\AnalyzeImageJob;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function sas(Request $request)
    {
        $disk = config('filesystems.default');

        return response()->json([
            'disk'       => $disk,
            'upload_url' => '/api/v1/uploads/confirm',
            'bucket'     => config('filesystems.disks.s3.bucket'),
            'region'     => config('filesystems.disks.s3.region'),
            'expires_in' => 900
        ]);
    }

    public function suggest(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240',
        ]);

        $file = $request->file('image');
        $tempPath = $file->store('temp', 'public');
        $fullPath = storage_path('app/public/' . $tempPath);

        $gemini = new \App\Services\GeminiService();
        $suggestions = $gemini->suggestMetadata($fullPath);

        // Delete temp file after analysis
        Storage::disk('public')->delete($tempPath);

        return response()->json($suggestions);
    }

    public function confirm(Request $request)
    {
        $request->validate([
            'image'    => 'required|image|max:10240', // 10 MB max
            'title'    => 'required|string|max:255',
            'caption'  => 'nullable|string',
            'location' => 'nullable|string',
            'tags'     => 'nullable|array',
            'ai_category' => 'nullable|string',
            'ai_description' => 'nullable|string',
        ]);

        // Always use the 'public' disk locally so the symlink serves the file.
        // When deploying, switch FILESYSTEM_DISK=s3 in .env and nothing else changes.
        $disk = config('filesystems.default', 'public');
        $path = $request->file('image')->store('images', $disk);

        // Build a clean public URL for the stored file.
        // - public disk  → /storage/images/xxx.jpg  (served via storage:link)
        // - s3 disk      → https://bucket.s3.region.amazonaws.com/images/xxx.jpg
        $url = Storage::disk($disk)->url($path);

        $image = Image::create([
            'creator_id'     => $request->user()->id,
            'title'          => $request->title,
            'caption'        => $request->caption,
            'location'       => $request->location,
            'file_path'      => $path,
            'cdn_url'        => $url,
            'thumbnail_path' => $path,   // same file; resize can be added later
            'tags'           => $request->tags ?? [],
            'ai_category'    => $request->ai_category,
            'ai_description' => $request->ai_description,
            'ai_status'      => 'done',
        ]);

        $image->refresh();
        return response()->json($image, 201);
    }
}
