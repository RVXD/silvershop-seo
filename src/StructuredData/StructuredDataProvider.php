<?php

declare(strict_types=1);

namespace SilverShop\Seo\StructuredData;

/**
 * One contributor to the page's JSON-LD {@link https://schema.org} @graph. The seo module ships the intrinsic
 * providers (Organization/WebSite, Product/Offer, category ItemList) and a set of guarded, seo-side adapters that
 * read other modules' data (reviews, brands, blog, events) WITHOUT editing them. Each provider is registered into
 * {@link StructuredDataRegistry} in config, so a shop (or a future sibling module) can add its own.
 *
 * A provider returns schema.org node arrays. A node that carries an {@code @id} already minted by another provider
 * is MERGED into that node by {@link JsonLdGraphService} — that is how the reviews adapter adds an
 * {@code aggregateRating} to the Product node, or the brand adapter adds a {@code brand}, instead of emitting a
 * second, competing node. Use {@link SeoContext::nodeId()} / {@link SeoContext::siteId()} so every provider agrees
 * on the same {@code @id}s.
 */
interface StructuredDataProvider
{
    /**
     * Machine name of this provider (also the registry key), e.g. 'product'.
     */
    public function getName(): string;

    /**
     * Whether this provider contributes anything for the given context (right page/record type, its module present,
     * its data non-empty). Providers that don't apply are skipped.
     */
    public function appliesTo(SeoContext $context): bool;

    /**
     * The schema.org nodes (or @id-keyed fragments to merge) this provider contributes for the context.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getNodes(SeoContext $context): array;
}
