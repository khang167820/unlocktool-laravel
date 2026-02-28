<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index()
    {
        $sitemaps = [
            ['loc' => url('/sitemap-pages.xml'), 'lastmod' => now()->toW3cString()],
            ['loc' => url('/sitemap-posts.xml'), 'lastmod' => now()->toW3cString()],
        ];
        $content = view('sitemaps.index', compact('sitemaps'))->render();
        return response($content, 200)->header('Content-Type', 'application/xml');
    }

    public function pages()
    {
        $pages = [
            ['loc' => url('/'), 'lastmod' => now()->toW3cString(), 'changefreq' => 'daily', 'priority' => '1.0'],
            ['loc' => url('/blog'), 'lastmod' => now()->toW3cString(), 'changefreq' => 'daily', 'priority' => '0.9'],
            ['loc' => url('/checkout'), 'lastmod' => now()->toW3cString(), 'changefreq' => 'weekly', 'priority' => '0.8'],
        ];
        $content = view('sitemaps.urls', ['urls' => $pages])->render();
        return response($content, 200)->header('Content-Type', 'application/xml');
    }

    public function posts()
    {
        $urls = [
            ['loc' => url('/blog'), 'lastmod' => now()->toW3cString(), 'changefreq' => 'daily', 'priority' => '0.8'],
        ];
        try {
            $posts = BlogPost::where('status', 'published')->orderBy('created_at', 'desc')->get();
            foreach ($posts as $post) {
                $lastmod = $post->updated_at ?? $post->created_at ?? now();
                $urls[] = [
                    'loc' => url("/blog/{$post->slug}"),
                    'lastmod' => $lastmod->toW3cString(),
                    'changefreq' => 'weekly',
                    'priority' => $post->is_cornerstone ? '0.9' : '0.7',
                ];
            }
        } catch (\Exception $e) {
            \Log::error('Sitemap posts error: ' . $e->getMessage());
        }
        $content = view('sitemaps.urls', ['urls' => $urls])->render();
        return response($content, 200)->header('Content-Type', 'application/xml');
    }
}
