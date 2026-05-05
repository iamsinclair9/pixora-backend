<?php

namespace App\Services;

use Aws\Rekognition\RekognitionClient;
use Aws\Comprehend\ComprehendClient;
use Illuminate\Support\Facades\Log;

class AIService
{
    protected $rekognition;
    protected $comprehend;

    public function __construct()
    {
        $config = [
            'region' => config('services.aws.region', env('AWS_DEFAULT_REGION', 'us-east-1')),
            'version' => 'latest',
            'credentials' => [
                'key'    => config('services.aws.key', env('AWS_ACCESS_KEY_ID')),
                'secret' => config('services.aws.secret', env('AWS_SECRET_ACCESS_KEY')),
            ],
        ];

        // Only instantiate if credentials are provided, otherwise it might fail in dev
        if ($config['credentials']['key']) {
            $this->rekognition = new RekognitionClient($config);
            $this->comprehend = new ComprehendClient($config);
        }
    }

    /**
     * Detect labels in an image using AWS Rekognition.
     */
    public function detectLabels(string $bucket, string $key): array
    {
        if (!$this->rekognition) {
            Log::warning('AWS Rekognition not configured. Returning mock labels.');
            return ['Nature', 'Photo', 'Unprocessed'];
        }

        try {
            $result = $this->rekognition->detectLabels([
                'Image' => [
                    'S3Object' => [
                        'Bucket' => $bucket,
                        'Name'   => $key,
                    ],
                ],
                'MaxLabels' => 10,
                'MinConfidence' => 70,
            ]);

            return collect($result['Labels'])->pluck('Name')->toArray();
        } catch (\Exception $e) {
            Log::error('AWS Rekognition Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Detect sentiment of text using AWS Comprehend.
     */
    public function detectSentiment(string $text): array
    {
        if (!$this->comprehend) {
            Log::warning('AWS Comprehend not configured. Returning mock sentiment.');
            return ['Sentiment' => 'NEUTRAL', 'Score' => 0.0];
        }

        try {
            $result = $this->comprehend->detectSentiment([
                'LanguageCode' => 'en',
                'Text' => $text,
            ]);

            return [
                'Sentiment' => $result['Sentiment'],
                'Score' => $result['SentimentScore'][ucfirst(strtolower($result['Sentiment']))] ?? 0.0,
            ];
        } catch (\Exception $e) {
            Log::error('AWS Comprehend Error: ' . $e->getMessage());
            return ['Sentiment' => 'UNKNOWN', 'Score' => 0.0];
        }
    }
}
