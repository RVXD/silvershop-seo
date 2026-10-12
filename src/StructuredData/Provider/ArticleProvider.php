<?php

declare(strict_types=1);

namespace SilverShop\Seo\StructuredData\Provider;

use SilverShop\Seo\StructuredData\SeoContext;
use SilverStripe\Assets\Image;
use SilverStripe\ORM\DataObject;

/**
 * Forward-looking, guarded provider for {@code silverstripe/blog}. Emits a {@code BlogPosting} node for a blog post.
 * Dormant (never registered-effective) unless the blog module is installed — it is shipped now so the company-website
 * theme's blog lights up automatically when the dep arrives. Field access is defensive for the same reason.
 */
class ArticleProvider extends AbstractStructuredDataProvider
{
    private const BLOGPOST_CLASS = 'SilverStripe\\Blog\\Model\\BlogPost';

    public function getName(): string
    {
        return 'article';
    }

    public function appliesTo(SeoContext $context): bool
    {
        $class = self::BLOGPOST_CLASS;
        if (!class_exists($class)) {
            return false;
        }
        $record = $context->getRecord();

        return $record instanceof $class;
    }

    public function getNodes(SeoContext $context): array
    {
        $record = $context->getRecord();
        if (!$record instanceof DataObject) {
            return [];
        }

        $node = $this->prune([
            '@type' => 'BlogPosting',
            '@id' => $context->nodeId('article'),
            'headline' => (string) $record->getField('Title'),
            'description' => (string) $record->getField('MetaDescription'),
            'datePublished' => $this->isoDate($record->getField('PublishDate')),
            'dateModified' => $this->isoDate($record->getField('LastEdited')),
            'author' => $this->author($record),
            'image' => $this->featuredImage($record),
            'url' => $context->getPageUrl(),
            'mainEntityOfPage' => $context->getPageUrl(),
            'publisher' => ['@id' => $context->siteId('organization')],
        ]);

        return [$node];
    }

    /**
     * @return array<string, string>
     */
    private function author(DataObject $record): array
    {
        $names = (string) ($record->getField('AuthorNames') ?: $record->getField('Credits'));
        if (trim($names) === '') {
            return [];
        }

        return ['@type' => 'Person', 'name' => $names];
    }

    private function featuredImage(DataObject $record): string
    {
        $image = $this->callMethod($record, 'FeaturedImage');
        if ($image instanceof Image && $image->exists()) {
            return (string) $image->getAbsoluteURL();
        }

        return '';
    }
}
