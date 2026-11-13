<?php

namespace Tests\Feature;

use Tests\TestCase;

class SeoEndpointsTest extends TestCase
{
    public function test_sitemap_index_only_advertises_active_sitemaps(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('/sitemap-pages.xml', false)
            ->assertSee('/sitemap-posts.xml', false)
            ->assertSee('/sitemap-images.xml', false)
            ->assertDontSee('/sitemap-services.xml', false)
            ->assertDontSee('/sitemap-videos.xml', false);
    }

    public function test_pages_sitemap_excludes_missing_and_transactional_routes(): void
    {
        $response = $this->get('/sitemap-pages.xml');

        $response->assertOk()
            ->assertSee('/blog', false)
            ->assertDontSee('/ord-services', false)
            ->assertDontSee('/ho-tro-mo-khoa', false)
            ->assertDontSee('/ma-giam-gia', false)
            ->assertDontSee('/dieu-khoan', false)
            ->assertDontSee('/order-status', false)
            ->assertDontSee('/checkout', false);
    }

    public function test_retired_sitemaps_return_valid_empty_xml(): void
    {
        foreach (['/sitemap-services.xml', '/sitemap-videos.xml'] as $uri) {
            $response = $this->get($uri);

            $response->assertOk()
                ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
                ->assertDontSee('<loc>', false);
        }
    }
}
