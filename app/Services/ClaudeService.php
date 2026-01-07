<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ClaudeService
{
    public function ask($message)
    {
        $response = Http::withHeaders([
            'x-api-key' => env('ANTHROPIC_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-3-opus-20240229',
            'max_tokens' => 800,
            'messages' => [
                ['role' => 'user', 'content' => $message],
            ],
        ]);

        return $response->json();
    }
}
