<?php

namespace SilverShop\Seo\Tests\Stub;

use SilverShop\Seo\StructuredData\SeoContext;
use SilverShop\Seo\StructuredData\StructuredDataProvider;
use SilverStripe\Dev\TestOnly;

/**
 * An enrichment provider that returns an {@code @id}-keyed fragment (no {@code @type}) carrying an aggregateRating —
 * exactly the shape the reviews adapter uses to merge a rating INTO the Product node.
 */
class StubRatingFragmentProvider implements StructuredDataProvider, TestOnly
{
    public function getName(): string
    {
        return 'stub-rating';
    }

    public function appliesTo(SeoContext $context): bool
    {
        return true;
    }

    public function getNodes(SeoContext $context): array
    {
        return [[
            '@id' => $context->nodeId('product'),
            'aggregateRating' => [
                '@type' => 'AggregateRating',
                'ratingValue' => '4.5',
                'reviewCount' => 10,
            ],
        ]];
    }
}
