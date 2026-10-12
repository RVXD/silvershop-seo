<?php

namespace SilverShop\Seo\Tests\Stub;

use SilverShop\Seo\StructuredData\SeoContext;
use SilverShop\Seo\StructuredData\StructuredDataProvider;
use SilverStripe\Dev\TestOnly;

/**
 * A provider that returns only an {@code @id}-keyed fragment (no {@code @type}) whose {@code @id} matches no other
 * node. The graph service must drop it rather than emit a typeless node.
 */
class StubOrphanFragmentProvider implements StructuredDataProvider, TestOnly
{
    public function getName(): string
    {
        return 'stub-orphan';
    }

    public function appliesTo(SeoContext $context): bool
    {
        return true;
    }

    public function getNodes(SeoContext $context): array
    {
        return [[
            '@id' => $context->nodeId('ghost'),
            'aggregateRating' => [
                '@type' => 'AggregateRating',
                'ratingValue' => '3.0',
                'reviewCount' => 1,
            ],
        ]];
    }
}
