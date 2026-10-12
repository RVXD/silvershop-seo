<?php

declare(strict_types=1);

namespace SilverShop\Seo\StructuredData;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;

/**
 * Assembles one coherent JSON-LD {@code @graph} for a page from the registered {@link StructuredDataProvider}s.
 *
 * Each provider contributes schema.org node arrays; nodes that share an {@code @id} are merged (so an enrichment
 * fragment — e.g. the reviews adapter's {@code aggregateRating} — lands inside the node another provider created,
 * rather than producing a second, competing node). The result is a single
 * {@code {"@context":"https://schema.org","@graph":[...]}} document, XSS-safely encoded for a {@code <script>}.
 */
class JsonLdGraphService
{
    use Injectable;

    /**
     * Build the full graph document for a context, or {@code []} when nothing applies.
     *
     * @return array<string, mixed>
     */
    public function graphFor(SeoContext $context): array
    {
        $registry = Injector::inst()->get(StructuredDataRegistry::class);

        /** @var array<string|int, array<string, mixed>> $nodes */
        $nodes = [];
        foreach ($registry->all() as $provider) {
            if (!$provider->appliesTo($context)) {
                continue;
            }
            foreach ($provider->getNodes($context) as $node) {
                if (!is_array($node) || $node === []) {
                    continue;
                }
                $id = isset($node['@id']) && is_string($node['@id']) ? $node['@id'] : null;
                if ($id !== null && isset($nodes[$id])) {
                    $nodes[$id] = $this->mergeNode($nodes[$id], $node);
                } elseif ($id !== null) {
                    $nodes[$id] = $node;
                } else {
                    $nodes[] = $node;
                }
            }
        }

        // Drop any @id-only fragments that never got merged into a real (typed) node.
        $graph = array_values(array_filter(
            array_values($nodes),
            static fn (array $node): bool => isset($node['@type'])
        ));

        if ($graph === []) {
            return [];
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ];
    }

    /**
     * The graph encoded as a JSON string ready to drop into a {@code <script type="application/ld+json">}, or '' when
     * the graph is empty. {@code JSON_HEX_TAG|JSON_HEX_AMP} escape {@code < > &} so content can't break out of the
     * script block (stored XSS) — the same hardening the reviews module uses.
     */
    public function jsonFor(SeoContext $context): string
    {
        $graph = $this->graphFor($context);
        if ($graph === []) {
            return '';
        }

        $json = json_encode(
            $graph,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP
        );

        return $json !== false ? $json : '';
    }

    /**
     * Recursively merge node $b into node $a: scalars from $b win, associative maps recurse, and sequential lists
     * (e.g. {@code review[]}, {@code image[]}) concatenate.
     *
     * @param array<string, mixed> $a
     * @param array<string, mixed> $b
     * @return array<string, mixed>
     */
    private function mergeNode(array $a, array $b): array
    {
        foreach ($b as $key => $value) {
            if (!array_key_exists($key, $a)) {
                $a[$key] = $value;
                continue;
            }

            if (is_array($a[$key]) && is_array($value)) {
                if (array_is_list($a[$key]) && array_is_list($value)) {
                    $a[$key] = array_merge($a[$key], $value);
                } else {
                    $a[$key] = $this->mergeNode($a[$key], $value);
                }
                continue;
            }

            $a[$key] = $value;
        }

        return $a;
    }
}
