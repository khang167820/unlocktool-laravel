<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class SitemapController extends Controller
{
    /**
     * Only advertise sitemap files that contain real, indexable URLs.
     */
    public function index(): Response
    {
        $postsLastModified = $this->postsLastModified();
        $sitemaps = [
            ['loc' => url('/sitemap-pages.xml'), 'lastmod' => $this->pagesLastModified()],
            ['loc' => url('/sitemap-posts.xml'), 'lastmod' => $postsLastModified],
            ['loc' => url('/sitemap-images.xml'), 'lastmod' => $postsLastModified],
        ];

        return $this->xmlResponse('sitemaps.index', compact('sitemaps'));
    }

    /**
     * Real public pages. Planned and transactional routes are excluded.
     */
    public function pages(): Response
    {
        $pages = [
            [
                'loc' => url('/'),
                'lastmod' => $this->viewLastModified('welcome.blade.php'),
                'changefreq' => 'daily',
                'priority' => '1.0',
            ],
            [
                'loc' => url('/blog'),
                'lastmod' => $this->postsLastModified(),
                'changefreq' => 'daily',
                'priority' => '0.8',
            ],
        ];

        return $this->xmlResponse('sitemaps.urls', ['urls' => $pages]);
    }

    /**
     * Kept for backwards compatibility with previously submitted sitemaps.
     * Add service URLs only after matching routes and unique content exist.
     */
    public function services(): Response
    {
        return $this->xmlResponse('sitemaps.urls', ['urls' => []]);
    }

    public function posts(): Response
    {
        $urls = [];

        try {
            $posts = BlogPost::published()->indexable()->latest('created_at')->get();
            foreach ($posts as $post) {
                $lastModified = $post->updated_at ?? $post->created_at;
                $urls[] = [
                    'loc' => url("/blog/{$post->slug}"),
                    'lastmod' => ($lastModified ?: now())->toW3cString(),
                    'changefreq' => 'weekly',
                    'priority' => $post->is_cornerstone ? '0.9' : '0.7',
                ];
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        return $this->xmlResponse('sitemaps.urls', compact('urls'));
    }

    public function images(): Response
    {
        $entries = [];

        try {
            $posts = BlogPost::published()->indexable()->latest('created_at')->get();
            foreach ($posts as $post) {
                $image = $post->featured_image ?? $post->image;
                if (empty($image)) {
                    continue;
                }

                $entries[] = [
                    'loc' => url("/blog/{$post->slug}"),
                    'image_loc' => Str::startsWith($image, ['http://', 'https://'])
                        ? $image
                        : url($image),
                    'image_title' => $post->title,
                    'image_caption' => $post->meta_description ?: $post->title,
                ];
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        return $this->xmlResponse('sitemaps.images', compact('entries'));
    }

    /**
     * Return valid empty XML until a real page with an embedded video exists.
     */
    public function videos(): Response
    {
        return $this->xmlResponse('sitemaps.videos', ['entries' => []]);
    }

    private function xmlResponse(string $view, array $data): Response
    {
        return response(view($view, $data)->render(), 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    private function pagesLastModified(): string
    {
        $timestamps = [
            filemtime(resource_path('views/welcome.blade.php')),
            filemtime(resource_path('views/blog/index.blade.php')),
        ];

        return Carbon::createFromTimestamp(max($timestamps), 'Asia/Ho_Chi_Minh')
            ->toW3cString();
    }

    private function postsLastModified(): string
    {
        try {
            $lastModified = BlogPost::published()->indexable()->max('updated_at');
            if ($lastModified) {
                return Carbon::parse($lastModified)->toW3cString();
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        return $this->viewLastModified('blog/index.blade.php');
    }

    private function viewLastModified(string $view): string
    {
        $path = resource_path("views/{$view}");
        $timestamp = file_exists($path) ? filemtime($path) : time();

        return Carbon::createFromTimestamp($timestamp, 'Asia/Ho_Chi_Minh')
            ->toW3cString();
    }
}
