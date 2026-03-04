<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GooglePlacesService
{
    private const TEXT_SEARCH_URL = 'https://maps.googleapis.com/maps/api/place/textsearch/json';
    private const DETAILS_URL     = 'https://maps.googleapis.com/maps/api/place/details/json';
    private const TIMEOUT         = 15;

    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('leads.google_places_api_key');
    }

    /**
     * Search places by text query. Handles pagination via next_page_token.
     *
     * @param  int|null $maxResults  Stop fetching pages once this many candidates are collected.
     *                               Null (default) fetches all available pages.
     * @return array<int, array<string, mixed>>
     */
    public function textSearch(string $query, ?int $maxResults = null): array
    {
        $results   = [];
        $pageToken = null;

        do {
            $params = [
                'query' => $query,
                'key'   => $this->apiKey,
            ];

            if ($pageToken) {
                // Google requires a short delay before using a page token
                sleep(2);
                $params['pagetoken'] = $pageToken;
            }

            try {
                $response = Http::timeout(self::TIMEOUT)
                    ->get(self::TEXT_SEARCH_URL, $params);

                $response->throw();

                $body      = $response->json();
                $results   = array_merge($results, $body['results'] ?? []);
                $pageToken = $body['next_page_token'] ?? null;

                // Stop early if we already have enough candidates
                if ($maxResults !== null && count($results) >= $maxResults) {
                    Log::debug('GooglePlacesService::textSearch: candidate cap reached, stopping pagination.', [
                        'query'       => $query,
                        'collected'   => count($results),
                        'max_results' => $maxResults,
                    ]);
                    break;
                }

            } catch (RequestException $e) {
                Log::error('GooglePlacesService::textSearch HTTP error', [
                    'query'   => $query,
                    'message' => $e->getMessage(),
                    'code'    => $e->response?->status(),
                ]);
                break;
            } catch (\Throwable $e) {
                Log::error('GooglePlacesService::textSearch unexpected error', [
                    'query'   => $query,
                    'message' => $e->getMessage(),
                ]);
                break;
            }

        } while ($pageToken);

        return $results;
    }

    /**
     * Fetch place details (phone, website, rating, etc.) by place_id.
     *
     * @return array<string, mixed>|null
     */
    public function getDetails(string $placeId): ?array
    {
        $fields = 'name,formatted_address,formatted_phone_number,website,rating,user_ratings_total,url,reviews';

        try {
            $response = Http::timeout(self::TIMEOUT)
                ->get(self::DETAILS_URL, [
                    'place_id' => $placeId,
                    'fields'   => $fields,
                    'key'      => $this->apiKey,
                ]);

            $response->throw();

            $body = $response->json();

            if (($body['status'] ?? '') !== 'OK') {
                Log::warning('GooglePlacesService::getDetails non-OK status', [
                    'place_id' => $placeId,
                    'status'   => $body['status'] ?? 'UNKNOWN',
                ]);
                return null;
            }

            return $body['result'] ?? null;

        } catch (RequestException $e) {
            Log::error('GooglePlacesService::getDetails HTTP error', [
                'place_id' => $placeId,
                'message'  => $e->getMessage(),
                'code'     => $e->response?->status(),
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::error('GooglePlacesService::getDetails unexpected error', [
                'place_id' => $placeId,
                'message'  => $e->getMessage(),
            ]);
            return null;
        }
    }
}
