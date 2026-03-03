<?php

namespace App\Services;

use App\Models\Lead;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LeadScoringService
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const TIMEOUT = 30;

    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('leads.anthropic_api_key');
        $this->model  = config('leads.anthropic_model');
    }

    /**
     * Score a lead via Claude and persist ai_score on the model.
     */
    public function score(Lead $lead): void
    {
        if (empty($this->apiKey)) {
            Log::warning('LeadScoringService: ANTHROPIC_API_KEY not set, skipping scoring.', [
                'lead_id' => $lead->id,
            ]);
            return;
        }

        $prompt = $this->buildPrompt($lead);

        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders([
                    'x-api-key'         => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ])
                ->post(self::API_URL, [
                    'model'      => $this->model,
                    'max_tokens' => 64,
                    'messages'   => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

            $response->throw();

            $content = $response->json('content.0.text', '');
            $score   = $this->extractScore($content);

            if ($score !== null) {
                $lead->update(['ai_score' => $score]);
                Log::info('LeadScoringService: scored lead', [
                    'lead_id'  => $lead->id,
                    'ai_score' => $score,
                ]);
            } else {
                Log::warning('LeadScoringService: could not extract numeric score from response', [
                    'lead_id'  => $lead->id,
                    'response' => $content,
                ]);
            }

        } catch (RequestException $e) {
            Log::error('LeadScoringService: API HTTP error', [
                'lead_id' => $lead->id,
                'message' => $e->getMessage(),
                'code'    => $e->response?->status(),
            ]);
        } catch (\Throwable $e) {
            Log::error('LeadScoringService: unexpected error', [
                'lead_id' => $lead->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function buildPrompt(Lead $lead): string
    {
        return <<<PROMPT
You are a business analyst. Based on the following business details, rate the likelihood (1-10) that this business needs a professional website. 10 = very likely, 1 = very unlikely.

Business Name: {$lead->business_name}
Category: {$lead->category}
Address: {$lead->address}
Phone: {$lead->phone}
Google Rating: {$lead->rating}
Number of Reviews: {$lead->reviews_count}
Has Website: No

Respond with ONLY a single integer between 1 and 10. No explanation.
PROMPT;
    }

    private function extractScore(string $text): ?int
    {
        preg_match('/\b([1-9]|10)\b/', trim($text), $matches);
        return isset($matches[1]) ? (int) $matches[1] : null;
    }
}
