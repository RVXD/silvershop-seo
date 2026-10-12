<?php

declare(strict_types=1);

namespace SilverShop\Seo\Control;

use SilverShop\Seo\Extension\SeoSiteTreeExtension;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\ORM\DataObject;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * Serves {@code /robots.txt}. Core has no robots UI, so this ships one: a SiteConfig override field when the merchant
 * wants full control, otherwise a generated default that references the sitemap and disallows the non-indexable
 * sections (cart / checkout / account / search), kept in sync with the actual page URLs and the SEO noindex rule.
 */
class RobotsController extends Controller
{
    use Configurable;

    /**
     * Extra path prefixes to always disallow, in addition to the resolved cart/checkout/account/search page URLs.
     *
     * @var array<int, string>
     */
    private static array $disallow = [];

    /**
     * Whether to append the {@code Sitemap:} line pointing at the googlesitemaps XML.
     */
    private static bool $include_sitemap = true;

    /**
     * Path (relative to the site root) the sitemap is served at.
     */
    private static string $sitemap_path = 'sitemap.xml';

    private static array $allowed_actions = [
        'index',
    ];

    public function index(HTTPRequest $request): HTTPResponse
    {
        $response = HTTPResponse::create($this->getRobotsContent());
        $response->addHeader('Content-Type', 'text/plain; charset=utf-8');

        return $response;
    }

    /**
     * The robots.txt body: the merchant's custom override when set, otherwise the generated default.
     */
    public function getRobotsContent(): string
    {
        $custom = trim((string) SiteConfig::current_site_config()->getField('CustomRobotsTxt'));
        if ($custom !== '') {
            return $this->ensureSitemap($custom);
        }

        $lines = ['User-agent: *'];
        foreach ($this->disallowedPaths() as $path) {
            $lines[] = 'Disallow: ' . $path;
        }

        $body = implode("\n", $lines);

        return $this->ensureSitemap($body);
    }

    /**
     * The paths to disallow: the live cart/checkout/account/search page URLs plus any configured extras.
     *
     * @return array<int, string>
     */
    private function disallowedPaths(): array
    {
        $paths = [];

        $classes = (array) Config::inst()->get(SeoSiteTreeExtension::class, 'noindex_page_classes');
        foreach ($classes as $class) {
            if (!is_string($class) || $class === '' || !class_exists($class)) {
                continue;
            }
            $page = DataObject::get($class)->first();
            if ($page === null || !$page->hasMethod('Link')) {
                continue;
            }
            $link = (string) call_user_func([$page, 'Link']);
            $paths[] = $this->normalisePath($link);
        }

        foreach ((array) $this->config()->get('disallow') as $extra) {
            if (is_string($extra) && $extra !== '') {
                $paths[] = $this->normalisePath($extra);
            }
        }

        return array_values(array_unique(array_filter($paths, static fn (string $p): bool => $p !== '' && $p !== '/')));
    }

    private function normalisePath(string $link): string
    {
        // Reduce an absolute link to its path, then guarantee a single leading slash.
        $path = parse_url($link, PHP_URL_PATH);
        $path = is_string($path) ? $path : $link;

        return '/' . ltrim($path, '/');
    }

    private function ensureSitemap(string $body): string
    {
        if (!$this->config()->get('include_sitemap')) {
            return $body . "\n";
        }

        if (stripos($body, 'Sitemap:') !== false) {
            return $body . "\n";
        }

        $sitemap = (string) Director::absoluteURL((string) $this->config()->get('sitemap_path'));

        return rtrim($body) . "\n\nSitemap: " . $sitemap . "\n";
    }
}
