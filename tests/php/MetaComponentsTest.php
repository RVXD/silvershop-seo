<?php

namespace SilverShop\Seo\Tests;

use Page;
use SilverShop\Extension\ShopConfigExtension;
use SilverShop\Page\CheckoutPage;
use SilverShop\Page\Product;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;

/**
 * The meta layer injected through updateMetaComponents: canonical, robots (with the forced-noindex page types),
 * OpenGraph/Twitter, the product price OG tags, and the JSON-LD script descriptor.
 */
class MetaComponentsTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected static bool $use_draft_site = true;

    protected function setUp(): void
    {
        parent::setUp();

        Config::modify()->set(Product::class, 'global_allow_purchase', true);
        Config::modify()->set(ShopConfigExtension::class, 'base_currency', 'EUR');
    }

    public function testProductMetaComponents(): void
    {
        $product = Product::create([
            'Title' => 'Rain Jacket',
            'BasePrice' => 89.95,
            'InternalItemID' => 'RJ-001',
            'MetaDescription' => 'Stay dry.',
        ]);
        $product->write();

        $tags = $product->MetaComponents();

        $this->assertSame($product->AbsoluteLink(), $tags['ssseoCanonical']['attributes']['href']);
        $this->assertSame('index,follow', $tags['ssseoRobots']['attributes']['content']);
        $this->assertSame('Rain Jacket', $tags['ssseoOg_og_title']['attributes']['content']);
        $this->assertSame('product', $tags['ssseoOg_og_type']['attributes']['content']);
        $this->assertSame('89.95', $tags['ssseoOg_product_price_amount']['attributes']['content']);
        $this->assertSame('EUR', $tags['ssseoOg_product_price_currency']['attributes']['content']);
        $this->assertSame('summary_large_image', $tags['ssseoTw_twitter_card']['attributes']['content']);

        $this->assertArrayHasKey('ssseoJsonLd', $tags);
        $this->assertSame('application/ld+json', $tags['ssseoJsonLd']['attributes']['type']);
        $this->assertStringContainsString('"@graph"', $tags['ssseoJsonLd']['content']);
        $this->assertStringContainsString('"Product"', $tags['ssseoJsonLd']['content']);
    }

    public function testMetaTitleOverride(): void
    {
        $page = Page::create(['Title' => 'Plain', 'MetaTitle' => 'Custom Title']);
        $page->write();

        $tags = $page->MetaComponents();

        $this->assertSame('Custom Title', $tags['title']['content']);
    }

    public function testNoindexFieldProducesNoindex(): void
    {
        $page = Page::create(['Title' => 'Hidden', 'RobotsIndex' => false, 'RobotsFollow' => false]);
        $page->write();

        $tags = $page->MetaComponents();

        $this->assertSame('noindex,nofollow', $tags['ssseoRobots']['attributes']['content']);
    }

    public function testCheckoutPageForcedNoindex(): void
    {
        $checkout = CheckoutPage::create(['Title' => 'Checkout']);
        $checkout->write();

        $tags = $checkout->MetaComponents();

        $this->assertSame('noindex,follow', $tags['ssseoRobots']['attributes']['content']);
        $this->assertFalse($checkout->isPageIndexable());
    }

    public function testCanonicalOverride(): void
    {
        $page = Page::create(['Title' => 'Alias', 'CanonicalURLOverride' => 'https://canonical.example/x']);
        $page->write();

        $tags = $page->MetaComponents();

        $this->assertSame('https://canonical.example/x', $tags['ssseoCanonical']['attributes']['href']);
    }
}
