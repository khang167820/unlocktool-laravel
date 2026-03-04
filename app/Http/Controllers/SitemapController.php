<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class SitemapController extends Controller
{
    /**
     * Sitemap Index — lists all sub-sitemaps (giống thuetaikhoan.net)
     */
    public function index()
    {
        $sitemaps = [
            ['loc' => url('/sitemap-pages.xml'), 'lastmod' => now()->toW3cString()],
            ['loc' => url('/sitemap-services.xml'), 'lastmod' => now()->toW3cString()],
            ['loc' => url('/sitemap-posts.xml'), 'lastmod' => now()->toW3cString()],
            ['loc' => url('/sitemap-images.xml'), 'lastmod' => now()->toW3cString()],
            ['loc' => url('/sitemap-videos.xml'), 'lastmod' => now()->toW3cString()],
        ];
        $content = view('sitemaps.index', compact('sitemaps'))->render();
        return response($content, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * Pages Sitemap — all static pages (giống thuetaikhoan.net: 6 URLs)
     */
    public function pages()
    {
        $pages = [
            ['loc' => url('/'), 'lastmod' => now()->toW3cString(), 'changefreq' => 'daily', 'priority' => '1.0'],
            ['loc' => url('/blog'), 'lastmod' => now()->toW3cString(), 'changefreq' => 'daily', 'priority' => '0.9'],
            ['loc' => url('/ord-services'), 'lastmod' => now()->toW3cString(), 'changefreq' => 'weekly', 'priority' => '0.8'],
            ['loc' => url('/ho-tro-mo-khoa'), 'lastmod' => now()->toW3cString(), 'changefreq' => 'weekly', 'priority' => '0.8'],
            ['loc' => url('/ma-giam-gia'), 'lastmod' => now()->toW3cString(), 'changefreq' => 'weekly', 'priority' => '0.7'],
            ['loc' => url('/dieu-khoan'), 'lastmod' => now()->toW3cString(), 'changefreq' => 'monthly', 'priority' => '0.3'],
        ];
        $content = view('sitemaps.urls', ['urls' => $pages])->render();
        return response($content, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * Services Sitemap — all service/tool pages (giống thuetaikhoan.net: 20+ services)
     */
    public function services()
    {
        $servicesSlugs = [
            'thue-unlocktool', 'thue-griffin', 'thue-tsm', 'thue-vietmap-live-pro',
            'thue-amt', 'thue-kg-killer', 'thue-samsung-tool', 'thue-chimera',
            'thue-octoplus', 'thue-infinity', 'thue-easy-jtag', 'thue-medusa',
            'thue-umt', 'thue-mrt', 'thue-falcon', 'thue-hydra',
            'thue-pandora', 'thue-z3x', 'thue-nck', 'thue-sigma',
            'thue-frp', 'thue-halabtech', 'thue-dft', 'thue-cheetah-tool',
        ];

        $urls = [];
        foreach ($servicesSlugs as $slug) {
            $urls[] = [
                'loc' => url("/{$slug}"),
                'lastmod' => now()->toW3cString(),
                'changefreq' => 'daily',
                'priority' => '0.9',
            ];
        }
        $content = view('sitemaps.urls', ['urls' => $urls])->render();
        return response($content, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * Posts Sitemap — all blog posts
     */
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

    /**
     * Images Sitemap — blog post featured images with SEO titles/captions
     */
    public function images()
    {
        $entries = [];
        try {
            $posts = BlogPost::where('status', 'published')->orderBy('created_at', 'desc')->get();
            foreach ($posts as $post) {
                if (!empty($post->featured_image)) {
                    $entries[] = [
                        'loc' => url("/blog/{$post->slug}"),
                        'image_loc' => $post->featured_image,
                        'image_title' => $post->title,
                        'image_caption' => $post->meta_description ?? $post->title,
                    ];
                }
            }
        } catch (\Exception $e) {
            \Log::error('Sitemap images error: ' . $e->getMessage());
        }
        $content = view('sitemaps.images', ['entries' => $entries])->render();
        return response($content, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * Videos Sitemap — YouTube videos on service pages
     */
    public function videos()
    {
        $entries = [
            [
                'loc' => url('/thue-unlocktool'),
                'thumbnail' => 'https://img.youtube.com/vi/_WKNj1lZyQ4/maxresdefault.jpg',
                'title' => 'Hướng dẫn thuê UnlockTool - ' . config('app.name'),
                'description' => 'Hướng dẫn thuê UnlockTool - ' . config('app.name') . ' - Giá rẻ, tự động 24/7',
                'content_loc' => 'https://www.youtube.com/watch?v=_WKNj1lZyQ4',
                'player_loc' => 'https://www.youtube.com/embed/_WKNj1lZyQ4',
            ],
            [
                'loc' => url('/thue-vietmap-live-pro'),
                'thumbnail' => 'https://img.youtube.com/vi/1QSLm8xn1WU/maxresdefault.jpg',
                'title' => 'Hướng dẫn thuê Vietmap Live Pro - ' . config('app.name'),
                'description' => 'Hướng dẫn thuê Vietmap Live Pro - ' . config('app.name') . ' - Giá rẻ, tự động 24/7',
                'content_loc' => 'https://www.youtube.com/watch?v=1QSLm8xn1WU',
                'player_loc' => 'https://www.youtube.com/embed/1QSLm8xn1WU',
            ],
        ];
        $content = view('sitemaps.videos', ['entries' => $entries])->render();
        return response($content, 200)->header('Content-Type', 'application/xml');
    }
}
