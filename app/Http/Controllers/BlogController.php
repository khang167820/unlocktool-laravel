<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Services\SEOSchemaGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    /**
     * Blog listing page
     */
    public function index(Request $request)
    {
        $query = BlogPost::published()->orderBy('created_at', 'desc');
        
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }
        
        $posts = $query->paginate(12)->withQueryString();
        
        // Categories
        $categories = BlogPost::published()
            ->select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->orderBy('count', 'desc')
            ->get();
        
        // Featured posts
        $featuredPosts = BlogPost::published()
            ->orderBy('views', 'desc')
            ->limit(3)
            ->get();
        
        return view('blog.index', compact('posts', 'categories', 'featuredPosts'));
    }
    
    /**
     * Single blog post
     */
    public function show($slug)
    {
        $post = BlogPost::where('slug', $slug)->where('status', 'published')->firstOrFail();
        
        // Increment views
        DB::table('blog_posts')->where('id', $post->id)->increment('views');
        
        // Related posts (same category)
        $relatedPosts = BlogPost::published()
            ->where('id', '!=', $post->id)
            ->where('category', $post->category)
            ->orderBy('views', 'desc')
            ->limit(4)
            ->get();
        
        // Popular posts
        $popularPosts = BlogPost::published()
            ->where('id', '!=', $post->id)
            ->orderBy('views', 'desc')
            ->limit(5)
            ->get();
        
        // Prev/Next
        $prevPost = BlogPost::published()->where('id', '<', $post->id)->orderBy('id', 'desc')->first();
        $nextPost = BlogPost::published()->where('id', '>', $post->id)->orderBy('id', 'asc')->first();
        
        // ===== Auto-SEO Processing (from SeoAnalyzerService) =====
        $seoAnalyzer = new \App\Services\SeoAnalyzerService();
        
        // Generate TOC from content
        $toc = $seoAnalyzer->generateToc($post->content);
        
        // Add heading IDs for TOC anchoring
        $post->content = $seoAnalyzer->addHeadingIds($post->content);
        
        // Auto lazy loading + width/height for images (CLS fix)
        $post->content = $seoAnalyzer->autoLazyLoading($post->content);
        
        // Auto image alt text from filename
        $post->content = $seoAnalyzer->autoImageAlt($post->content);
        
        // Auto meta description if missing
        if (empty($post->meta_description)) {
            $post->meta_description = $seoAnalyzer->autoMetaDescription($post->content, $post->title);
        }
        
        // Detect video schema (YouTube/Vimeo)
        $videoSchema = $seoAnalyzer->detectVideoSchema(
            $post->content, $post->title, $post->meta_description ?? ''
        );
        
        // Auto internal linking (max 3 links)
        $post->content = $seoAnalyzer->insertInternalLinks($post->content, $post->id, 3);
        
        // Rating data
        $ratingCount = 0;
        $ratingAvg = 0;
        $hasRated = false;
        try {
            $ratingCount = DB::table('blog_ratings')->where('post_id', $post->id)->count();
            $ratingAvg = round(DB::table('blog_ratings')->where('post_id', $post->id)->avg('rating') ?? 0, 1);
            $hasRated = DB::table('blog_ratings')
                ->where('post_id', $post->id)
                ->where('ip_address', request()->ip())
                ->exists();
        } catch (\Exception $e) {}
        
        // FAQ & HowTo Schema
        $faqSchema = null;
        $howToSchema = null;
        try {
            $schemaGenerator = new SEOSchemaGenerator();
            
            $faqs = $schemaGenerator->extractFAQFromContent($post->content);
            if ($faqs) {
                $faqSchema = $schemaGenerator->generateFAQSchema($faqs);
            }
            
            if ($schemaGenerator->isHowToContent($post->title, $post->content)) {
                $steps = $schemaGenerator->extractHowToFromContent($post->content);
                if ($steps) {
                    $howToSchema = $schemaGenerator->generateHowToSchema($post->title, $steps, $post->meta_description);
                }
            }
        } catch (\Exception $e) {}
        
        return view('blog.show', compact(
            'post', 'relatedPosts', 'popularPosts', 'prevPost', 'nextPost',
            'toc', 'ratingCount', 'ratingAvg', 'hasRated',
            'faqSchema', 'howToSchema', 'videoSchema'
        ));
    }
    
    /**
     * Blog by category
     */
    public function category($category)
    {
        $posts = BlogPost::published()
            ->where('category', $category)
            ->orderBy('created_at', 'desc')
            ->paginate(12)
            ->withQueryString();
        
        $categories = BlogPost::published()
            ->select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->orderBy('count', 'desc')
            ->get();
        
        $featuredPosts = collect();
        
        return view('blog.index', compact('posts', 'categories', 'featuredPosts'));
    }
    
    /**
     * Rate a blog post (AJAX)
     */
    public function ratePost(Request $request, $slug)
    {
        $post = BlogPost::where('slug', $slug)->firstOrFail();
        
        $request->validate(['rating' => 'required|integer|min:1|max:5']);
        
        try {
            $exists = DB::table('blog_ratings')
                ->where('post_id', $post->id)
                ->where('ip_address', $request->ip())
                ->exists();
            
            if ($exists) {
                return response()->json(['success' => false, 'error' => 'Already rated']);
            }
            
            DB::table('blog_ratings')->insert([
                'post_id' => $post->id,
                'ip_address' => $request->ip(),
                'rating' => $request->rating,
                'created_at' => now(),
            ]);
            
            $avg = round(DB::table('blog_ratings')->where('post_id', $post->id)->avg('rating'), 1);
            $count = DB::table('blog_ratings')->where('post_id', $post->id)->count();
            
            return response()->json(['success' => true, 'avg' => $avg, 'count' => $count]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * Generate Table of Contents from HTML content
     */
    private function generateTOC(string $content): array
    {
        $toc = [];
        preg_match_all('/<h([2-4])[^>]*(?:id=["\']([^"\']*)["\'])?[^>]*>(.*?)<\/h[2-4]>/is', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $level = (int) $match[1];
            $id = $match[2] ?: Str::slug(strip_tags($match[3]));
            $text = strip_tags($match[3]);
            
            $toc[] = [
                'level' => $level,
                'id' => $id,
                'text' => $text,
            ];
        }
        
        return $toc;
    }
}
