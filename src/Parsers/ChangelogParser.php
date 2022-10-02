<?php
declare(strict_types=1);


namespace lightswitch05\PhpVersionAudit\Parsers;


use lightswitch05\PhpVersionAudit\CachedDownload;
use lightswitch05\PhpVersionAudit\DateHelpers;
use lightswitch05\PhpVersionAudit\Exceptions\DownloadException;
use lightswitch05\PhpVersionAudit\Exceptions\ParseException;
use lightswitch05\PhpVersionAudit\Logger;
use lightswitch05\PhpVersionAudit\PhpRelease;
use lightswitch05\PhpVersionAudit\PhpVersion;

final class ChangelogParser
{
    /**
     * @return PhpRelease[]
     * @throws DownloadException
     * @throws ParseException
     */
    public static function run(): array
    {
        $urls = [
            'https://www.php.net/ChangeLog-4.php',
            'https://www.php.net/ChangeLog-5.php',
            'https://www.php.net/ChangeLog-7.php',
            'https://www.php.net/ChangeLog-8.php'
        ];
        $allReleases = [];
        foreach ($urls as $url) {
            $releases = self::parseChangelog($url);
            $allReleases = array_merge($allReleases, $releases);
        }
        return PhpRelease::sort($allReleases);
    }

    /**
     * @param string $url
     * @return PhpRelease[]
     * @throws DownloadException
     * @throws ParseException
     */
    private static function parseChangelog(string $url): array
    {
        $releases = [];
        Logger::info('Beginning Changelog parse: ', $url);
        $dom = CachedDownload::dom($url);
        foreach ($dom->getElementsByTagName('section') as $sectionTag) {
            $versionString = $sectionTag->getAttribute('id');
            $version = PhpVersion::fromString($versionString);
            if ($version === null) {
                continue;
            }
            $dateString = trim($sectionTag->getElementsByTagName('time')[0]->getAttribute('datetime'));
            $releaseDate = DateHelpers::fromYMDToISO8601($dateString);
            $releases[] = PhpRelease::fromReleaseDescription($version, $releaseDate, $sectionTag->textContent);
        }
        return $releases;
    }
}
