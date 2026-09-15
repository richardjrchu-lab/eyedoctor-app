<?php

namespace App\Services;

use Illuminate\Http\Request;

final class ClientIpResolver
{
    /**
     * Resolve the original visitor IP for RETINA.
     *
     * Production evidence on Render showed:
     *
     * - REMOTE_ADDR / Laravel request IP resolves to ::1
     * - CF-Connecting-IP follows the actual visitor across networks
     * - X-Forwarded-For contains a multi-hop proxy chain
     *
     * Therefore, prefer Cloudflare's single-valued client-IP header when it
     * contains a valid IP address. Fall back to Laravel's normal request IP
     * when the header is missing or malformed.
     */
    public function resolve(Request $request): string
    {
        $cloudflareIp = $this->normalize(
            $request->header('CF-Connecting-IP')
        );

        if ($cloudflareIp !== null) {
            return $cloudflareIp;
        }

        $fallbackIp = $this->normalize(
            $request->ip()
        );

        return $fallbackIp ?? 'unknown';
    }

    private function normalize(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $packed = @inet_pton($value);

        if ($packed === false) {
            return null;
        }

        $normalized = inet_ntop($packed);

        return is_string($normalized)
            ? $normalized
            : null;
    }
}
