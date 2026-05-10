<?php

namespace App\Services\Communication;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class CommunicationClient
{
    public function configured(): bool
    {
        return $this->baseUrl() !== '' && $this->token() !== '';
    }

    public function createWhatsappSession(array $payload): array
    {
        return $this->request()
            ->post($this->url('/whatsapp/sessions'), $payload)
            ->throw()
            ->json();
    }

    public function getWhatsappSessionQr(string $uuid): array
    {
        return $this->request()
            ->get($this->url("/whatsapp/sessions/{$uuid}/qr"))
            ->throw()
            ->json();
    }

    public function getWhatsappSessionStatus(string $uuid): array
    {
        return $this->request()
            ->get($this->url("/whatsapp/sessions/{$uuid}/status"))
            ->throw()
            ->json();
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->timeout(15)
            ->withToken($this->token());
    }

    private function url(string $path): string
    {
        return rtrim($this->baseUrl(), '/').$path;
    }

    private function baseUrl(): string
    {
        return (string) config('aqamed.communication.api_url', '');
    }

    private function token(): string
    {
        return (string) config('aqamed.communication.api_token', '');
    }
}
