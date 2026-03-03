<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SeoAnalyzerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SeoController extends Controller
{
    /**
     * SEO Dashboard - Overview of all posts' SEO scores
     */
    public function dashboard(Request $request)
    {
        $analyzer = new SeoAnalyzerService();
        
        try {
            $allPosts = DB::table('blog_posts')
                ->where('status', 'published')
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            $allPosts = collect();
        }
        
        $good = 0;
        $warning = 0;
        $bad = 0;
        $totalScore = 0;
        
        foreach ($allPosts as $post) {
            $result = $analyzer->quickScore($post);
            $post->seo_score = $result['score'];
            $post->seo_status = $result['status'];
            $post->seo_stats = $result['stats'];
            
            $totalScore += $result['score'];
            
            if ($result['status'] === 'good') $good++;
            elseif ($result['status'] === 'warning') $warning++;
            else $bad++;
        }
        
        $avgScore = $allPosts->count() > 0 ? round($totalScore / $allPosts->count()) : 0;
        $avgStatus = $avgScore >= 70 ? 'good' : ($avgScore >= 40 ? 'warning' : 'bad');
        
        // Filter
        $filter = $request->get('filter');
        if ($filter && $filter !== 'all') {
            if ($filter === 'no-keyword') {
                $allPosts = $allPosts->filter(fn($p) => empty($p->focus_keyword));
            } else {
                $allPosts = $allPosts->filter(fn($p) => $p->seo_status === $filter);
            }
        }
        
        $stats = [
            'total' => $allPosts->count(),
            'good' => $good,
            'warning' => $warning,
            'bad' => $bad,
            'avgScore' => $avgScore,
            'avgStatus' => $avgStatus,
        ];
        
        // Advanced analytics
        $staleContent = $analyzer->staleContentAnalysis();
        $orphanedContent = $analyzer->orphanedContentAnalysis();
        $cannibalization = $analyzer->keywordCannibalization();
        $analytics = $analyzer->dashboardAnalytics();
        
        // Recently added posts
        $recentlyAdded = DB::table('blog_posts')
            ->orderBy('id', 'desc')
            ->get();
        
        return view('admin.seo-analyzer.index', [
            'posts' => $allPosts,
            'stats' => $stats,
            'staleContent' => $staleContent,
            'orphanedContent' => $orphanedContent,
            'cannibalization' => $cannibalization,
            'analytics' => $analytics,
            'recentlyAdded' => $recentlyAdded,
        ]);
    }
    
    /**
     * SEO Analyze Post - AJAX endpoint for real-time analysis
     */
    public function analyzePost(Request $request)
    {
        $analyzer = new SeoAnalyzerService();
        
        $data = [
            'id' => $request->input('id'),
            'title' => $request->input('title', ''),
            'slug' => $request->input('slug', ''),
            'content' => $request->input('content', ''),
            'meta_title' => $request->input('meta_title', ''),
            'meta_description' => $request->input('meta_description', ''),
            'focus_keyword' => $request->input('focus_keyword', ''),
            'related_keyphrases' => $request->input('related_keyphrases', ''),
            'excerpt' => $request->input('excerpt', ''),
            'image' => $request->input('image', ''),
        ];
        
        $result = $analyzer->analyze($data);
        
        // Add social preview data
        $result['socialPreview'] = $analyzer->socialPreviewData($data);
        
        // Add internal linking suggestions
        if ($data['id'] && !empty($data['content'])) {
            $result['linkSuggestions'] = $analyzer->internalLinkingSuggestions(
                (int)$data['id'], $data['content'], $data['title']
            );
        }
        
        // Add Content Insights
        if (!empty($data['content'])) {
            $result['contentInsights'] = $analyzer->contentInsights($data['content']);
        }
        
        // Add Transition Words check
        if (!empty($data['content'])) {
            $result['transitionWords'] = $analyzer->checkTransitionWords($data['content']);
        }
        
        // Add Keyword Variation Detection
        if (!empty($data['focus_keyword']) && !empty($data['content'])) {
            $result['keywordVariations'] = $analyzer->checkKeywordVariationsInContent(
                $data['focus_keyword'], $data['content']
            );
        }
        
        // Add Cornerstone Analysis
        $isCornerstone = $request->input('is_cornerstone', false);
        if ($isCornerstone && !empty($data['content'])) {
            $result['cornerstoneAnalysis'] = $analyzer->cornerstoneAnalysis($data);
        }
        
        // Add Word Complexity Check
        if (!empty($data['content'])) {
            $result['wordComplexity'] = $analyzer->wordComplexityCheck($data['content']);
        }
        
        // Add Estimated Reading Time
        if (!empty($data['content'])) {
            $result['readingTime'] = $analyzer->estimatedReadingTime($data['content']);
        }
        
        return response()->json($result);
    }
    
    /**
     * Bulk SEO Editor - AJAX save
     */
    public function bulkSave(Request $request)
    {
        $items = $request->input('items', []);
        $updated = 0;
        
        foreach ($items as $item) {
            if (empty($item['id'])) continue;
            
            $updateData = [];
            if (isset($item['meta_title'])) $updateData['meta_title'] = $item['meta_title'];
            if (isset($item['meta_description'])) $updateData['meta_description'] = $item['meta_description'];
            if (isset($item['focus_keyword'])) $updateData['focus_keyword'] = $item['focus_keyword'];
            
            if (!empty($updateData)) {
                $updateData['updated_at'] = now();
                DB::table('blog_posts')->where('id', $item['id'])->update($updateData);
                $updated++;
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => "Đã cập nhật {$updated} bài viết!",
            'updated' => $updated,
        ]);
    }
    
    /**
     * SEO Redirect Manager
     */
    public function redirects()
    {
        $redirects = SeoAnalyzerService::getRedirects();
        return view('admin.seo-analyzer.redirects', compact('redirects'));
    }
    
    public function redirectStore(Request $request)
    {
        $request->validate([
            'from_url' => 'required|string|max:500',
            'to_url' => 'required|string|max:500',
            'status_code' => 'required|integer|in:301,302',
        ]);
        
        $success = SeoAnalyzerService::addRedirect(
            $request->from_url,
            $request->to_url,
            $request->status_code
        );
        
        if ($success) {
            return back()->with('success', 'Redirect đã được thêm!');
        }
        return back()->with('error', 'Có lỗi xảy ra.');
    }
    
    public function redirectDelete($id)
    {
        SeoAnalyzerService::deleteRedirect((int)$id);
        return back()->with('success', 'Redirect đã được xóa!');
    }
    
    /**
     * Internal Linking Suggestions
     */
    public function internalLinks(Request $request)
    {
        $postId = $request->input('post_id');
        $content = $request->input('content', '');
        
        if (empty($content) && $postId) {
            $post = DB::table('blog_posts')->where('id', $postId)->first();
            $content = $post->content ?? '';
        }
        
        $seo = new SeoAnalyzerService();
        $suggestions = $seo->autoInternalLinking($content, $postId);
        
        return response()->json([
            'success' => true,
            'suggestions' => $suggestions,
        ]);
    }
    
    /**
     * Export Focus Keywords as CSV
     */
    public function exportKeywords()
    {
        $analyzer = new SeoAnalyzerService();
        $csv = $analyzer->exportFocusKeywordsCsv();
        
        $filename = 'focus-keywords-' . date('Y-m-d') . '.csv';
        
        return response($csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->header('Content-Length', strlen($csv));
    }
    
    /**
     * Content Decay Detection (AJAX)
     */
    public function contentDecay()
    {
        $analyzer = new SeoAnalyzerService();
        $result = $analyzer->contentDecayDetection();
        return response()->json($result);
    }
    
    /**
     * Broken Link Checker (AJAX)
     */
    public function brokenLinks()
    {
        set_time_limit(300);
        try {
            $analyzer = new SeoAnalyzerService();
            $result = $analyzer->brokenLinkChecker();
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'broken' => [],
                'checked' => 0,
                'total_links' => 0,
                'broken_count' => 0,
                'error' => 'Timeout hoặc lỗi: ' . $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Topical Authority Map (AJAX)
     */
    public function topicalAuthority()
    {
        $analyzer = new SeoAnalyzerService();
        $result = $analyzer->topicalAuthorityMap();
        return response()->json($result);
    }
    
    /**
     * SEO Auto-Fix V2 Preview (dry run)
     */
    public function autoFixV2Preview()
    {
        set_time_limit(300);
        $analyzer = new SeoAnalyzerService();
        $result = $analyzer->seoAutoFixAllV2(true);
        return response()->json($result);
    }
    
    /**
     * SEO Auto-Fix V2 Apply
     */
    public function autoFixV2()
    {
        set_time_limit(300);
        $analyzer = new SeoAnalyzerService();
        $result = $analyzer->seoAutoFixAllV2(false);
        return response()->json($result);
    }
}
