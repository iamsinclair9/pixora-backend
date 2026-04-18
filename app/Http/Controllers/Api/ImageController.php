<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Image;
use Illuminate\Http\Request;

class ImageController extends Controller
{
    public function index(Request $request)
    {
        $query = Image::with('creator:id,name,avatar_url')->latest();

        if ($request->has('creator_id')) {
            $query->where('creator_id', $request->creator_id);
        }

        return response()->json($query->paginate(20));
    }

    public function show(Image $image)
    {
        $image->load(['creator:id,name,avatar_url']);
        return response()->json($image);
    }

    public function search(Request $request)
    {
        $q = $request->query('q');
        $tags = $request->query('tags');
        $loc = $request->query('loc');

        $query = Image::with('creator:id,name,avatar_url')->latest();

        if ($q) {
            $query->where(function($b) use ($q) {
                $b->where('title', 'like', "%{$q}%")
                  ->orWhere('caption', 'like', "%{$q}%");
            });
        }

        if ($loc) {
            $query->where('location', 'like', "%{$loc}%");
        }

        if ($tags) {
            $tagArray = explode(',', $tags);
            foreach ($tagArray as $tag) {
                $query->whereJsonContains('tags', trim($tag));
            }
        }

        return response()->json($query->paginate(20));
    }

    public function update(Request $request, Image $image)
    {
        if ($image->creator_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'title' => 'string|max:255',
            'caption' => 'nullable|string',
            'location' => 'nullable|string',
            'tags' => 'nullable|array',
        ]);

        $image->update($request->only(['title', 'caption', 'location', 'tags']));

        return response()->json($image);
    }

    public function destroy(Request $request, Image $image)
    {
        if ($image->creator_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $image->delete();

        return response()->json(['message' => 'Image deleted']);
    }
}
