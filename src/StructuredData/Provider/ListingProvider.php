<?php

declare(strict_types=1);

namespace SilverShop\Seo\StructuredData\Provider;

use SilverShop\Page\ProductCategory;
use SilverShop\Seo\StructuredData\SeoContext;
use SilverStripe\Core\Config\Configurable;

/**
 * The intrinsic listing provider: an {@code ItemList} of the products shown on a product category, plus a
 * {@code BreadcrumbList}. Gives category / collection pages a structured list for rich results.
 */
class ListingProvider extends AbstractStructuredDataProvider
{
    use Configurable;

    /**
     * Max products listed in the ItemList (category pages can hold many; the sitemap covers the rest).
     */
    private static int $item_limit = 50;

    public function getName(): string
    {
        return 'listing';
    }

    public function appliesTo(SeoContext $context): bool
    {
        return $context->getRecord() instanceof ProductCategory;
    }

    public function getNodes(SeoContext $context): array
    {
        $record = $context->getRecord();
        if (!$record instanceof ProductCategory) {
            return [];
        }

        $nodes = [];

        $elements = [];
        $position = 1;
        $limit = max(0, (int) self::config()->get('item_limit'));
        foreach ($record->ProductsShowable() as $product) {
            $link = (string) $this->callMethod($product, 'AbsoluteLink');
            if ($link === '') {
                continue;
            }
            $elements[] = $this->prune([
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => (string) $product->getField('Title'),
                'url' => $link,
            ]);

            if ($limit > 0 && count($elements) >= $limit) {
                break;
            }
        }

        if ($elements !== []) {
            $nodes[] = [
                '@type' => 'ItemList',
                '@id' => $context->nodeId('itemlist'),
                'name' => (string) $record->Title,
                'url' => $context->getPageUrl(),
                'numberOfItems' => count($elements),
                'itemListElement' => $elements,
            ];
        }

        $breadcrumbs = $this->buildBreadcrumbs($record, $context);
        if ($breadcrumbs !== []) {
            $nodes[] = $breadcrumbs;
        }

        return $nodes;
    }
}
