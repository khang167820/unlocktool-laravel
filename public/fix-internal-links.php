<?php
/**
 * Fix internal links in blog_posts content
 * Changes /articles/XX-slug.php → /blog/slug
 * Run once, then DELETE this file!
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(\Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;

echo "<h2>🔗 Fix Internal Links in Blog Posts</h2>";
echo "<pre>";

$posts = DB::table('blog_posts')->get();
$fixed = 0;
$totalLinks = 0;

foreach ($posts as $post) {
    $content = $post->content;
    $originalContent = $content;
    
    // Fix links: /articles/XX-slug.php → /blog/slug
    // Also handle: articles/XX-slug.php (relative)
    // Also handle: ../articles/XX-slug.php
    $content = preg_replace_callback(
        '/(href|src)=["\'](?:\.\.\/|\/)?articles\/(\d+-[^"\']+?)\.php["\']/',
        function ($matches) use (&$totalLinks) {
            $attr = $matches[1];
            $filename = $matches[2];
            // Remove leading number: "14-thue-unlocktool-gia-re" → "thue-unlocktool-gia-re"
            $slug = preg_replace('/^\d+-/', '', $filename);
            $totalLinks++;
            return $attr . '="/blog/' . $slug . '"';
        },
        $content
    );
    
    // Also fix canonical URLs pointing to old articles
    $content = str_replace(
        'https://www.unlocktool.us/articles/',
        '/blog/',
        $content
    );
    
    if ($content !== $originalContent) {
        DB::table('blog_posts')->where('id', $post->id)->update([
            'content' => $content,
            'updated_at' => now(),
        ]);
        echo "✅ Fixed: {$post->slug}\n";
        $fixed++;
    }
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "📊 Results:\n";
echo "  ✅ Posts fixed: {$fixed}\n";
echo "  🔗 Links updated: {$totalLinks}\n";
echo "  📁 Total posts: " . count($posts) . "\n";
echo "</pre>";
echo "<p style='color:red;font-weight:bold;'>⚠️ DELETE this file after running!</p>";
