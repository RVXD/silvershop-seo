<?php

namespace SilverShop\Seo\Tests\Stub;

use SilverShop\Seo\StructuredData\SeoContext;
use SilverShop\Seo\StructuredData\StructuredDataProvider;
use SilverStripe\Dev\TestOnly;

/**
 * A minimal provider that contributes a Product node. The name carries {@code <b>&</b>} so the XSS-hardening of the
 * JSON encoding can be asserted.
 */
class StubProductProvider implements StructuredDataProvider, TestOnly
{
    public function getName(): string
    {
        return 'stub-product';
    }

    public function appliesTo(SeoContext $context): bool
    {
        return true;
    }

    public function getNodes(SeoContext $context): array
    {
        return [[
            '@type' => 'Product',
            '@id' => $context->nodeId('product'),
            'name' => 'Jacket <b>&</b>',
            'offers' => [
                '@type' => 'Offer',
                'price' => '19.99',
                'priceCurrency' => 'EUR',
            ],
        ]];
    }
}
