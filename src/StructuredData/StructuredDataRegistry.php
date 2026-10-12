<?php

declare(strict_types=1);

namespace SilverShop\Seo\StructuredData;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injector;

/**
 * Registry of the structured-data providers assembled into the page @graph. Providers are declared in config
 * (name => Injector service), so each one plugs in without {@link JsonLdGraphService} knowing about it:
 *
 *   SilverShop\Seo\StructuredData\StructuredDataRegistry:
 *     providers:
 *       organization: '%$SilverShop\Seo\StructuredData\Provider\OrganizationProvider'
 *       product: '%$SilverShop\Seo\StructuredData\Provider\ProductProvider'
 *
 * Config order is the order the providers run (and thus the order their nodes first appear in the graph).
 */
class StructuredDataRegistry
{
    use Configurable;

    /**
     * @var array<string, string> provider name => Injector service name / class
     */
    private static array $providers = [];

    /**
     * All registered providers, in config order, keyed by name.
     *
     * @return array<string, StructuredDataProvider>
     */
    public function all(): array
    {
        $providers = [];
        foreach ((array) self::config()->get('providers') as $service) {
            if (!$service) {
                continue;
            }
            $provider = Injector::inst()->get($service);
            if ($provider instanceof StructuredDataProvider) {
                $providers[$provider->getName()] = $provider;
            }
        }

        return $providers;
    }
}
