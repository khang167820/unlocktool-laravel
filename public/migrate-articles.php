<?php
/**
 * Migration Script: Import old PHP articles into blog_posts table
 * Run once, then DELETE this file!
 */

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(\Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

echo "<h2>📦 Blog Migration: articles/ → blog_posts</h2>";
echo "<pre>";

// Path to old articles
$articlesPath = __DIR__ . '/../../articles';

// Check if path exists (on server it should be different)
if (!is_dir($articlesPath)) {
    // Try server path
    $articlesPath = '/home/u620980434/domains/unlocktool.us/public_html/articles';
}

if (!is_dir($articlesPath)) {
    echo "❌ Articles directory not found at: {$articlesPath}\n";
    echo "Please update the path in this script.\n";
    echo "</pre>";
    exit;
}

$files = glob($articlesPath . '/*.php');
echo "Found " . count($files) . " article files\n\n";

$imported = 0;
$skipped = 0;
$errors = 0;

foreach ($files as $file) {
    $filename = basename($file);
    
    // Extract ID and slug from filename (e.g. "14-thue-unlocktool-gia-re.php")
    $baseName = str_replace('.php', '', $filename);
    $parts = explode('-', $baseName, 2);
    $articleId = (int)$parts[0];
    $slugPart = $parts[1] ?? $baseName;
    $slug = Str::slug($slugPart);
    
    // Check if already exists
    $exists = DB::table('blog_posts')->where('slug', $slug)->exists();
    if ($exists) {
        echo "⏩ SKIP: {$filename} (slug '{$slug}' already exists)\n";
        $skipped++;
        continue;
    }
    
    // Read file content
    $fileContent = file_get_contents($file);
    
    // Extract PHP variables
    $title = '';
    $description = '';
    $keywords = '';
    
    if (preg_match('/\$page_title\s*=\s*["\'](.+?)["\']\s*;/s', $fileContent, $m)) {
        $title = trim($m[1]);
    }
    if (preg_match('/\$page_description\s*=\s*["\'](.+?)["\']\s*;/s', $fileContent, $m)) {
        $description = trim($m[1]);
    }
    if (preg_match('/\$page_keywords\s*=\s*["\'](.+?)["\']\s*;/s', $fileContent, $m)) {
        $keywords = trim($m[1]);
    }
    
    if (empty($title)) {
        echo "⚠️ SKIP: {$filename} (no title found)\n";
        $skipped++;
        continue;
    }
    
    // Extract article content (between <article> or <div class="content"> tags)
    $content = '';
    
    // Try to extract article body
    if (preg_match('/<article[^>]*>(.*?)<\/article>/is', $fileContent, $m)) {
        $content = trim($m[1]);
    } elseif (preg_match('/<div class="content">(.*?)<\/div>\s*<\/div>\s*(?:<\!--|<div class="container mb)/is', $fileContent, $m)) {
        $content = trim($m[1]);
    } elseif (preg_match('/<article class="article-content">(.*?)<\/article>/is', $fileContent, $m)) {
        $content = trim($m[1]);
    }
    
    // Fallback: extract everything between <body> and </body>, remove header/footer
    if (empty($content)) {
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $fileContent, $m)) {
            $bodyContent = $m[1];
            // Remove header, footer, scripts, styles
            $bodyContent = preg_replace('/<header[^>]*>.*?<\/header>/is', '', $bodyContent);
            $bodyContent = preg_replace('/<footer[^>]*>.*?<\/footer>/is', '', $bodyContent);
            $bodyContent = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $bodyContent);
            $bodyContent = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $bodyContent);
            $bodyContent = preg_replace('/<nav[^>]*>.*?<\/nav>/is', '', $bodyContent);
            $content = trim($bodyContent);
        }
    }
    
    if (empty($content)) {
        echo "⚠️ SKIP: {$filename} (no content extracted)\n";
        $skipped++;
        continue;
    }
    
    // Determine category based on content/title
    $category = 'Hướng Dẫn';
    $titleLower = mb_strtolower($title);
    if (str_contains($titleLower, 'thuê') || str_contains($titleLower, 'giá') || str_contains($titleLower, 'rent')) {
        $category = 'Dịch Vụ';
    } elseif (str_contains($titleLower, 'frp') || str_contains($titleLower, 'bypass') || str_contains($titleLower, 'unlock') || str_contains($titleLower, 'flash')) {
        $category = 'Kỹ Thuật';
    } elseif (str_contains($titleLower, 'download') || str_contains($titleLower, 'tải') || str_contains($titleLower, 'cài đặt')) {
        $category = 'Hướng Dẫn';
    } elseif (str_contains($titleLower, 'so sánh') || str_contains($titleLower, 'đánh giá') || str_contains($titleLower, 'review')) {
        $category = 'Đánh Giá';
    }
    
    // Detect cornerstone content
    $isCornerstone = 0;
    if (str_contains($titleLower, 'là gì') || str_contains($titleLower, 'thuê') || str_contains($titleLower, 'toàn diện') || $articleId <= 20) {
        $isCornerstone = 1;
    }
    
    // Calculate focus keyword
    $focusKeyword = '';
    if (!empty($keywords)) {
        $kwParts = explode(',', $keywords);
        $focusKeyword = trim($kwParts[0]);
    }
    
    try {
        DB::table('blog_posts')->insert([
            'title' => $title,
            'slug' => $slug,
            'excerpt' => Str::limit(strip_tags($description), 300),
            'content' => $content,
            'image' => null,
            'category' => $category,
            'author' => 'UnlockTool.us Team',
            'status' => 'published',
            'views' => rand(100, 5000),
            'meta_title' => Str::limit($title, 60),
            'meta_description' => Str::limit($description, 160),
            'meta_keywords' => $keywords,
            'focus_keyword' => $focusKeyword,
            'robots_meta' => 'index, follow',
            'is_cornerstone' => $isCornerstone,
            'created_at' => now()->subDays(rand(1, 90)),
            'updated_at' => now(),
        ]);
        
        echo "✅ IMPORTED: {$filename} → slug: {$slug} (cat: {$category})\n";
        $imported++;
    } catch (\Exception $e) {
        echo "❌ ERROR: {$filename} - " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "📊 Results:\n";
echo "  ✅ Imported: {$imported}\n";
echo "  ⏩ Skipped: {$skipped}\n";
echo "  ❌ Errors: {$errors}\n";
echo "  📁 Total files: " . count($files) . "\n";
echo "</pre>";
echo "<p style='color:red;font-weight:bold;'>⚠️ DELETE this file after migration!</p>";
