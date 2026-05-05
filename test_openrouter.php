<?php
$apiKey = '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';

$tiny = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI6QAAAABJRU5ErkJggg==';

// Target models that support vision + stay under free credits by limiting max_tokens
$candidates = [
    'google/gemini-2.5-flash',
    'google/gemini-2.5-flash-image',
    'google/gemini-2.0-flash-lite-001',
    'google/gemini-2.0-flash-001',
    'openai/gpt-4o-mini',
    'anthropic/claude-3-haiku',
    'meta-llama/llama-3.2-11b-vision-instruct:free',
    'qwen/qwen-2-vl-7b-instruct:free',
    'microsoft/phi-3.5-vision-128k-instruct:free',
];

foreach ($candidates as $model) {
    echo "Testing: $model ... ";
    $payload = json_encode([
        'model'      => $model,
        'max_tokens' => 200,   // hard cap to avoid credit overrun
        'messages'   => [
            [
                'role'    => 'user',
                'content' => [
                    ['type' => 'text', 'text' => 'Describe this image in ONE sentence.'],
                    ['type' => 'image_url', 'image_url' => ['url' => 'data:image/png;base64,' . $tiny]]
                ]
            ]
        ],
    ]);

    $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
    ]);
    $body     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($body, true);
    if ($httpCode === 200 && isset($data['choices'][0]['message']['content'])) {
        echo "✅ WORKS! \"" . trim($data['choices'][0]['message']['content']) . "\"\n";
    } else {
        $msg = $data['error']['message'] ?? substr($body, 0, 100);
        echo "❌ ($httpCode): $msg\n";
    }
}
