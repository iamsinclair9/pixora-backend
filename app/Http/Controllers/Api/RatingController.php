<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Models\Rating;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function show(Image $image)
    {
        return response()->json([
            'avg_rating' => (float) $image->avg_rating,
            'rating_count' => $image->rating_count,
        ]);
    }

    public function store(Request $request, Image $image)
    {
        $request->validate([
            'score' => 'required|integer|min:1|max:5',
        ]);

        $rating = Rating::updateOrCreate(
            ['image_id' => $image->id, 'user_id' => $request->user()->id],
            ['score' => $request->score]
        );

        // Update image aggregates
        $avg = $image->ratings()->avg('score');
        $count = $image->ratings()->count();

        $image->update([
            'avg_rating' => $avg,
            'rating_count' => $count,
        ]);

        return response()->json([
            'rating' => $rating,
            'avg_rating' => (float) $avg,
            'rating_count' => $count,
        ]);
    }
}
