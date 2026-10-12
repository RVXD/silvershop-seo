<?php

namespace SilverShop\Seo\Tests;

use SilverShop\Seo\StructuredData\Provider\OrganizationProvider;
use SilverShop\Seo\StructuredData\SeoContext;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * The site-level Organization + WebSite nodes, falling back to the SiteConfig title + base URL when store-profile is
 * not installed (as in the test deps).
 */
class OrganizationProviderTest extends SapphireTest
{
    protected $usesDatabase = true;

    /**
     * @param array<int, array<string, mixed>> $nodes
     * @return array<string, mixed>|null
     */
    private function nodeOfType(array $nodes, string $type): ?array
    {
        foreach ($nodes as $node) {
            if (($node['@type'] ?? null) === $type) {
                return $node;
            }
        }

        return null;
    }

    public function testAlwaysApplies(): void
    {
        $this->assertTrue((new OrganizationProvider())->appliesTo(SeoContext::create()));
    }

    public function testOrganizationAndWebsiteNodes(): void
    {
        $config = SiteConfig::current_site_config();
        $config->Title = 'My Shop';
        $config->write();

        $context = SeoContext::create(null, null, 'http://seo.test/');
        $nodes = (new OrganizationProvider())->getNodes($context);

        $organization = $this->nodeOfType($nodes, 'Organization');
        $this->assertNotNull($organization);
        $this->assertSame('My Shop', $organization['name']);
        $this->assertStringEndsWith('#organization', $organization['@id']);

        $website = $this->nodeOfType($nodes, 'WebSite');
        $this->assertNotNull($website);
        $this->assertSame('My Shop', $website['name']);
        $this->assertSame(['@id' => $context->siteId('organization')], $website['publisher']);
    }
}
