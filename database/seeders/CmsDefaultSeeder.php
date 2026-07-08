<?php

namespace Database\Seeders;

use App\Models\AboutContent;
use App\Models\AboutStat;
use App\Models\AboutValue;
use App\Models\FaqItem;
use App\Models\HomepageSection;
use App\Models\PolicyPage;
use App\Models\TrustStripItem;
use Illuminate\Database\Seeder;

class CmsDefaultSeeder extends Seeder
{
    /**
     * Seed default CMS content.
     */
    public function run(): void
    {
        $sections = [
            ['key' => 'hero', 'title' => 'AFIFI', 'subtitle' => 'Everyday essentials with a refined fit.', 'sort_order' => 0],
            ['key' => 'featured-products', 'title' => 'Featured Products', 'subtitle' => 'Selected pieces from the latest collection.', 'sort_order' => 1],
            ['key' => 'new-arrivals', 'title' => 'New Arrivals', 'subtitle' => 'Fresh styles just added.', 'sort_order' => 2],
        ];

        foreach ($sections as $section) {
            HomepageSection::updateOrCreate(
                ['key' => $section['key']],
                [
                    'title' => $section['title'],
                    'subtitle' => $section['subtitle'],
                    'body' => null,
                    'media_id' => null,
                    'cta_text' => null,
                    'cta_url' => null,
                    'config' => null,
                    'sort_order' => $section['sort_order'],
                    'is_active' => true,
                ],
            );
        }

        $trustItems = [
            ['number_label' => '01', 'title' => 'Secure Checkout', 'description' => 'Safe checkout experience for every order.'],
            ['number_label' => '02', 'title' => 'Fast Shipping', 'description' => 'Reliable delivery across supported zones.'],
            ['number_label' => '03', 'title' => 'Easy Returns', 'description' => 'Clear return and exchange process.'],
        ];

        foreach ($trustItems as $index => $item) {
            TrustStripItem::updateOrCreate(
                ['number_label' => $item['number_label']],
                [
                    'title' => $item['title'],
                    'description' => $item['description'],
                    'sort_order' => $index,
                    'is_active' => true,
                ],
            );
        }

        AboutContent::updateOrCreate(
            ['id' => 1],
            [
                'story_media_id' => null,
                'story_title' => 'About AFIFI',
                'story_body' => 'AFIFI creates everyday apparel focused on comfort, quality, and clean design.',
            ],
        );

        $values = [
            ['title' => 'Quality', 'description' => 'Built around reliable materials and thoughtful finishing.'],
            ['title' => 'Comfort', 'description' => 'Designed for everyday movement and effortless wear.'],
            ['title' => 'Trust', 'description' => 'Clear service, dependable delivery, and consistent support.'],
        ];

        foreach ($values as $index => $value) {
            AboutValue::updateOrCreate(
                ['title' => $value['title']],
                [
                    'description' => $value['description'],
                    'sort_order' => $index,
                    'is_active' => true,
                ],
            );
        }

        $stats = [
            ['label' => 'Years', 'value' => '0+'],
            ['label' => 'Customers', 'value' => '0+'],
            ['label' => 'Products', 'value' => '0+'],
        ];

        foreach ($stats as $index => $stat) {
            AboutStat::updateOrCreate(
                ['label' => $stat['label']],
                [
                    'value' => $stat['value'],
                    'sort_order' => $index,
                    'is_active' => true,
                ],
            );
        }

        $faqs = [
            ['question' => 'How long does shipping take?', 'answer' => 'Shipping time depends on your governorate and selected shipping zone.'],
            ['question' => 'Can I return or exchange an item?', 'answer' => 'Return and exchange requests are reviewed according to the published return policy.'],
            ['question' => 'What payment methods are available?', 'answer' => 'Available payment methods are shown during checkout.'],
        ];

        foreach ($faqs as $index => $faq) {
            FaqItem::updateOrCreate(
                ['question' => $faq['question']],
                [
                    'answer' => $faq['answer'],
                    'sort_order' => $index,
                    'is_active' => true,
                ],
            );
        }

        $pages = [
            ['slug' => 'privacy-policy', 'title' => 'Privacy Policy'],
            ['slug' => 'terms-and-conditions', 'title' => 'Terms and Conditions'],
            ['slug' => 'return-policy', 'title' => 'Return Policy'],
            ['slug' => 'shipping-policy', 'title' => 'Shipping Policy'],
        ];

        foreach ($pages as $page) {
            PolicyPage::updateOrCreate(
                ['slug' => $page['slug']],
                [
                    'title' => $page['title'],
                    'content' => 'This page content will be updated by the AFIFI admin team.',
                ],
            );
        }
    }
}
