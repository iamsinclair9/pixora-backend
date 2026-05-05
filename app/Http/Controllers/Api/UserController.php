<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /** GET /me — current user profile + stats */
    public function me(Request $request)
    {
        $user = $request->user();

        $stats = [
            'uploads'   => $user->images()->count(),
            'likes'     => $user->images()->sum('likes_count'),
            'bookmarks' => Bookmark::where('user_id', $user->id)->count(),
        ];

        return response()->json([
            'user'  => $user,
            'stats' => $stats,
        ]);
    }

    /** GET /me/images — all images by the authenticated user */
    public function myImages(Request $request)
    {
        $images = Image::with('creator:id,name,avatar_url')
            ->withCount('comments')
            ->where('creator_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json($images);
    }

    /** GET /me/bookmarks — all bookmarked images */
    public function bookmarks(Request $request)
    {
        $images = Image::with('creator:id,name,avatar_url')
            ->withCount('comments')
            ->whereHas('bookmarks', fn($q) => $q->where('user_id', $request->user()->id))
            ->latest()
            ->paginate(20);

        // Mark each as bookmarked
        foreach ($images as $img) {
            $img->user_bookmarked = true;
        }

        return response()->json($images);
    }

    /** POST /images/{image}/bookmark — toggle bookmark */
    public function toggleBookmark(Request $request, Image $image)
    {
        $userId = $request->user()->id;

        $existing = Bookmark::where('user_id', $userId)
            ->where('image_id', $image->id)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['bookmarked' => false]);
        }

        Bookmark::create(['user_id' => $userId, 'image_id' => $image->id]);
        return response()->json(['bookmarked' => true]);
    }

    /** POST /uploads/from-url — upload an image from a URL */
    public function uploadFromUrl(Request $request)
    {
        $request->validate([
            'url'      => 'required|string',
            'title'    => 'required|string|max:255',
            'caption'  => 'nullable|string',
            'location' => 'nullable|string',
            'tags'     => 'nullable|array',
            'ai_category' => 'nullable|string',
            'ai_description' => 'nullable|string',
        ]);

        try {
            $url = $request->url;

            if (str_starts_with($url, 'data:image/')) {
                // Handle base64 string
                $parts = explode(',', $url);
                if (count($parts) !== 2) {
                    return response()->json(['message' => 'Invalid base64 image data.'], 422);
                }
                
                $meta = explode(';', $parts[0]);
                $contentType = str_replace('data:', '', $meta[0]);
                $imageContents = base64_decode($parts[1]);
            } else {
                // Fetch the remote image
                $response = \Illuminate\Support\Facades\Http::timeout(15)->get($url);

                if (!$response->successful()) {
                    return response()->json(['message' => 'Could not fetch image from the provided URL.'], 422);
                }

                $contentType = $response->header('Content-Type') ?? 'image/jpeg';
                $imageContents = $response->body();
            }

            if (!str_starts_with($contentType, 'image/')) {
                return response()->json(['message' => 'The URL does not point to a valid image.'], 422);
            }

            // Determine extension from content-type
            $ext = match (true) {
                str_contains($contentType, 'jpeg') => 'jpg',
                str_contains($contentType, 'png')  => 'png',
                str_contains($contentType, 'webp') => 'webp',
                str_contains($contentType, 'gif')  => 'gif',
                default                            => 'jpg',
            };

            $filename = 'images/' . \Illuminate\Support\Str::uuid() . '.' . $ext;
            Storage::disk('public')->put($filename, $imageContents);

            $image = \App\Models\Image::create([
                'creator_id'     => $request->user()->id,
                'title'          => $request->title,
                'caption'        => $request->caption,
                'location'       => $request->location,
                'file_path'      => $filename,
                'cdn_url'        => Storage::disk('public')->url($filename),
                'thumbnail_path' => $filename,
                'tags'           => $request->tags ?? [],
                'ai_category'    => $request->ai_category,
                'ai_description' => $request->ai_description,
                'ai_status'      => 'done',
            ]);

            $image->refresh();

            return response()->json($image, 201);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to process the image: ' . $e->getMessage()], 500);
        }
    }
}
