<?php

declare(strict_types=1);

namespace SilverShop\Seo\StructuredData;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataObject;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * The per-request context handed to every {@link StructuredDataProvider} and used by the meta layer. Carries the
 * current page/record, the active controller (when any) and the page's absolute URL, and mints the stable
 * {@code @id}s that let graph nodes cross-reference one another instead of duplicating.
 */
class SeoContext
{
    use Injectable;

    public function __construct(
        private ?DataObject $record = null,
        private ?Controller $controller = null,
        private string $url = ''
    ) {
    }

    public function getRecord(): ?DataObject
    {
        return $this->record;
    }

    public function getController(): ?Controller
    {
        return $this->controller;
    }

    /**
     * The canonical absolute URL of the current page: the record's own {@code AbsoluteLink()} when available (so the
     * {@code @id}s stay stable and query-string-free), else the request URL, else the site base URL.
     */
    public function getPageUrl(): string
    {
        if ($this->record && $this->record->hasMethod('AbsoluteLink')) {
            $link = $this->record->AbsoluteLink();
            if (is_string($link) && $link !== '') {
                return $link;
            }
        }

        return $this->url !== '' ? $this->url : $this->getBaseUrl();
    }

    public function getBaseUrl(): string
    {
        return Director::absoluteBaseURL();
    }

    /**
     * A page-scoped node {@code @id}, e.g. {@code https://shop.test/jacket/#product}.
     */
    public function nodeId(string $fragment): string
    {
        return rtrim($this->getPageUrl(), '/') . '/#' . ltrim($fragment, '#');
    }

    /**
     * A site-scoped node {@code @id}, e.g. {@code https://shop.test/#organization}. Shared by every page so the
     * Organization / WebSite nodes are referenced, not repeated.
     */
    public function siteId(string $fragment): string
    {
        return rtrim($this->getBaseUrl(), '/') . '/#' . ltrim($fragment, '#');
    }

    public function getSiteConfig(): SiteConfig
    {
        return SiteConfig::current_site_config();
    }
}
