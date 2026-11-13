<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

    public function test_duplicate_blog_topics_redirect_directly_to_the_canonical_article(): void
    {
        $this->get('/blog/cach-su-dung-unlocktool')
            ->assertStatus(301)
            ->assertRedirect('/blog/unlocktool-full-guide');

        $this->get('/blog/tai-unlocktool-mien-phi')
            ->assertStatus(301)
            ->assertRedirect('/blog/tai-unlocktool');
    }

    public function test_legacy_article_uses_the_final_consolidated_target(): void
    {
        $this->get('/articles/22-cach-su-dung-unlocktool.php')
            ->assertStatus(301)
            ->assertRedirect('/blog/unlocktool-full-guide');
    }

    public function test_posts_sitemap_excludes_consolidated_articles(): void
    {
        $this->createBlogPostsTable();

        try {
            DB::table('blog_posts')->insert([
                $this->postRow('Bài chuẩn', 'unlocktool-full-guide'),
                $this->postRow('Bài trùng', 'cach-su-dung-unlocktool'),
            ]);

            $this->get('/sitemap-posts.xml')
                ->assertOk()
                ->assertSee('/blog/unlocktool-full-guide', false)
                ->assertDontSee('/blog/cach-su-dung-unlocktool', false);
        } finally {
            Schema::dropIfExists('blog_posts');
        }
    }

    public function test_paginated_blog_archives_are_noindex_follow(): void
    {
        $this->createBlogPostsTable();

        try {
            for ($number = 1; $number <= 13; $number++) {
                DB::table('blog_posts')->insert($this->postRow("Bài {$number}", "bai-{$number}"));
            }

            $this->get('/blog?page=2')
                ->assertOk()
                ->assertSee('<meta name="robots" content="noindex, follow">', false);
        } finally {
            Schema::dropIfExists('blog_posts');
        }
    }

    private function createBlogPostsTable(): void
    {
        Schema::dropIfExists('blog_posts');
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('image')->nullable();
            $table->string('category')->nullable();
            $table->string('author')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('views')->default(0);
            $table->boolean('is_cornerstone')->default(false);
            $table->text('meta_description')->nullable();
            $table->timestamps();
        });
    }

    private function postRow(string $title, string $slug): array
    {
        return [
            'title' => $title,
            'slug' => $slug,
            'excerpt' => 'Nội dung mô tả thử nghiệm.',
            'content' => '<p>Nội dung bài viết thử nghiệm.</p>',
            'category' => 'Hướng dẫn',
            'author' => 'UnlockTool.us',
            'status' => 'published',
            'views' => 1,
            'is_cornerstone' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
