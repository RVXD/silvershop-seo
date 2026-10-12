<?php

declare(strict_types=1);

namespace SilverShop\Seo\StructuredData\Provider;

use SilverShop\Seo\StructuredData\SeoContext;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\ORM\DataObject;

/**
 * Forward-looking, guarded provider for an events page type. Emits an {@code Event} node. There is no single
 * canonical events module in the suite yet, so the page classes that count as events are config-driven and the
 * default is empty — the provider is inert until a shop lists its event page class:
 *
 *   SilverShop\Seo\StructuredData\Provider\EventProvider:
 *     event_classes:
 *       - My\Events\EventPage
 *     field_map:
 *       startDate: StartDate
 *       endDate: EndDate
 *       location: Location
 *
 * Field access is defensive (getField), so a partial mapping still produces a valid, minimal node.
 */
class EventProvider extends AbstractStructuredDataProvider
{
    use Configurable;

    /**
     * Page classes treated as events (exact ClassName match). Empty = provider inert.
     *
     * @var array<int, string>
     */
    private static array $event_classes = [];

    /**
     * Map of schema.org property => source field name on the event record.
     *
     * @var array<string, string>
     */
    private static array $field_map = [
        'startDate' => 'StartDate',
        'endDate' => 'EndDate',
        'location' => 'Location',
    ];

    public function getName(): string
    {
        return 'event';
    }

    public function appliesTo(SeoContext $context): bool
    {
        $record = $context->getRecord();
        if (!$record instanceof DataObject) {
            return false;
        }

        foreach ((array) self::config()->get('event_classes') as $class) {
            if (is_string($class) && $class !== '' && class_exists($class) && $record instanceof $class) {
                return true;
            }
        }

        return false;
    }

    public function getNodes(SeoContext $context): array
    {
        $record = $context->getRecord();
        if (!$record instanceof DataObject) {
            return [];
        }

        $map = (array) self::config()->get('field_map');
        $startField = (string) ($map['startDate'] ?? 'StartDate');
        $endField = (string) ($map['endDate'] ?? 'EndDate');
        $locationField = (string) ($map['location'] ?? 'Location');

        $node = $this->prune([
            '@type' => 'Event',
            '@id' => $context->nodeId('event'),
            'name' => (string) $record->getField('Title'),
            'description' => (string) $record->getField('MetaDescription'),
            'startDate' => $this->isoDate($record->getField($startField)),
            'endDate' => $this->isoDate($record->getField($endField)),
            'location' => $this->location((string) $record->getField($locationField)),
            'url' => $context->getPageUrl(),
        ]);

        return [$node];
    }

    /**
     * @return array<string, string>
     */
    private function location(string $name): array
    {
        if (trim($name) === '') {
            return [];
        }

        return ['@type' => 'Place', 'name' => $name];
    }
}
