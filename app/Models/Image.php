<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

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
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'moderation_flag' => 'boolean',
            'avg_rating' => 'decimal:2',
        ];
    }

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
}
