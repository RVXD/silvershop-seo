<?php

namespace SilverShop\Seo\Tests\Stub;

use SilverShop\Seo\StructuredData\SeoContext;
use SilverShop\Seo\StructuredData\StructuredDataProvider;
use SilverStripe\Dev\TestOnly;

/**
 * A provider that never applies: its node must not appear in the graph.
 */
class StubInapplicableProvider implements StructuredDataProvider, TestOnly
{
    public function getName(): string
    {
        return 'stub-inapplicable';
    }

    public function appliesTo(SeoContext $context): bool
    {
        return false;
    }

    public function getNodes(SeoContext $context): array
    {
        return [[
            '@type' => 'Thing',
            '@id' => $context->nodeId('thing'),
            'name' => 'should-not-appear',
        ]];
    }
}
