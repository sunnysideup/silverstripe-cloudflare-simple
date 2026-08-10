<?php

namespace Sunnysideup\CloudflareSimple;

use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Flushable;

class CloudflarePurgeCache implements Flushable
{
    use Configurable;

    /**
     * List of characters that cannot be part of the zone ID.
     */
    private const INVALID_ZONE_ID_CHARS = ['.', '/', '\\', '?', '#', '%'];

    /**
     * Host names whose cache to be purged.
     *
     * @var string[]|null
     */
    private static array $purge_hosts = [];

    /**
     * Flush the Cloudflare cache.
     */
    public static function flush(): void
    {
        $purgeHosts = self::config()->get('purge_hosts') ?? [];

        [$zoneId, $apiToken] = self::getCredentials();
        if (!$zoneId || !$apiToken) {
            return;
        }
        if (!self::isValidZoneId($zoneId)) {
            user_error("Invalid zone ID: $zoneId", E_USER_ERROR);
            return;
        }

        $result = self::purgeCache($zoneId, $apiToken, $purgeHosts);
        if (Director::is_cli()) {
            if ($result) {
                echo self::craftMessage('Cloudflare cache purged successfully', $purgeHosts);
            } else {
                user_error(self::craftMessage('Failed to purge Cloudflare cache', $purgeHosts), E_USER_WARNING);
            }
        }
    }

    private static function craftMessage(string $message, array $hosts): string
    {
        $hostList = empty($hosts) ? ' everything' : (':' . PHP_EOL . '- ' . implode(PHP_EOL . '- ', $hosts));
        return "{$message} for{$hostList}" . PHP_EOL;
    }

    private static function purgeCache(string $zoneId, string $apiToken, array $hosts): bool
    {
        $url = "https://api.cloudflare.com/client/v4/zones/$zoneId/purge_cache";
        $data = empty($hosts) ? ['purge_everything' => true] : ['hosts' => $hosts];
        $options = [
            'http' => [
                'header' => [
                    "Content-Type: application/json",
                    "Authorization: Bearer $apiToken",
                ],
                'method' => 'POST',
                'content' => json_encode($data),
            ],
        ];

        $context = stream_context_create($options);
        $result = @json_decode(@file_get_contents($url, false, $context), true);

        return $result && isset($result['success']) && $result['success'];
    }

    private static function getCredentials(): array
    {
        return [
            Environment::getEnv('CLOUDFLARE_PURGE_ZONE_ID'),
            Environment::getEnv('CLOUDFLARE_PURGE_API_TOKEN'),
        ];
    }

    private static function isValidZoneId(string $zoneId): bool
    {
        return array_find(
            self::INVALID_ZONE_ID_CHARS,
            static fn (string $ch): bool => str_contains($zoneId, $ch)
        ) === null;
    }
}
