<?php

declare(strict_types=1);

namespace SilverShop\Seo\Extension;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;

/**
 * Site-wide SEO / social defaults on {@link \SilverStripe\SiteConfig\SiteConfig}: the fallback social-share image,
 * the Twitter site handle, the organization's social-profile URLs (schema.org {@code sameAs}) and an optional
 * hand-written robots.txt body. All optional; everything degrades sanely when blank.
 *
 * @extends Extension<\SilverStripe\SiteConfig\SiteConfig>
 * @property string $TwitterSiteHandle
 * @property string $OrganizationSameAs
 * @property string $CustomRobotsTxt
 * @property int $SeoDefaultShareImageID
 * @method Image SeoDefaultShareImage()
 */
class SeoSiteConfigExtension extends Extension
{
    private static array $db = [
        'TwitterSiteHandle' => 'Varchar(100)',
        'OrganizationSameAs' => 'Text',
        'CustomRobotsTxt' => 'Text',
    ];

    private static array $has_one = [
        'SeoDefaultShareImage' => Image::class,
    ];

    private static array $owns = [
        'SeoDefaultShareImage',
    ];

    protected function updateCMSFields(FieldList $fields): void
    {
        $fields->addFieldsToTab('Root.SEO', [
            HeaderField::create('SeoSocialHeader', _t(self::class . '.SocialHeader', 'Social sharing defaults')),
            $image = UploadField::create(
                'SeoDefaultShareImage',
                _t(self::class . '.DefaultShareImage', 'Default share image')
            ),
            TextField::create('TwitterSiteHandle', _t(self::class . '.TwitterSiteHandle', 'Twitter / X site handle'))
                ->setDescription(_t(
                    self::class . '.TwitterSiteHandleDesc',
                    'The site @handle for Twitter/X cards, e.g. @myshop.'
                )),
            TextareaField::create(
                'OrganizationSameAs',
                _t(self::class . '.OrganizationSameAs', 'Social profile URLs')
            )->setDescription(_t(
                self::class . '.OrganizationSameAsDesc',
                'One URL per line (Facebook, Instagram, LinkedIn, …). Emitted as the organization sameAs in structured data.'
            )),
            HeaderField::create('SeoRobotsHeader', _t(self::class . '.RobotsHeader', 'robots.txt')),
            TextareaField::create('CustomRobotsTxt', _t(self::class . '.CustomRobotsTxt', 'Custom robots.txt'))
                ->setDescription(_t(
                    self::class . '.CustomRobotsTxtDesc',
                    'Optional. Leave blank to serve the generated default (sitemap reference + cart/checkout/account disallow).'
                )),
        ]);

        $image->setFolderName('seo');
        $image->getValidator()->setAllowedExtensions(['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp']);
    }

    /**
     * Social-profile URLs as a clean list.
     *
     * @return array<int, string>
     */
    public function OrganizationSameAsList(): array
    {
        $raw = (string) $this->getOwner()->getField('OrganizationSameAs');
        if (trim($raw) === '') {
            return [];
        }

        $urls = preg_split('/\r\n|\r|\n/', $raw) ?: [];

        return array_values(array_filter(array_map('trim', $urls), static fn (string $v): bool => $v !== ''));
    }
}
