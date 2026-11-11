<?php

namespace App\Support;

/**
 * Files are never uploaded to the system: invoices, photos and documents live in
 * Google Drive and the archive stores a link to them. This helper understands the
 * common Drive URL shapes so the panel can show an embedded preview.
 */
final class DriveLink
{
    public static function isDrive(?string $url): bool
    {
        if (blank($url)) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST) ?: '';

        return in_array($host, ['drive.google.com', 'docs.google.com'], true);
    }

    public static function fileId(?string $url): ?string
    {
        if (! self::isDrive($url)) {
            return null;
        }

        if (preg_match('~/(?:file|document|spreadsheets|presentation)/d/([a-zA-Z0-9_-]{10,})~', $url, $m)) {
            return $m[1];
        }

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return isset($query['id']) && preg_match('~^[a-zA-Z0-9_-]{10,}$~', $query['id']) ? $query['id'] : null;
    }

    public static function folderId(?string $url): ?string
    {
        if (self::isDrive($url) && preg_match('~/folders/([a-zA-Z0-9_-]{10,})~', (string) $url, $m)) {
            return $m[1];
        }

        return null;
    }

    /** URL that can be placed in an iframe, or null when the link cannot be embedded. */
    public static function embedUrl(?string $url): ?string
    {
        if ($folder = self::folderId($url)) {
            return "https://drive.google.com/embeddedfolderview?id={$folder}#grid";
        }

        if (! ($id = self::fileId($url))) {
            return null;
        }

        return match (true) {
            str_contains($url, '/document/') => "https://docs.google.com/document/d/{$id}/preview",
            str_contains($url, '/spreadsheets/') => "https://docs.google.com/spreadsheets/d/{$id}/preview",
            str_contains($url, '/presentation/') => "https://docs.google.com/presentation/d/{$id}/preview",
            default => "https://drive.google.com/file/d/{$id}/preview",
        };
    }

    public static function canPreview(?string $url): bool
    {
        return self::embedUrl($url) !== null;
    }
}
