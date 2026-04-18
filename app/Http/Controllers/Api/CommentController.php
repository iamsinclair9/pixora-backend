<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Image;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Image $image)
    {
        $comments = $image->comments()->with('user:id,name,avatar_url')->latest()->paginate(20);
        return response()->json($comments);
    }

    public function store(Request $request, Image $image)
    {
        $request->validate([
            'body' => 'required|string|max:1000',
        ]);

        $comment = $image->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->body,
        ]);

        $comment->load('user:id,name,avatar_url');

        return response()->json($comment, 201);
    }

    public function destroy(Request $request, Comment $comment)
    {
        if ($comment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted']);
    }
}
