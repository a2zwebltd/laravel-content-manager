<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Support;

use Closure;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Throwable;

/**
 * A featured image handed to the content tools, as a URL or as base64.
 *
 * A content key is the only credential the caller holds, so neither form may
 * reach further than an image on the public internet:
 *  - a URL is fetched only from a public address (no loopback, private,
 *    link-local or reserved range — the host's own services sit there), on
 *    the standard ports, with no redirects, and the address checked is the
 *    one connected to, so a DNS answer cannot change between the two;
 *  - the bytes must be a raster image (JPEG, PNG, WebP, GIF) by their
 *    content, and the stored file is named from that type, never from the
 *    caller, so HTML or SVG is never served from the site's own origin.
 */
final class RemoteImage
{
    /** @var array<string, string> MIME type => extension */
    public const TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    public const MAX_BYTES = 15 * 1024 * 1024;

    /** @var (Closure(string): list<string>)|null */
    private static ?Closure $resolver = null;

    /** Replaces DNS resolution (tests). Null restores the system resolver. */
    public static function resolveUsing(?Closure $resolver): void
    {
        self::$resolver = $resolver;
    }

    /**
     * @return array{bytes: string, extension: string}
     *
     * @throws InvalidArgumentException when the URL, the address or the content is refused
     */
    public static function fetch(string $url): array
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower(trim((string) ($parts['host'] ?? ''), '[]'));

        if (! in_array($scheme, ['http', 'https'], true) || $host === '' || isset($parts['user']) || isset($parts['pass'])) {
            throw new InvalidArgumentException('The image URL must be a plain http:// or https:// address.');
        }

        $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));

        if (! in_array($port, [80, 443], true)) {
            throw new InvalidArgumentException('The image URL must use the standard http or https port.');
        }

        $addresses = filter_var($host, FILTER_VALIDATE_IP) !== false ? [$host] : (self::$resolver ?? self::systemResolver(...))($host);

        if ($addresses === [] || array_filter($addresses, fn (string $ip): bool => ! self::isPublic($ip)) !== []) {
            throw new InvalidArgumentException('The image URL must point to a public address.');
        }

        $pinned = str_contains($addresses[0], ':') ? '['.$addresses[0].']' : $addresses[0];

        try {
            $response = Http::withOptions([
                'allow_redirects' => false,
                'curl' => [CURLOPT_RESOLVE => ["{$host}:{$port}:{$pinned}"]],
            ])->timeout(15)->get($url);
        } catch (Throwable) {
            throw new InvalidArgumentException('The image could not be fetched.');
        }

        if (! $response->successful()) {
            throw new InvalidArgumentException('The image could not be fetched.');
        }

        return self::accept($response->body());
    }

    /**
     * @return array{bytes: string, extension: string}
     *
     * @throws InvalidArgumentException
     */
    public static function decode(string $base64): array
    {
        $bytes = base64_decode((string) preg_replace('#^data:[^,]*,#', '', $base64), true);

        if ($bytes === false) {
            throw new InvalidArgumentException('The base64 data is not valid.');
        }

        return self::accept($bytes);
    }

    /**
     * @return array{bytes: string, extension: string}
     *
     * @throws InvalidArgumentException
     */
    private static function accept(string $bytes): array
    {
        if ($bytes === '' || strlen($bytes) > self::MAX_BYTES) {
            throw new InvalidArgumentException('The image must be between 1 byte and '.(self::MAX_BYTES / 1024 / 1024).' MB.');
        }

        $type = (string) (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);

        if (! isset(self::TYPES[$type])) {
            throw new InvalidArgumentException('The file must be a JPEG, PNG, WebP or GIF image.');
        }

        return ['bytes' => $bytes, 'extension' => self::TYPES[$type]];
    }

    private static function isPublic(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    /** @return list<string> */
    private static function systemResolver(string $host): array
    {
        $addresses = [];

        foreach ([DNS_A => 'ip', DNS_AAAA => 'ipv6'] as $type => $key) {
            foreach (@dns_get_record($host, $type) ?: [] as $record) {
                if (isset($record[$key])) {
                    $addresses[] = (string) $record[$key];
                }
            }
        }

        return $addresses;
    }
}
