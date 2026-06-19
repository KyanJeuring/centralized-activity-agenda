<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EventUrlResolver
{
    public function resolve(?string $url): ?string
    {
        if (! $url) {
            return $url;
        }

        foreach ($this->candidates($url) as $candidate) {
            try {
                $response = Http::timeout(5)
                    ->connectTimeout(3)
                    ->withHeaders(['User-Agent' => 'CAA-LinkChecker/1.0'])
                    ->withOptions(['allow_redirects' => true])
                    ->get($candidate);

                if ($response->successful()) {
                    return (string) $response->effectiveUri();
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return $url;
    }

    private function candidates(string $url): array
    {
        $parts = parse_url(trim($url));

        if (! isset($parts['scheme'], $parts['host'])) {
            return [$url];
        }

        $base = $parts['scheme'] . '://' . $parts['host'];
        $path = '/' . trim($parts['path'] ?? '/', '/');

        $candidates = [$url];

        $candidates[] = $base . rtrim($path, '/') . '/';

        $segments = array_values(array_filter(explode('/', trim($path, '/'))));

        while (count($segments) > 0) {
            array_pop($segments);

            $candidatePath = count($segments)
                ? '/' . implode('/', $segments) . '/'
                : '/';

            $candidates[] = $base . $candidatePath;
        }

        return array_values(array_unique($candidates));
    }
}
