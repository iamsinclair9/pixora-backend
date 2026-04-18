<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    public function sas(Request $request)
    {
      // For this demo, I will simulate the SAS token generation and return a dummy upload URL and token. In a real implementation.
        return response()->json([
            'upload_url' => '/api/v1/uploads/confirm', // local simulation endpoint
            'sas_token' => 'simulated-sas-token-' . Str::random(16),
            'expires_in' => 900 // 15 mins
        ]);
    }

    public function confirm(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // 10MB max
            'title' => 'required|string|max:255',
            'caption' => 'nullable|string',
            'location' => 'nullable|string',
            'tags' => 'nullable|array',
        ]);

        $path = $request->file('image')->store('images', 'public');

        $image = Image::create([
            'creator_id' => $request->user()->id,
            'title' => $request->title,
            'caption' => $request->caption,
            'location' => $request->location,
            'file_path' => $path,
            'cdn_url' => '/storage/' . $path, // Local simulated CDN URL
            'thumbnail_path' => $path, // In a real app, generating thumbnail async
            'tags' => $request->tags ?? [],
            'ai_status' => 'done', // Simulated AI completion
        ]);

        $image->refresh();
        return response()->json($image, 201);
    }
}
