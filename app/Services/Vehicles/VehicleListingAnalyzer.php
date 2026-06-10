<?php

namespace App\Services\Vehicles;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class VehicleListingAnalyzer
{
    public function isOlxCommand(string $text): bool
    {
        return preg_match('/^\s*olx\s*:\s*\S+/i', $text) === 1;
    }

    public function analyzeOlxCommand(string $text): string
    {
        if (! (bool) config('aqamed.vehicle_lookup.enabled', false)) {
            return 'Consulta de veiculos nao habilitada. Configure VEHICLE_LOOKUP_ENABLED=true para ativar o comando.';
        }

        $url = trim((string) preg_replace('/^\s*olx\s*:\s*/i', '', $text));
        if (! $this->isAllowedOlxUrl($url)) {
            return 'Link OLX invalido. Envie no formato: olx:https://www.olx.com.br/...';
        }

        $listing = $this->fetchListing($url) ?: $this->listingFromUrl($url);
        if (! $listing) {
            return 'Nao consegui ler esse anuncio da OLX agora. Confira se o link esta publico e tente novamente.';
        }

        $fipe = $this->lookupFipe($listing);

        return $this->formatResponse($listing, $fipe);
    }

    private function isAllowedOlxUrl(string $url): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $host = mb_strtolower((string) parse_url($url, PHP_URL_HOST));

        return $host === 'olx.com.br' || str_ends_with($host, '.olx.com.br');
    }

    private function fetchListing(string $url): ?array
    {
        $response = Http::timeout((int) config('aqamed.vehicle_lookup.timeout', 12))
            ->withHeaders([
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',
            ])
            ->get($url);

        if (! $response->successful()) {
            return null;
        }

        $html = $response->body();
        $title = $this->metaContent($html, 'og:title') ?: $this->titleTag($html);
        $description = $this->metaContent($html, 'og:description');
        $jsonLd = $this->jsonLd($html);

        $title = trim((string) ($title ?: Arr::get($jsonLd, 'name', '')));
        $description = trim((string) ($description ?: Arr::get($jsonLd, 'description', '')));
        $price = $this->parseMoney(
            (string) (Arr::get($jsonLd, 'offers.price')
                ?: $this->metaContent($html, 'product:price:amount')
                ?: $this->firstMatch('/R\$\s*[\d\.\,]+/', $title.' '.$description.' '.$html))
        );

        $searchable = $title.' '.$description.' '.$this->plainText($html);

        return [
            'url' => $url,
            'title' => $title !== '' ? $title : 'Anuncio OLX',
            'description' => $description,
            'price_cents' => $price,
            'year' => $this->extractYear($searchable),
            'mileage' => $this->extractMileage($searchable),
        ];
    }

    private function listingFromUrl(string $url): ?array
    {
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        if ($path === '') {
            return null;
        }

        $slug = basename($path);
        if ($slug === '' || $slug === '/') {
            return null;
        }

        $slug = preg_replace('/-\d+(?:\?.*)?$/', '', $slug) ?: $slug;
        $title = trim(str_replace('-', ' ', $slug));
        if ($title === '') {
            return null;
        }

        $title = Str::of($title)
            ->replace('  ', ' ')
            ->title()
            ->toString();

        return [
            'url' => $url,
            'title' => $title,
            'description' => '',
            'price_cents' => null,
            'year' => $this->extractYear($title),
            'mileage' => null,
        ];
    }

    private function lookupFipe(array $listing): ?array
    {
        $year = (int) ($listing['year'] ?? 0);
        if ($year <= 0) {
            return null;
        }

        $baseUrl = rtrim((string) config('aqamed.vehicle_lookup.fipe_api_url'), '/');
        if ($baseUrl === '') {
            return null;
        }

        $title = $this->normalize((string) $listing['title']);
        $brands = $this->getJson("{$baseUrl}/carros/marcas");
        $brand = collect($brands)->first(fn ($item) => $this->containsNormalized($title, (string) ($item['nome'] ?? '')));
        if (! $brand) {
            return null;
        }

        $modelsPayload = $this->getJson("{$baseUrl}/carros/marcas/{$brand['codigo']}/modelos");
        $models = collect((array) ($modelsPayload['modelos'] ?? []));
        $model = $models
            ->sortByDesc(fn ($item) => mb_strlen($this->normalize((string) ($item['nome'] ?? ''))))
            ->first(fn ($item) => $this->containsNormalized($title, (string) ($item['nome'] ?? '')));

        if (! $model) {
            return ['brand' => $brand['nome'] ?? null];
        }

        $years = $this->getJson("{$baseUrl}/carros/marcas/{$brand['codigo']}/modelos/{$model['codigo']}/anos");
        $fipeYear = collect($years)->first(fn ($item) => str_starts_with((string) ($item['nome'] ?? ''), (string) $year));
        if (! $fipeYear) {
            return [
                'brand' => $brand['nome'] ?? null,
                'model' => $model['nome'] ?? null,
            ];
        }

        $price = $this->getJson("{$baseUrl}/carros/marcas/{$brand['codigo']}/modelos/{$model['codigo']}/anos/{$fipeYear['codigo']}");
        $fipeCents = $this->parseMoney((string) ($price['Valor'] ?? ''));

        return [
            'brand' => $price['Marca'] ?? $brand['nome'] ?? null,
            'model' => $price['Modelo'] ?? $model['nome'] ?? null,
            'year' => $price['AnoModelo'] ?? $year,
            'fuel' => $price['Combustivel'] ?? null,
            'reference' => $price['MesReferencia'] ?? null,
            'fipe_code' => $price['CodigoFipe'] ?? null,
            'price_cents' => $fipeCents,
            'raw_value' => $price['Valor'] ?? null,
        ];
    }

    private function getJson(string $url): array
    {
        $response = Http::timeout((int) config('aqamed.vehicle_lookup.timeout', 12))->get($url);

        return $response->successful() ? (array) $response->json() : [];
    }

    private function formatResponse(array $listing, ?array $fipe): string
    {
        $lines = [
            '*Analise OLX x FIPE*',
            '',
            'Anuncio: '.$listing['title'],
        ];

        if ($listing['price_cents']) {
            $lines[] = 'Valor anunciado: '.$this->formatMoney((int) $listing['price_cents']);
        }

        if ($listing['year']) {
            $lines[] = 'Ano identificado: '.$listing['year'];
        }

        if ($listing['mileage']) {
            $lines[] = 'Km informada: '.$listing['mileage'];
        }

        if (empty($listing['price_cents'])) {
            $lines[] = 'Valor anunciado: nao consegui ler automaticamente no anuncio.';
        }

        if (! $fipe || empty($fipe['price_cents'])) {
            $lines[] = '';
            $lines[] = 'FIPE: nao encontrei uma correspondencia confiavel pelo titulo do anuncio.';
            $lines[] = 'Dica: envie um link com marca, modelo e ano visiveis no anuncio.';

            return implode("\n", $lines);
        }

        $difference = (int) ($listing['price_cents'] ?? 0) - (int) $fipe['price_cents'];
        $percentage = $fipe['price_cents'] > 0 ? ($difference / $fipe['price_cents']) * 100 : 0;

        $lines[] = '';
        $lines[] = 'FIPE: '.$this->formatMoney((int) $fipe['price_cents']);
        $lines[] = 'Modelo FIPE: '.trim(($fipe['brand'] ?? '').' '.($fipe['model'] ?? ''));
        $lines[] = 'Referencia: '.($fipe['reference'] ?? 'atual');

        if ($listing['price_cents']) {
            $lines[] = 'Diferenca: '.$this->formatMoney(abs($difference)).' '.($difference >= 0 ? 'acima' : 'abaixo').' da FIPE ('.number_format(abs($percentage), 1, ',', '.').'%).';
        }

        return implode("\n", $lines);
    }

    private function metaContent(string $html, string $property): string
    {
        $quoted = preg_quote($property, '/');
        if (preg_match('/<meta[^>]+(?:property|name)=["\']'.$quoted.'["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $html, $matches)) {
            return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return '';
    }

    private function titleTag(string $html): string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            return html_entity_decode(strip_tags($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return '';
    }

    private function jsonLd(string $html): array
    {
        if (! preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
            return [];
        }

        foreach ($matches[1] as $json) {
            $decoded = json_decode(html_entity_decode($json, ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
            if (is_array($decoded)) {
                return Arr::isList($decoded) ? (array) ($decoded[0] ?? []) : $decoded;
            }
        }

        return [];
    }

    private function plainText(string $html): string
    {
        return trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?: '');
    }

    private function firstMatch(string $pattern, string $value): string
    {
        return preg_match($pattern, $value, $matches) ? (string) $matches[0] : '';
    }

    private function extractYear(string $value): ?int
    {
        if (preg_match('/\b(19[8-9][0-9]|20[0-3][0-9])\b/', $value, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function extractMileage(string $value): ?string
    {
        if (preg_match('/\b([\d\.]{1,7})\s*km\b/i', $value, $matches)) {
            return $matches[1].' km';
        }

        return null;
    }

    private function parseMoney(string $value): ?int
    {
        $value = preg_replace('/[^\d\.\,]/', '', Str::replace(['R$', ' '], '', $value)) ?: '';
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (str_contains($value, ',')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif (substr_count($value, '.') > 1 || ! preg_match('/\.\d{2}$/', $value)) {
            $value = str_replace('.', '', $value);
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (int) round(((float) $value) * 100);
    }

    private function formatMoney(int $cents): string
    {
        return 'R$ '.number_format($cents / 100, 2, ',', '.');
    }

    private function containsNormalized(string $haystack, string $needle): bool
    {
        $needle = $this->normalize($needle);

        return $needle !== '' && str_contains($haystack, $needle);
    }

    private function normalize(string $value): string
    {
        return Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();
    }
}
