<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Models\ImageInteraction;
use App\Models\Bookmark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImageController extends Controller
{
    private function appendUserInteractions($images, $user)
    {
        if (!$user || empty($images)) return;

        $userId = $user->id;
        $imageIds = collect($images)->pluck('id')->toArray();
        
        $interactions = ImageInteraction::where('user_id', $userId)
            ->whereIn('image_id', $imageIds)
            ->get()
            ->keyBy('image_id');
            
        $bookmarks = Bookmark::where('user_id', $userId)
            ->whereIn('image_id', $imageIds)
            ->pluck('image_id')
            ->toArray();

        foreach ($images as $image) {
            $interaction = $interactions->get($image->id);
            $image->user_liked = $interaction && $interaction->type === 'like';
            $image->user_disliked = $interaction && $interaction->type === 'dislike';
            $image->user_bookmarked = in_array($image->id, $bookmarks);
        }
    }
    public function index(Request $request)
    {
        $query = Image::with('creator:id,name,avatar_url')
            ->withCount('comments')
            ->latest();

        if ($request->has('creator_id')) {
            $query->where('creator_id', $request->creator_id);
        }

        if ($request->has('category')) {
            $query->where('ai_category', $request->category);
        }

        $images = $query->paginate(20);

        if ($request->user()) {
            $this->appendUserInteractions($images->items(), $request->user());
        }

        return response()->json($images);
    }

    public function show(Request $request, Image $image)
    {
        $image->load(['creator:id,name,avatar_url']);
        
        if ($request->user()) {
            $this->appendUserInteractions([$image], $request->user());
        }

        return response()->json($image);
    }

    public function interact(Request $request, Image $image)
    {
        $request->validate([
            'type' => 'required|in:like,dislike,none'
        ]);

        $userId = $request->user()->id;
        $type = $request->type;

        DB::transaction(function () use ($image, $userId, $type) {
            // Remove existing interaction
            ImageInteraction::where('user_id', $userId)
                ->where('image_id', $image->id)
                ->delete();

            if ($type !== 'none') {
                ImageInteraction::create([
                    'user_id' => $userId,
                    'image_id' => $image->id,
                    'type' => $type
                ]);
            }

            // Sync counts
            $image->likes_count = ImageInteraction::where('image_id', $image->id)->where('type', 'like')->count();
            $image->dislikes_count = ImageInteraction::where('image_id', $image->id)->where('type', 'dislike')->count();
            $image->save();
        });

        return response()->json([
            'likes_count' => $image->likes_count,
            'dislikes_count' => $image->dislikes_count,
            'user_liked' => $type === 'like',
            'user_disliked' => $type === 'dislike'
        ]);
    }

    public function search(Request $request)
    {
        $q = $request->query('q');
        $tags = $request->query('tags');
        $loc = $request->query('loc');
        $category = $request->query('category');

        $query = Image::with('creator:id,name,avatar_url')->latest();

       
        if ($category && !$q && !$tags && !$loc) {
            $query->where('ai_category', $category);
            $images = $query->paginate(20);
            if ($request->user()) {
                $this->appendUserInteractions($images->items(), $request->user());
            }
            return response()->json($images);
        }

      
        if ($q || $tags || $loc) {
            $query->where(function($main) use ($q, $tags, $loc) {
                if ($q) {
                    $main->orWhere('title', 'like', "%{$q}%")
                         ->orWhere('caption', 'like', "%{$q}%")
                         ->orWhere('ai_description', 'like', "%{$q}%")
                         ->orWhere('ai_category', 'like', "%{$q}%");
                }
                
                if ($loc) {
                    $main->orWhere('location', 'like', "%{$loc}%");
                }
                
                if ($tags) {
                    $tagArray = explode(',', $tags);
                    foreach ($tagArray as $tag) {
                        $main->orWhereJsonContains('tags', trim($tag));
                        $main->orWhere('tags', 'like', "%" . trim($tag) . "%");
                    }
                }
            });
        }

        $images = $query->paginate(20);
        if ($request->user()) {
            $this->appendUserInteractions($images->items(), $request->user());
        }

        return response()->json($images);
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
