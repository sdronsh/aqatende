<?php

namespace App\Services\Licenses;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LicenseClient
{
    public function getLicenseByCnpj(string $cnpj, bool $useCache = true): ?array
    {
        $cnpj = $this->normalizeCnpj($cnpj);
        if ($cnpj === '') {
            return null;
        }

        $cacheKey = 'license:cnpj:'.$cnpj;
        $cached = $useCache ? Cache::get($cacheKey) : null;

        $cacheTtl = (int) config('aqamed.license.cache_ttl', 600);
        $staleTtl = (int) config('aqamed.license.stale_ttl', 1800);
        $now = now()->timestamp;

        if ($useCache && is_array($cached) && isset($cached['fetched_at'])) {
            if (($now - (int) $cached['fetched_at']) <= $cacheTtl) {
                return $cached['data'] ?? null;
            }
        }

        $baseUrl = (string) config('aqamed.license.api_url');
        if ($baseUrl === '') {
            return $useCache ? $this->useStale($cached, $now, $staleTtl) : null;
        }

        $endpoint = (string) config('aqamed.license.endpoint', '/api/licenses/lookup');
        $url = rtrim($baseUrl, '/').$endpoint;

        try {
            $request = Http::timeout(5);
            $token = (string) config('aqamed.license.api_token');
            if ($token !== '') {
                $request = $request->withToken($token);
            }

            if (str_contains($endpoint, '{cnpj}')) {
                $url = rtrim($baseUrl, '/').str_replace('{cnpj}', $cnpj, $endpoint);
                $response = $request->get($url);
            } else {
                $response = $request->get($url, ['cnpj' => $cnpj]);
            }

            if (! $response->successful()) {
                Log::warning('License API request failed', [
                    'url' => $url,
                    'status_code' => $response->status(),
                    'cnpj' => $cnpj,
                    'use_cache' => $useCache,
                ]);
                return $useCache ? $this->useStale($cached, $now, $staleTtl) : null;
            }

            $payload = $response->json();
            $license = $this->extractLicensePayload($payload);
            if (! is_array($license)) {
                Log::warning('License API returned invalid payload', [
                    'url' => $url,
                    'cnpj' => $cnpj,
                    'use_cache' => $useCache,
                ]);
                return $useCache ? $this->useStale($cached, $now, $staleTtl) : null;
            }

            Cache::put($cacheKey, [
                'data' => $license,
                'fetched_at' => $now,
            ], $staleTtl);

            return $license;
        } catch (\Throwable $e) {
            Log::warning('License API request exception', [
                'url' => $url,
                'cnpj' => $cnpj,
                'message' => $e->getMessage(),
                'use_cache' => $useCache,
            ]);
            return $useCache ? $this->useStale($cached, $now, $staleTtl) : null;
        }
    }

    private function useStale(?array $cached, int $now, int $staleTtl): ?array
    {
        if (! is_array($cached) || ! isset($cached['fetched_at'])) {
            return null;
        }

        if (($now - (int) $cached['fetched_at']) <= $staleTtl) {
            return $cached['data'] ?? null;
        }

        return null;
    }

    private function normalizeCnpj(string $value): string
    {
        return preg_replace('/\D/', '', $value) ?? '';
    }

    private function extractLicensePayload(mixed $payload): ?array
    {
        if (! is_array($payload)) {
            return null;
        }

        $hasTopLevelLicenseData = array_key_exists('has_access', $payload)
            || array_key_exists('status', $payload)
            || array_key_exists('billing', $payload)
            || array_key_exists('user_limit', $payload)
            || array_key_exists('clinic_limit', $payload)
            || array_key_exists('unit_limit', $payload);

        if ($hasTopLevelLicenseData) {
            return $payload;
        }

        $data = $payload['data'] ?? null;
        if (is_array($data)) {
            return $data;
        }

        $license = $payload['license'] ?? null;
        if (is_array($license)) {
            return $license;
        }

        return $payload;
    }
}
