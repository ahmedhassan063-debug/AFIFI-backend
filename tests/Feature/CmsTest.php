<?php

namespace Tests\Feature;

use App\Models\FaqItem;
use App\Models\PolicyPage;
use Tests\TestCase;

class CmsTest extends TestCase
{
    public function test_unknown_policy_slug_returns_404(): void
    {
        $response = $this->getJson('/api/cms/policies/does-not-exist');

        $response->assertNotFound();
        $response->assertJsonPath('message', 'Policy page not found.');
    }

    public function test_known_policy_slug_returns_content(): void
    {
        PolicyPage::query()->create([
            'slug' => 'privacy',
            'title' => 'Privacy Policy',
            'content' => 'Privacy content.',
        ]);

        $response = $this->getJson('/api/cms/policies/privacy');

        $response->assertOk();
        $response->assertJsonPath('data.slug', 'privacy');
        $response->assertJsonPath('data.title', 'Privacy Policy');
    }

    public function test_faq_endpoint_hides_inactive_items(): void
    {
        FaqItem::query()->create([
            'question' => 'Visible question',
            'answer' => 'Visible answer',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        FaqItem::query()->create([
            'question' => 'Hidden question',
            'answer' => 'Hidden answer',
            'sort_order' => 2,
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/cms/faq');

        $response->assertOk();
        $response->assertJsonCount(1, 'items');
        $response->assertJsonPath('items.0.question', 'Visible question');
    }
}
