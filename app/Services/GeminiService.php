<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;
    protected $endpoint = 'https://openrouter.ai/api/v1/chat/completions';
    protected $model = 'google/gemini-flash-1.5';

    public function __construct()
    {
        $this->apiKey = env('OPENROUTER_API_KEY');
    }

    /**
     * Analyze an image using OpenRouter (Gemini 1.5 Flash).
     */
    public function analyzeImage(string $filePath)
    {
        if (!$this->apiKey) {
            Log::warning('OpenRouter API Key is not set. Skipping AI analysis.');
            return $this->getMockResponse();
        }

        try {
            $fullPath = storage_path('app/public/' . $filePath);
            if (!file_exists($fullPath)) return null;

            return $this->processImage($fullPath, "Analyze this image and provide a JSON response with exactly three fields:
            1. 'category': A single word category (e.g., Nature, Animal, Architecture, People, Tech, Food, Urban).
            2. 'description': A concise, professional one-sentence description of the photo.
            3. 'tags': An array of 5 relevant keywords.");

        } catch (\Exception $e) {
            Log::error('AI Service Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get metadata suggestions for a draft image.
     */
    public function suggestMetadata(string $fullPath)
    {
        if (!$this->apiKey) return null;

        return $this->processImage($fullPath, "Analyze this image and suggest a professional title, a descriptive caption, and 5 relevant tags.
        Return ONLY a raw JSON object with these exact keys:
        - 'title': A catchy title (max 50 chars).
        - 'caption': An engaging description (max 150 chars).
        - 'tags': An array of 5 keywords.
        - 'category': A single word category (e.g., Nature, Animal, Architecture, People, Tech, Food, Urban).");
    }

    protected function processImage(string $fullPath, string $prompt)
    {
        $imageData = base64_encode(file_get_contents($fullPath));
        $mimeType = mime_content_type($fullPath);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'HTTP-Referer' => config('app.url'),
            'X-Title' => config('app.name'),
        ])->post($this->endpoint, [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $prompt . " Return ONLY the raw JSON object."],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => "data:{$mimeType};base64,{$imageData}"
                            ]
                        ]
                    ]
                ]
            ],
            'response_format' => ['type' => 'json_object']
        ]);

        if ($response->successful()) {
            $result = $response->json();
            $textResponse = $result['choices'][0]['message']['content'] ?? '';
            
            Log::info('OpenRouter Raw Response: ' . $textResponse);

            return json_decode($textResponse, true);
        }

        Log::error('OpenRouter API Error: ' . $response->body());
        return null;
    }

    protected function getMockResponse()
    {
        return [
            'category' => 'Unknown',
            'description' => 'A photo shared on Pixora.',
            'tags' => ['photography', 'pixora', 'social']
        ];
    }
}
