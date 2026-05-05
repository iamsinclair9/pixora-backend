<?php

namespace App\Jobs;

use App\Models\Comment;
use App\Services\AIService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AnalyzeCommentJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Comment $comment)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(AIService $aiService): void
    {
        $result = $aiService->detectSentiment($this->comment->body);

        $this->comment->update([
            'sentiment' => $result['Sentiment'],
            'sentiment_score' => $result['Score'],
        ]);
    }
}
