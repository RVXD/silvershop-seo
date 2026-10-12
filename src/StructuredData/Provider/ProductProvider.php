<?php

declare(strict_types=1);

namespace SilverShop\Seo\StructuredData\Provider;

use SilverShop\Extension\ShopConfigExtension;
use SilverShop\Page\Product;
use SilverShop\Seo\StructuredData\SeoContext;
use SilverStripe\Core\Config\Configurable;

/**
 * The intrinsic Product provider: a {@code Product} node with a nested {@code Offer} (price, currency, availability,
 * SKU, condition, seller) and a {@code BreadcrumbList}. This is the single, complete Product node that replaces
 * core's partial microdata and the reviews module's competing JSON-LD Product; the reviews / brands / gallery
 * adapters enrich THIS node (by {@code @id}) rather than emitting their own.
 */
class ProductProvider extends AbstractStructuredDataProvider
{
    use Configurable;

    /**
     * schema.org itemCondition for the Offer. Override per shop (e.g. UsedCondition) if needed.
     */
    private static string $item_condition = 'https://schema.org/NewCondition';

    /**
     * strtotime() expression for the Offer priceValidUntil (Google recommends one). Blank = omit.
     */
    private static string $price_valid_period = '+1 year';

    /**
     * Max images to list on the Product node.
     */
    private static int $image_limit = 6;

    public function getName(): string
    {
        return 'product';
    }

    public function appliesTo(SeoContext $context): bool
    {
        return $context->getRecord() instanceof Product;
    }

    public function getNodes(SeoContext $context): array
    {
        $record = $context->getRecord();
        if (!$record instanceof Product) {
            return [];
        }

        $node = $this->prune([
            '@type' => 'Product',
            '@id' => $context->nodeId('product'),
            'name' => (string) $record->Title,
            'description' => $this->description($record),
            'sku' => (string) $record->InternalItemID,
            'url' => $context->getPageUrl(),
            'image' => $this->imageUrls($record, (int) self::config()->get('image_limit')),
            'offers' => $this->offer($record, $context),
        ]);

        $nodes = [$node];

        $breadcrumbs = $this->buildBreadcrumbs($record, $context);
        if ($breadcrumbs !== []) {
            $nodes[] = $breadcrumbs;
        }

        return $nodes;
    }

    /**
     * @return array<string, mixed>
     */
    private function offer(Product $product, SeoContext $context): array
    {
        $price = (float) $product->sellingPrice();
        $currency = ShopConfigExtension::get_site_currency();

        $availability = 'https://schema.org/InStock';
        if ($product->hasMethod('hasAvailableStock')) {
            $availability = $this->callMethod($product, 'hasAvailableStock', true)
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock';
        }

        $offer = [
            '@type' => 'Offer',
            'price' => number_format($price, 2, '.', ''),
            'priceCurrency' => $currency,
            'availability' => $availability,
            'itemCondition' => (string) self::config()->get('item_condition'),
            'url' => $context->getPageUrl(),
            'seller' => ['@id' => $context->siteId('organization')],
        ];

        $period = trim((string) self::config()->get('price_valid_period'));
        if ($period !== '') {
            $timestamp = strtotime($period);
            if ($timestamp !== false) {
                $offer['priceValidUntil'] = date('Y-m-d', $timestamp);
            }
        }

        return $offer;
    }

    private function description(Product $product): string
    {
        $meta = trim((string) $product->MetaDescription);
        if ($meta !== '') {
            return $meta;
        }

        $content = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) $product->Content)));
        if (mb_strlen($content) > 300) {
            $content = rtrim(mb_substr($content, 0, 300)) . '…';
        }

        return $content;
    }
}
