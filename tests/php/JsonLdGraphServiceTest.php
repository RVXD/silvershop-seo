<?php

namespace SilverShop\Seo\Tests;

use SilverShop\Seo\StructuredData\JsonLdGraphService;
use SilverShop\Seo\StructuredData\SeoContext;
use SilverShop\Seo\StructuredData\StructuredDataRegistry;
use SilverShop\Seo\Tests\Stub\StubInapplicableProvider;
use SilverShop\Seo\Tests\Stub\StubOrphanFragmentProvider;
use SilverShop\Seo\Tests\Stub\StubProductProvider;
use SilverShop\Seo\Tests\Stub\StubRatingFragmentProvider;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;

/**
 * The @graph assembly: providers are driven by the registry, fragments merge into nodes by @id, inapplicable
 * providers and orphan fragments are dropped, and the output is XSS-hardened JSON.
 */
class JsonLdGraphServiceTest extends SapphireTest
{
    protected $usesDatabase = false;

    private function context(): SeoContext
    {
        return SeoContext::create(null, null, 'http://seo.test/jacket/');
    }

    /**
     * @param array<string, string> $providers
     */
    private function useProviders(array $providers): void
    {
        Config::modify()->set(StructuredDataRegistry::class, 'providers', $providers);
    }

    public function testFragmentMergesIntoNodeBySharedId(): void
    {
        $this->useProviders([
            'product' => StubProductProvider::class,
            'rating' => StubRatingFragmentProvider::class,
        ]);

        $graph = JsonLdGraphService::singleton()->graphFor($this->context());

        $this->assertSame('https://schema.org', $graph['@context']);
        // Two providers, one shared @id => a single merged node.
        $this->assertCount(1, $graph['@graph']);

        $node = $graph['@graph'][0];
        $this->assertSame('Product', $node['@type']);
        $this->assertArrayHasKey('offers', $node);
        $this->assertArrayHasKey('aggregateRating', $node);
        $this->assertSame('4.5', $node['aggregateRating']['ratingValue']);
        $this->assertSame('http://seo.test/jacket/#product', $node['@id']);
    }

    public function testInapplicableProviderIsSkipped(): void
    {
        $this->useProviders([
            'skip' => StubInapplicableProvider::class,
            'product' => StubProductProvider::class,
        ]);

        $graph = JsonLdGraphService::singleton()->graphFor($this->context());

        $this->assertCount(1, $graph['@graph']);
        $this->assertSame('Product', $graph['@graph'][0]['@type']);
    }

    public function testOrphanFragmentIsDropped(): void
    {
        $this->useProviders(['orphan' => StubOrphanFragmentProvider::class]);

        // A fragment that matches no typed node yields no graph at all.
        $this->assertSame([], JsonLdGraphService::singleton()->graphFor($this->context()));
    }

    public function testEmptyGraphYieldsEmptyJson(): void
    {
        $this->useProviders([]);

        $this->assertSame('', JsonLdGraphService::singleton()->jsonFor($this->context()));
    }

    public function testJsonIsXssHardened(): void
    {
        $this->useProviders(['product' => StubProductProvider::class]);

        $json = JsonLdGraphService::singleton()->jsonFor($this->context());

        $this->assertStringContainsString('"@graph"', $json);
        // < > & are escaped so the payload can't break out of a <script> block.
        $this->assertStringNotContainsString('<', $json);
        $this->assertStringContainsString('\\u003C', $json);
        $this->assertStringContainsString('\\u0026', $json);
        // Slashes in URLs stay readable (not escaped to \/).
        $this->assertStringContainsString('http://seo.test/jacket/#product', $json);
    }
}
