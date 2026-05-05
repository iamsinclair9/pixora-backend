<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    use HasUuids;

    protected $fillable = [
        'creator_id',
        'title',
        'caption',
        'location',
        'file_path',
        'cdn_url',
        'thumbnail_path',
        'tags',
        'ai_status',
        'moderation_flag',
        'avg_rating',
        'rating_count',
        'ai_category',
        'ai_description',
        'likes_count',
        'dislikes_count',
    ];

    protected $appends = ['image_url', 'thumbnail_url'];

    protected function casts(): array
    {
        return [
            'tags'            => 'array',
            'moderation_flag' => 'boolean',
            'avg_rating'      => 'decimal:2',
        ];
    }

    // ─────────────────────────────────────────────
    //  Accessors — always return a full public URL
    // ─────────────────────────────────────────────

    /**
     * Full URL to the original image.
     * Works for both local/public disk and S3.
     */
    public function getImageUrlAttribute(): string
{
    if ($this->cdn_url && str_starts_with($this->cdn_url, 'http')) {
        return $this->cdn_url;
    }

    if (!$this->file_path) {
        return '';  // or a default placeholder URL
    }

    $disk = config('filesystems.default', 'public');
    return Storage::disk($disk)->url($this->file_path);
}

public function getThumbnailUrlAttribute(): string
{
    $path = $this->thumbnail_path ?: $this->file_path;

    if (!$path) {
        return '';  // or a default placeholder URL
    }

    if (str_starts_with($path, 'http')) {
        return $path;
    }

    $disk = config('filesystems.default', 'public');
    return Storage::disk($disk)->url($path);
}

    // ─────────────────────────────────────────────
    //  Relationships
    // ─────────────────────────────────────────────

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function interactions()
    {
        return $this->hasMany(ImageInteraction::class);
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }
}
