<?php

namespace Tests\Feature;

use App\Services\SeoAnalyzerService;
use Tests\TestCase;

class SeoContentCleanupTest extends TestCase
{
    public function test_it_removes_generated_keyword_paragraphs_and_repairs_interrupted_tags(): void
    {
        $content = '<div<p>Khi sử dụng thuê unlocktool, bạn sẽ được hỗ trợ kỹ thuật 24/7 từ đội ngũ chuyên gia của chúng tôi.</p> class="card">Nội dung thật</div>';

        $clean = (new SeoAnalyzerService())->sanitizeLegacySeoContent($content);

        $this->assertSame('<div class="card">Nội dung thật</div>', $clean);
    }

    public function test_it_removes_generated_intro_but_keeps_original_paragraph(): void
    {
        $content = '<p>Tìm hiểu chi tiết về thuê unlocktool với hướng dẫn đầy đủ và cập nhật mới nhất 2026. Nội dung do tác giả viết.</p>';

        $clean = (new SeoAnalyzerService())->sanitizeLegacySeoContent($content);

        $this->assertSame('<p>Nội dung do tác giả viết.</p>', $clean);
    }

    public function test_it_converts_old_relative_php_links_to_final_canonical_urls(): void
    {
        $content = '<a href="11-unlocktool-download.php">Tải</a><a href="index.php">Blog</a>';

        $clean = (new SeoAnalyzerService())->normalizeLegacyBlogLinks($content);

        $this->assertSame('<a href="/blog/tai-unlocktool">Tải</a><a href="/blog">Blog</a>', $clean);
    }
}
