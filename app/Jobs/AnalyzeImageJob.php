<?php

namespace App\Jobs;

use App\Models\Image;
use App\Services\GeminiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AnalyzeImageJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Image $image)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(GeminiService $geminiService): void
    {
        $this->image->update(['ai_status' => 'processing']);

        Log::info("Starting Gemini analysis for image: {$this->image->id}");

        $result = $geminiService->analyzeImage($this->image->file_path);

        if ($result) {
            $this->image->update([
                'ai_category' => $result['category'] ?? 'Unknown',
                'ai_description' => $result['description'] ?? '',
                'tags' => array_unique(array_merge($this->image->tags ?? [], $result['tags'] ?? [])),
                'ai_status' => 'done'
            ]);
            Log::info("Gemini analysis completed for image: {$this->image->id}");
        } else {
            $this->image->update(['ai_status' => 'failed']);
            Log::error("Gemini analysis failed for image: {$this->image->id}");
        }
    }
}
