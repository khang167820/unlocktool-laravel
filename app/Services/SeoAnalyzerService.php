<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class SeoAnalyzerService
{
    /**
     * Analyze a blog post for SEO quality
     * Returns structured analysis with scores and recommendations
     */
    public function analyze(array $data): array
    {
        $checks = [];
        
        $title = $data['title'] ?? '';
        $slug = $data['slug'] ?? '';
        $content = $data['content'] ?? '';
        $metaTitle = $data['meta_title'] ?? '';
        $metaDescription = $data['meta_description'] ?? '';
        $focusKeyword = strtolower(trim($data['focus_keyword'] ?? ''));
        $relatedKeyphrases = $data['related_keyphrases'] ?? '';
        $excerpt = $data['excerpt'] ?? '';
        $postId = $data['id'] ?? null;
        
        // Use meta_title or title for SEO title
        $seoTitle = !empty($metaTitle) ? $metaTitle : $title;
        
        // Strip HTML from content for text analysis
        $plainContent = strip_tags($content);
        $plainContentLower = mb_strtolower($plainContent);
        $wordCount = $this->countWords($plainContent);
        
        // ============================
        // 1. Focus Keyword Checks
        // ============================
        if (!empty($focusKeyword)) {
            $checks[] = $this->checkKeywordInTitle($focusKeyword, $seoTitle);
            $checks[] = $this->checkKeywordInSlug($focusKeyword, $slug);
            $checks[] = $this->checkKeywordInMeta($focusKeyword, $metaDescription);
            $checks[] = $this->checkKeywordInFirstParagraph($focusKeyword, $content);
            $checks[] = $this->checkKeywordDensity($focusKeyword, $plainContentLower, $wordCount);
            $checks[] = $this->checkKeywordInHeadings($focusKeyword, $content);
            $checks[] = $this->checkKeywordInImageAlt($focusKeyword, $content);
            $checks[] = $this->checkKeywordUniqueness($focusKeyword, $postId);
        } else {
            $checks[] = [
                'id' => 'focus_keyword',
                'category' => 'keyword',
                'status' => 'bad',
                'message' => 'Chưa đặt từ khóa mục tiêu (Focus Keyword). Hãy nhập từ khóa chính cho bài viết.',
                'score' => 0,
                'maxScore' => 10,
            ];
        }
        
        // ============================
        // 2. Title Checks
        // ============================
        $checks[] = $this->checkTitleLength($seoTitle);
        $checks[] = $this->checkTitleExists($seoTitle, $title);
        
        // ============================
        // 3. Meta Description Checks
        // ============================
        $checks[] = $this->checkMetaDescriptionLength($metaDescription);
        $checks[] = $this->checkMetaDescriptionExists($metaDescription);
        
        // ============================
        // 4. Content Checks
        // ============================
        $checks[] = $this->checkContentLength($wordCount);
        $checks[] = $this->checkHeadingStructure($content);
        $checks[] = $this->checkInternalLinks($content);
        $checks[] = $this->checkExternalLinks($content);
        $checks[] = $this->checkImageAltText($content);
        $checks[] = $this->checkImageCount($content);
        
        // ============================
        // 5. Readability Checks
        // ============================
        $checks[] = $this->checkParagraphLength($content);
        $checks[] = $this->checkSubheadingDistribution($content);
        
        // ============================
        // 6. Slug Check
        // ============================
        $checks[] = $this->checkSlugLength($slug);
        
        // ============================
        // 7. Phase 3: Content Quality
        // ============================
        $checks[] = $this->checkContentDepth($content, $wordCount);
        $checks[] = $this->checkHeadingHierarchy($content);
        $checks[] = $this->checkImageSeo($content);
        if (!empty($focusKeyword)) {
            $checks[] = $this->checkKeywordStuffing($focusKeyword, $plainContentLower, $wordCount);
        }
        $checks[] = $this->checkAnchorTextDiversity($content);
        
        // ============================
        // 8. Phase 4: Technical SEO
        // ============================
        $checks[] = $this->checkOutboundLinks($content);
        $checks[] = $this->checkMobileFriendliness($content);
        
        // ============================
        // 9. Phase 5: Advanced SEO
        // ============================
        $checks[] = $this->readabilityScore($content);
        
        // ============================
        // 10. Phase 5.5: Related Keyphrases
        // ============================
        if (!empty($relatedKeyphrases)) {
            $checks[] = $this->checkRelatedKeyphrases($relatedKeyphrases, $plainContentLower, $seoTitle, $metaDescription);
        }
        
        // Calculate overall score
        $totalScore = 0;
        $maxScore = 0;
        foreach ($checks as $check) {
            $totalScore += $check['score'];
            $maxScore += $check['maxScore'];
        }
        
        $overallScore = $maxScore > 0 ? round(($totalScore / $maxScore) * 100) : 0;
        
        // Determine overall status
        if ($overallScore >= 70) {
            $overallStatus = 'good';
        } elseif ($overallScore >= 40) {
            $overallStatus = 'warning';
        } else {
            $overallStatus = 'bad';
        }
        
        // Google preview data
        $googlePreview = [
            'title' => mb_substr($seoTitle, 0, 60),
            'url' => 'thuetaikhoan.net/blog/' . $slug,
            'description' => !empty($metaDescription) 
                ? mb_substr($metaDescription, 0, 160) 
                : mb_substr($plainContent, 0, 160),
        ];
        
        // Keyword density for stats
        $keywordDensity = 0;
        if (!empty($focusKeyword) && $wordCount > 0) {
            $kwCount = mb_substr_count($plainContentLower, $focusKeyword);
            $keywordDensity = round(($kwCount / $wordCount) * 100, 1);
        }
        
        // Schema suggestions
        $schemas = $this->detectSchemas($content, $title);
        
        return [
            'score' => $overallScore,
            'status' => $overallStatus,
            'checks' => $checks,
            'stats' => [
                'wordCount' => $wordCount,
                'titleLength' => mb_strlen($seoTitle),
                'metaLength' => mb_strlen($metaDescription),
                'imageCount' => $this->countImages($content),
                'internalLinks' => $this->countInternalLinks($content),
                'externalLinks' => $this->countExternalLinks($content),
                'keywordDensity' => $keywordDensity,
                'headingCount' => $this->countHeadings($content),
            ],
            'googlePreview' => $googlePreview,
            'schemas' => $schemas,
        ];
    }
    
    /**
     * Quick score for dashboard (lighter analysis)
     */
    public function quickScore(object $post): array
    {
        return $this->analyze([
            'id' => $post->id ?? null,
            'title' => $post->title ?? '',
            'slug' => $post->slug ?? '',
            'content' => $post->content ?? '',
            'meta_title' => $post->meta_title ?? '',
            'meta_description' => $post->meta_description ?? '',
            'focus_keyword' => $post->focus_keyword ?? '',
            'excerpt' => $post->excerpt ?? '',
        ]);
    }
    
    // ============================================================
    // Keyword Checks
    // ============================================================
    
    private function checkKeywordInTitle(string $keyword, string $title): array
    {
        $titleLower = mb_strtolower($title);
        $found = mb_strpos($titleLower, $keyword) !== false;
        
        return [
            'id' => 'keyword_in_title',
            'category' => 'keyword',
            'status' => $found ? 'good' : 'bad',
            'message' => $found 
                ? "Từ khóa \"{$keyword}\" có trong tiêu đề SEO. Tốt!" 
                : "Từ khóa \"{$keyword}\" không có trong tiêu đề SEO. Hãy thêm vào!",
            'score' => $found ? 10 : 0,
            'maxScore' => 10,
        ];
    }
    
    private function checkKeywordInSlug(string $keyword, string $slug): array
    {
        // Normalize keyword for slug comparison
        $keywordSlug = $this->vietnameseToSlug($keyword);
        $found = strpos($slug, $keywordSlug) !== false;
        
        return [
            'id' => 'keyword_in_slug',
            'category' => 'keyword',
            'status' => $found ? 'good' : 'warning',
            'message' => $found 
                ? "Từ khóa có trong URL (slug). Tốt!" 
                : "Từ khóa không có trong URL. Nên thêm để SEO tốt hơn.",
            'score' => $found ? 5 : 1,
            'maxScore' => 5,
        ];
    }
    
    private function checkKeywordInMeta(string $keyword, string $metaDescription): array
    {
        $metaLower = mb_strtolower($metaDescription);
        $found = mb_strpos($metaLower, $keyword) !== false;
        
        return [
            'id' => 'keyword_in_meta',
            'category' => 'keyword',
            'status' => $found ? 'good' : 'warning',
            'message' => $found 
                ? "Từ khóa có trong meta description. Tốt!" 
                : "Từ khóa không có trong meta description. Nên thêm để Google hiểu nội dung.",
            'score' => $found ? 8 : 2,
            'maxScore' => 8,
        ];
    }
    
    private function checkKeywordInFirstParagraph(string $keyword, string $htmlContent): array
    {
        // Get first paragraph
        $firstParagraph = '';
        if (preg_match('/<p[^>]*>(.*?)<\/p>/is', $htmlContent, $match)) {
            $firstParagraph = mb_strtolower(strip_tags($match[1]));
        } else {
            // No <p> tags, take first 200 chars
            $firstParagraph = mb_strtolower(mb_substr(strip_tags($htmlContent), 0, 200));
        }
        
        $found = mb_strpos($firstParagraph, $keyword) !== false;
        
        return [
            'id' => 'keyword_in_intro',
            'category' => 'keyword',
            'status' => $found ? 'good' : 'warning',
            'message' => $found 
                ? "Từ khóa xuất hiện trong đoạn mở đầu. Tốt!" 
                : "Từ khóa chưa xuất hiện trong đoạn mở đầu. Nên thêm vào 1-2 câu đầu tiên.",
            'score' => $found ? 7 : 2,
            'maxScore' => 7,
        ];
    }
    
    private function checkKeywordDensity(string $keyword, string $plainContentLower, int $wordCount): array
    {
        if ($wordCount === 0) {
            return [
                'id' => 'keyword_density',
                'category' => 'keyword',
                'status' => 'bad',
                'message' => 'Bài viết chưa có nội dung.',
                'score' => 0,
                'maxScore' => 8,
            ];
        }
        
        $keywordCount = mb_substr_count($plainContentLower, $keyword);
        $keywordWords = $this->countWords($keyword);
        $density = ($keywordCount * $keywordWords / $wordCount) * 100;
        $densityRound = round($density, 1);
        
        if ($density >= 1 && $density <= 3) {
            $status = 'good';
            $score = 8;
            $msg = "Mật độ từ khóa: {$densityRound}% ({$keywordCount} lần). Tối ưu!";
        } elseif ($density > 3) {
            $status = 'warning';
            $score = 4;
            $msg = "Mật độ từ khóa: {$densityRound}% ({$keywordCount} lần). Hơi cao — có thể bị Google coi là spam.";
        } elseif ($density > 0) {
            $status = 'warning';
            $score = 4;
            $msg = "Mật độ từ khóa: {$densityRound}% ({$keywordCount} lần). Hơi thấp — nên đạt 1-3%.";
        } else {
            $status = 'bad';
            $score = 0;
            $msg = "Từ khóa không xuất hiện trong bài viết! Hãy thêm vào nội dung.";
        }
        
        return [
            'id' => 'keyword_density',
            'category' => 'keyword',
            'status' => $status,
            'message' => $msg,
            'score' => $score,
            'maxScore' => 8,
        ];
    }
    
    private function checkKeywordInHeadings(string $keyword, string $htmlContent): array
    {
        preg_match_all('/<h[2-4][^>]*>(.*?)<\/h[2-4]>/is', $htmlContent, $matches);
        
        if (empty($matches[1])) {
            return [
                'id' => 'keyword_in_headings',
                'category' => 'keyword',
                'status' => 'warning',
                'message' => 'Bài viết chưa có heading (H2-H4). Nên thêm heading để chia nội dung.',
                'score' => 1,
                'maxScore' => 5,
            ];
        }
        
        $found = false;
        foreach ($matches[1] as $heading) {
            if (mb_strpos(mb_strtolower(strip_tags($heading)), $keyword) !== false) {
                $found = true;
                break;
            }
        }
        
        return [
            'id' => 'keyword_in_headings',
            'category' => 'keyword',
            'status' => $found ? 'good' : 'warning',
            'message' => $found 
                ? "Từ khóa xuất hiện trong heading (H2-H4). Tốt!" 
                : "Từ khóa chưa có trong heading nào. Nên thêm vào ít nhất 1 heading.",
            'score' => $found ? 5 : 1,
            'maxScore' => 5,
        ];
    }
    
    private function checkKeywordInImageAlt(string $keyword, string $htmlContent): array
    {
        preg_match_all('/<img[^>]+alt=["\']([^"\']*)["\'][^>]*>/i', $htmlContent, $matches);
        
        if (empty($matches[0])) {
            return [
                'id' => 'keyword_in_img_alt',
                'category' => 'keyword',
                'status' => 'warning',
                'message' => 'Bài viết chưa có hình ảnh. Nên thêm ảnh minh họa.',
                'score' => 1,
                'maxScore' => 3,
            ];
        }
        
        $found = false;
        foreach ($matches[1] as $alt) {
            if (mb_strpos(mb_strtolower($alt), $keyword) !== false) {
                $found = true;
                break;
            }
        }
        
        return [
            'id' => 'keyword_in_img_alt',
            'category' => 'keyword',
            'status' => $found ? 'good' : 'warning',
            'message' => $found 
                ? "Từ khóa có trong alt text ảnh. Tốt!" 
                : "Từ khóa chưa có trong alt text ảnh. Nên thêm để SEO hình ảnh tốt hơn.",
            'score' => $found ? 3 : 1,
            'maxScore' => 3,
        ];
    }
    
    private function checkKeywordUniqueness(string $keyword, ?int $postId): array
    {
        try {
            $query = DB::table('blog_posts')->where('focus_keyword', $keyword);
            if ($postId) {
                $query->where('id', '!=', $postId);
            }
            $duplicates = $query->count();
        } catch (\Exception $e) {
            $duplicates = 0;
        }
        
        return [
            'id' => 'keyword_unique',
            'category' => 'keyword',
            'status' => $duplicates === 0 ? 'good' : 'warning',
            'message' => $duplicates === 0 
                ? "Từ khóa này chưa được dùng trong bài khác. Tốt!" 
                : "Từ khóa này đã được dùng trong {$duplicates} bài khác. Nên chọn từ khóa khác để tránh cạnh tranh nội bộ.",
            'score' => $duplicates === 0 ? 5 : 1,
            'maxScore' => 5,
        ];
    }
    
    // ============================================================
    // Title Checks
    // ============================================================
    
    private function checkTitleLength(string $title): array
    {
        $len = mb_strlen($title);
        
        if ($len >= 30) {
            $status = 'good';
            $score = 8;
            $msg = "Tiêu đề SEO dài {$len} ký tự. ✅";
        } else {
            $status = 'bad';
            $score = 1;
            $msg = "Tiêu đề SEO quá ngắn ({$len} ký tự). Nên đạt ít nhất 30 ký tự.";
        }
        
        return [
            'id' => 'title_length',
            'category' => 'title',
            'status' => $status,
            'message' => $msg,
            'score' => $score,
            'maxScore' => 8,
        ];
    }
    
    private function checkTitleExists(string $metaTitle, string $title): array
    {
        $hasMetaTitle = !empty($metaTitle) && $metaTitle !== $title;
        
        return [
            'id' => 'meta_title_set',
            'category' => 'title',
            'status' => $hasMetaTitle ? 'good' : 'warning',
            'message' => $hasMetaTitle 
                ? "Đã đặt Meta Title riêng cho SEO. Tốt!" 
                : "Chưa đặt Meta Title riêng. Google sẽ dùng tiêu đề bài viết — nên tối ưu riêng.",
            'score' => $hasMetaTitle ? 3 : 1,
            'maxScore' => 3,
        ];
    }
    
    // ============================================================
    // Meta Description Checks
    // ============================================================
    
    private function checkMetaDescriptionLength(string $meta): array
    {
        $len = mb_strlen($meta);
        
        if ($len >= 120 && $len <= 160) {
            $status = 'good';
            $score = 8;
            $msg = "Meta description dài {$len} ký tự. Tối ưu (120-160)!";
        } elseif ($len >= 80 && $len < 120) {
            $status = 'warning';
            $score = 5;
            $msg = "Meta description dài {$len} ký tự. Hơi ngắn — nên đạt 120-160.";
        } elseif ($len > 160 && $len <= 200) {
            $status = 'warning';
            $score = 4;
            $msg = "Meta description dài {$len} ký tự. Hơi dài — có thể bị cắt trên Google.";
        } elseif ($len > 200) {
            $status = 'bad';
            $score = 2;
            $msg = "Meta description dài {$len} ký tự. Quá dài — sẽ bị cắt. Nên rút xuống 120-160.";
        } else {
            $status = 'bad';
            $score = 0;
            $msg = "Meta description quá ngắn hoặc chưa có ({$len} ký tự). Nên viết 120-160 ký tự.";
        }
        
        return [
            'id' => 'meta_desc_length',
            'category' => 'meta',
            'status' => $status,
            'message' => $msg,
            'score' => $score,
            'maxScore' => 8,
        ];
    }
    
    private function checkMetaDescriptionExists(string $meta): array
    {
        $exists = !empty(trim($meta));
        
        return [
            'id' => 'meta_desc_exists',
            'category' => 'meta',
            'status' => $exists ? 'good' : 'bad',
            'message' => $exists 
                ? "Có meta description. Tốt!" 
                : "Chưa có meta description! Google sẽ tự lấy đoạn văn bản — thường không tối ưu.",
            'score' => $exists ? 5 : 0,
            'maxScore' => 5,
        ];
    }
    
    // ============================================================
    // Content Checks
    // ============================================================
    
    private function checkContentLength(int $wordCount): array
    {
        if ($wordCount >= 1000) {
            $status = 'good';
            $score = 10;
            $msg = "Bài viết dài {$wordCount} từ. Tuyệt vời cho SEO!";
        } elseif ($wordCount >= 500) {
            $status = 'good';
            $score = 8;
            $msg = "Bài viết dài {$wordCount} từ. Đủ tốt. Nếu có thể, hãy bổ sung lên 1000+ từ.";
        } elseif ($wordCount >= 300) {
            $status = 'warning';
            $score = 5;
            $msg = "Bài viết dài {$wordCount} từ. Hơi ngắn — nên đạt 500+ từ để xếp hạng tốt.";
        } else {
            $status = 'bad';
            $score = 2;
            $msg = "Bài viết quá ngắn ({$wordCount} từ). Google ưu tiên nội dung dài 500+ từ.";
        }
        
        return [
            'id' => 'content_length',
            'category' => 'content',
            'status' => $status,
            'message' => $msg,
            'score' => $score,
            'maxScore' => 10,
        ];
    }
    
    private function checkHeadingStructure(string $htmlContent): array
    {
        preg_match_all('/<h([1-6])[^>]*>/i', $htmlContent, $matches);
        $headings = $matches[1] ?? [];
        
        if (empty($headings)) {
            return [
                'id' => 'heading_structure',
                'category' => 'content',
                'status' => 'bad',
                'message' => 'Bài viết không có heading (H2, H3...). Nên thêm heading để chia nội dung rõ ràng.',
                'score' => 0,
                'maxScore' => 5,
            ];
        }
        
        $hasH2 = in_array('2', $headings);
        $hasH1 = in_array('1', $headings);
        
        if ($hasH2 && !$hasH1) {
            $status = 'good';
            $score = 5;
            $msg = "Cấu trúc heading tốt: " . count($headings) . " heading. Bắt đầu từ H2 đúng chuẩn!";
        } elseif ($hasH1) {
            $status = 'warning';
            $score = 3;
            $msg = "Có H1 trong nội dung — H1 nên chỉ dùng cho tiêu đề bài viết, nội dung nên dùng H2, H3.";
        } else {
            $status = 'warning';
            $score = 3;
            $msg = count($headings) . " heading trong bài. Nên bắt đầu với H2 rồi H3.";
        }
        
        return [
            'id' => 'heading_structure',
            'category' => 'content',
            'status' => $status,
            'message' => $msg,
            'score' => $score,
            'maxScore' => 5,
        ];
    }
    
    private function checkInternalLinks(string $htmlContent): array
    {
        $count = $this->countInternalLinks($htmlContent);
        
        if ($count >= 3) {
            $status = 'good';
            $score = 5;
            $msg = "Có {$count} internal link. Tốt cho SEO!";
        } elseif ($count >= 1) {
            $status = 'warning';
            $score = 3;
            $msg = "Có {$count} internal link. Nên thêm 2-3 link nữa đến các bài liên quan.";
        } else {
            $status = 'bad';
            $score = 0;
            $msg = "Không có internal link! Hãy thêm link đến các bài viết/trang khác trên website.";
        }
        
        return [
            'id' => 'internal_links',
            'category' => 'content',
            'status' => $status,
            'message' => $msg,
            'score' => $score,
            'maxScore' => 5,
        ];
    }
    
    private function checkExternalLinks(string $htmlContent): array
    {
        $count = $this->countExternalLinks($htmlContent);
        
        if ($count >= 1 && $count <= 5) {
            $status = 'good';
            $score = 3;
            $msg = "Có {$count} external link. Tốt — cho thấy bài viết tham khảo nguồn uy tín.";
        } elseif ($count > 5) {
            $status = 'warning';
            $score = 2;
            $msg = "Có {$count} external link. Hơi nhiều — nên giữ dưới 5 để không mất link juice.";
        } else {
            $status = 'warning';
            $score = 1;
            $msg = "Chưa có external link. Thêm 1-2 link đến nguồn uy tín sẽ tốt hơn.";
        }
        
        return [
            'id' => 'external_links',
            'category' => 'content',
            'status' => $status,
            'message' => $msg,
            'score' => $score,
            'maxScore' => 3,
        ];
    }
    
    private function checkImageAltText(string $htmlContent): array
    {
        preg_match_all('/<img[^>]*>/i', $htmlContent, $allImages);
        preg_match_all('/<img[^>]+alt=["\']([^"\']+)["\'][^>]*>/i', $htmlContent, $withAlt);
        
        $totalImages = count($allImages[0]);
        $withAltCount = count($withAlt[0]);
        
        if ($totalImages === 0) {
            return [
                'id' => 'image_alt',
                'category' => 'content',
                'status' => 'warning',
                'message' => 'Bài viết chưa có hình ảnh. Nên thêm ảnh minh họa.',
                'score' => 2,
                'maxScore' => 5,
            ];
        }
        
        $missingAlt = $totalImages - $withAltCount;
        
        if ($missingAlt === 0) {
            $status = 'good';
            $score = 5;
            $msg = "Tất cả {$totalImages} ảnh đều có alt text. Tuyệt!";
        } elseif ($missingAlt <= 2) {
            $status = 'warning';
            $score = 3;
            $msg = "{$missingAlt}/{$totalImages} ảnh thiếu alt text. Nên bổ sung để SEO hình ảnh tốt hơn.";
        } else {
            $status = 'bad';
            $score = 1;
            $msg = "{$missingAlt}/{$totalImages} ảnh thiếu alt text! Hãy thêm alt text cho tất cả ảnh.";
        }
        
        return [
            'id' => 'image_alt',
            'category' => 'content',
            'status' => $status,
            'message' => $msg,
            'score' => $score,
            'maxScore' => 5,
        ];
    }
    
    private function checkImageCount(string $htmlContent): array
    {
        $count = $this->countImages($htmlContent);
        
        if ($count >= 2) {
            $status = 'good';
            $score = 3;
            $msg = "Có {$count} hình ảnh trong bài. Tốt!";
        } elseif ($count === 1) {
            $status = 'warning';
            $score = 2;
            $msg = "Chỉ có 1 hình ảnh. Nên thêm 1-2 ảnh nữa để bài viết sinh động hơn.";
        } else {
            $status = 'warning';
            $score = 1;
            $msg = "Bài viết không có hình ảnh. Nên thêm ít nhất 1 ảnh minh họa.";
        }
        
        return [
            'id' => 'image_count',
            'category' => 'content',
            'status' => $status,
            'message' => $msg,
            'score' => $score,
            'maxScore' => 3,
        ];
    }
    
    // ============================================================
    // Readability Checks
    // ============================================================
    
    private function checkParagraphLength(string $htmlContent): array
    {
        preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $htmlContent, $matches);
        
        if (empty($matches[1])) {
            return [
                'id' => 'paragraph_length',
                'category' => 'readability',
                'status' => 'warning',
                'message' => 'Nội dung chưa chia thành các đoạn văn (<p>). Nên chia để dễ đọc hơn.',
                'score' => 1,
                'maxScore' => 5,
            ];
        }
        
        $longParagraphs = 0;
        foreach ($matches[1] as $para) {
            $wordCount = $this->countWords(strip_tags($para));
            if ($wordCount > 150) {
                $longParagraphs++;
            }
        }
        
        if ($longParagraphs === 0) {
            $status = 'good';
            $score = 5;
            $msg = "Tất cả đoạn văn có độ dài phù hợp. Dễ đọc!";
        } elseif ($longParagraphs <= 2) {
            $status = 'warning';
            $score = 3;
            $msg = "{$longParagraphs} đoạn văn quá dài (>150 từ). Nên chia thành đoạn nhỏ hơn.";
        } else {
            $status = 'bad';
            $score = 1;
            $msg = "{$longParagraphs} đoạn văn quá dài! Rất khó đọc. Hãy chia thành đoạn 50-100 từ.";
        }
        
        return [
            'id' => 'paragraph_length',
            'category' => 'readability',
            'status' => $status,
            'message' => $msg,
            'score' => $score,
            'maxScore' => 5,
        ];
    }
    
    private function checkSubheadingDistribution(string $htmlContent): array
    {
        // Split content by headings and check if long text blocks exist without headings
        $parts = preg_split('/<h[2-6][^>]*>/i', $htmlContent);
        
        $longSections = 0;
        foreach ($parts as $part) {
            $wordCount = $this->countWords(strip_tags($part));
            if ($wordCount > 300) {
                $longSections++;
            }
        }
        
        if ($longSections === 0) {
            $status = 'good';
            $score = 5;
            $msg = "Nội dung được chia đều bằng heading. Dễ đọc!";
        } elseif ($longSections === 1) {
            $status = 'warning';
            $score = 3;
            $msg = "Có 1 phần nội dung dài >300 từ không có heading. Nên thêm heading chia nhỏ.";
        } else {
            $status = 'bad';
            $score = 1;
            $msg = "{$longSections} phần nội dung dài không có heading. Rất khó theo dõi!";
        }
        
        return [
            'id' => 'subheading_distribution',
            'category' => 'readability',
            'status' => $status,
            'message' => $msg,
            'score' => $score,
            'maxScore' => 5,
        ];
    }
    
    // ============================================================
    // Slug Check
    // ============================================================
    
    private function checkSlugLength(string $slug): array
    {
        $len = strlen($slug);
        
        if ($len > 0 && $len <= 75) {
            $status = 'good';
            $score = 3;
            $msg = "URL slug dài {$len} ký tự. Tốt!";
        } elseif ($len > 75) {
            $status = 'warning';
            $score = 1;
            $msg = "URL slug dài {$len} ký tự. Nên rút gọn dưới 75 ký tự.";
        } else {
            $status = 'bad';
            $score = 0;
            $msg = "Chưa có slug! Hãy thêm URL slug cho bài viết.";
        }
        
        return [
            'id' => 'slug_length',
            'category' => 'slug',
            'status' => $status,
            'message' => $msg,
            'score' => $score,
            'maxScore' => 3,
        ];
    }
    
    // ============================================================
    // Helper Methods
    // ============================================================
    
    private function countWords(string $text): int
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));
        if (empty($text)) return 0;
        return count(preg_split('/\s+/', $text));
    }
    
    private function countImages(string $html): int
    {
        preg_match_all('/<img[^>]*>/i', $html, $matches);
        return count($matches[0]);
    }
    
    private function countInternalLinks(string $html): int
    {
        preg_match_all('/<a[^>]+href=["\']([^"\']*)["\'][^>]*>/i', $html, $matches);
        $count = 0;
        foreach ($matches[1] as $href) {
            if (strpos($href, 'thuetaikhoan') !== false || 
                (strpos($href, '/') === 0 && strpos($href, '//') !== 0)) {
                $count++;
            }
        }
        return $count;
    }
    
    private function countExternalLinks(string $html): int
    {
        preg_match_all('/<a[^>]+href=["\']([^"\']*)["\'][^>]*>/i', $html, $matches);
        $count = 0;
        foreach ($matches[1] as $href) {
            if (preg_match('/^https?:\/\//i', $href) && strpos($href, 'thuetaikhoan') === false) {
                $count++;
            }
        }
        return $count;
    }
    
    private function vietnameseToSlug(string $text): string
    {
        $text = mb_strtolower($text);
        // Remove Vietnamese diacritics
        $text = preg_replace('/[àáạảãâầấậẩẫăằắặẳẵ]/u', 'a', $text);
        $text = preg_replace('/[èéẹẻẽêềếệểễ]/u', 'e', $text);
        $text = preg_replace('/[ìíịỉĩ]/u', 'i', $text);
        $text = preg_replace('/[òóọỏõôồốộổỗơờớợởỡ]/u', 'o', $text);
        $text = preg_replace('/[ùúụủũưừứựửữ]/u', 'u', $text);
        $text = preg_replace('/[ỳýỵỷỹ]/u', 'y', $text);
        $text = preg_replace('/đ/u', 'd', $text);
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        return trim($text, '-');
    }
    
    // ============================================================
    // Phase 3: Content Quality & Schema Generators
    // ============================================================
    
    /**
     * Check content depth — thin content and subheading ratio
     */
    private function checkContentDepth(string $html, int $wordCount): array
    {
        $score = 0;
        $maxScore = 5;
        $messages = [];
        
        // Thin content check
        if ($wordCount < 300) {
            $messages[] = "Nội dung quá mỏng ({$wordCount} từ). Google coi bài <300 từ là thin content. Nên viết tối thiểu 800 từ.";
            $status = 'bad';
        } elseif ($wordCount < 600) {
            $score = 2;
            $messages[] = "Nội dung ngắn ({$wordCount} từ). Nên mở rộng lên 800-1500 từ để cạnh tranh tốt hơn.";
            $status = 'warning';
        } elseif ($wordCount < 1000) {
            $score = 3;
            $messages[] = "Độ dài nội dung tốt ({$wordCount} từ). Có thể mở rộng thêm để tăng chuyên sâu.";
            $status = 'warning';
        } else {
            $score = 5;
            $messages[] = "Nội dung chuyên sâu ({$wordCount} từ). Tuyệt vời cho SEO!";
            $status = 'good';
        }
        
        // Subheading ratio: should have ~1 subheading per 300 words
        preg_match_all('/<h[2-6][^>]*>/i', $html, $headings);
        $headingCount = count($headings[0]);
        $expectedHeadings = max(1, floor($wordCount / 300));
        
        if ($headingCount < $expectedHeadings && $wordCount > 600) {
            $score = max(0, $score - 1);
            $messages[] = "Cần thêm subheading. Có {$headingCount} heading cho {$wordCount} từ (nên có ~{$expectedHeadings}).";
            $status = min($status, 'warning');
        }
        
        return [
            'id' => 'content_depth',
            'category' => 'quality',
            'status' => $status,
            'message' => implode(' ', $messages),
            'score' => $score,
            'maxScore' => $maxScore,
        ];
    }
    
    /**
     * Validate heading hierarchy (H1 in content, proper H2→H6 nesting)
     */
    private function checkHeadingHierarchy(string $html): array
    {
        preg_match_all('/<(h[1-6])[^>]*>(.*?)<\/\1>/is', $html, $matches);
        $headings = $matches[1] ?? [];
        
        if (empty($headings)) {
            return [
                'id' => 'heading_hierarchy',
                'category' => 'quality',
                'status' => 'warning',
                'message' => 'Không có heading nào trong nội dung. Nên thêm H2, H3 để cấu trúc bài viết.',
                'score' => 1,
                'maxScore' => 4,
            ];
        }
        
        $issues = [];
        $score = 4;
        
        // Check H1 in content (should not have H1 — page title is H1)
        $h1Count = count(array_filter($headings, fn($h) => strtolower($h) === 'h1'));
        if ($h1Count > 0) {
            $issues[] = "Có {$h1Count} thẻ H1 trong nội dung. Không nên dùng H1 — tiêu đề trang đã là H1.";
            $score -= 2;
        }
        
        // Check for skipped levels (e.g., H2 → H4 without H3)
        $prevLevel = 1; // Start from H1 (page title)
        foreach ($headings as $h) {
            $level = (int) substr($h, 1);
            if ($level > $prevLevel + 1) {
                $issues[] = "Nhảy cấp heading: H{$prevLevel} → H{$level}. Nên dùng H" . ($prevLevel + 1) . " trước.";
                $score -= 1;
                break;
            }
            $prevLevel = $level;
        }
        
        $score = max(0, $score);
        $status = $score >= 3 ? 'good' : ($score >= 2 ? 'warning' : 'bad');
        $msg = empty($issues) 
            ? "Cấu trúc heading chuẩn! (" . count($headings) . " heading, không H1, không nhảy cấp)" 
            : implode(' ', $issues);
        
        return [
            'id' => 'heading_hierarchy',
            'category' => 'quality',
            'status' => $status,
            'message' => $msg,
            'score' => $score,
            'maxScore' => 4,
        ];
    }
    
    /**
     * Image SEO check — alt tags, dimensions, lazy loading
     */
    private function checkImageSeo(string $html): array
    {
        preg_match_all('/<img[^>]*>/i', $html, $matches);
        $images = $matches[0] ?? [];
        
        if (empty($images)) {
            return [
                'id' => 'image_seo',
                'category' => 'quality',
                'status' => 'warning',
                'message' => 'Không có ảnh nào. Nên thêm ít nhất 1 ảnh minh họa.',
                'score' => 1,
                'maxScore' => 4,
            ];
        }
        
        $missingAlt = 0;
        $missingDimensions = 0;
        $missingLazy = 0;
        
        foreach ($images as $img) {
            if (!preg_match('/alt\s*=\s*["\'][^"\']+["\']/i', $img)) {
                $missingAlt++;
            }
            if (!preg_match('/width\s*=|height\s*=/i', $img)) {
                $missingDimensions++;
            }
            if (!preg_match('/loading\s*=\s*["\']lazy["\']/i', $img)) {
                $missingLazy++;
            }
        }
        
        $total = count($images);
        $issues = [];
        $score = 4;
        
        if ($missingAlt > 0) {
            $issues[] = "{$missingAlt}/{$total} ảnh thiếu alt text.";
            $score -= 2;
        }
        if ($missingDimensions > 0) {
            $issues[] = "{$missingDimensions}/{$total} ảnh thiếu width/height (gây CLS — Core Web Vitals).";
            $score -= 1;
        }
        if ($missingLazy > 0 && $total > 1) {
            $issues[] = "{$missingLazy}/{$total} ảnh thiếu loading=\"lazy\" (ảnh hưởng tốc độ tải).";
            $score -= 1;
        }
        
        $score = max(0, $score);
        $status = $score >= 3 ? 'good' : ($score >= 2 ? 'warning' : 'bad');
        $msg = empty($issues) 
            ? "Tất cả {$total} ảnh đều có alt, dimensions và lazy loading. Tuyệt vời!"
            : implode(' ', $issues);
        
        return [
            'id' => 'image_seo',
            'category' => 'quality',
            'status' => $status,
            'message' => $msg,
            'score' => $score,
            'maxScore' => 4,
        ];
    }
    
    /**
     * Keyword stuffing detection (>2.5% = over-optimization penalty risk)
     */
    private function checkKeywordStuffing(string $keyword, string $contentLower, int $wordCount): array
    {
        if ($wordCount === 0) {
            return [
                'id' => 'keyword_stuffing', 'category' => 'quality', 'status' => 'warning',
                'message' => 'Không có nội dung để phân tích keyword density.', 'score' => 0, 'maxScore' => 5,
            ];
        }
        
        $count = mb_substr_count($contentLower, $keyword);
        $density = ($count / $wordCount) * 100;
        $densityStr = number_format($density, 1);
        
        if ($density > 3.0) {
            return [
                'id' => 'keyword_stuffing', 'category' => 'quality', 'status' => 'bad',
                'message' => "⚠️ KEYWORD STUFFING! Mật độ \"{$keyword}\" = {$densityStr}% (>{3}%). Google sẽ phạt! Giảm xuống <2.5%.",
                'score' => 0, 'maxScore' => 5,
            ];
        } elseif ($density > 2.5) {
            return [
                'id' => 'keyword_stuffing', 'category' => 'quality', 'status' => 'warning',
                'message' => "Mật độ keyword hơi cao: {$densityStr}%. Nên giữ dưới 2.5% để tự nhiên.",
                'score' => 3, 'maxScore' => 5,
            ];
        } elseif ($density >= 0.5) {
            return [
                'id' => 'keyword_stuffing', 'category' => 'quality', 'status' => 'good',
                'message' => "Mật độ keyword tự nhiên: {$densityStr}% ({$count} lần). Hoàn hảo cho SEO!",
                'score' => 5, 'maxScore' => 5,
            ];
        } else {
            return [
                'id' => 'keyword_stuffing', 'category' => 'quality', 'status' => 'warning',
                'message' => "Mật độ keyword thấp: {$densityStr}%. Nên sử dụng keyword tự nhiên hơn (0.5-2.5%).",
                'score' => 2, 'maxScore' => 5,
            ];
        }
    }
    
    /**
     * Anchor text diversity — check internal link anchor text variety
     */
    private function checkAnchorTextDiversity(string $html): array
    {
        preg_match_all('/<a[^>]+href=["\']([^"\']*)["\'][^>]*>(.*?)<\/a>/is', $html, $matches);
        
        if (empty($matches[0])) {
            return [
                'id' => 'anchor_diversity', 'category' => 'quality', 'status' => 'warning',
                'message' => 'Không có link nào trong bài. Nên thêm internal link.', 'score' => 1, 'maxScore' => 3,
            ];
        }
        
        $anchors = [];
        foreach ($matches[2] as $i => $anchor) {
            $href = $matches[1][$i];
            // Only check internal links
            if (strpos($href, 'thuetaikhoan') !== false || (strpos($href, '/') === 0 && strpos($href, '//') !== 0)) {
                $cleanAnchor = mb_strtolower(trim(strip_tags($anchor)));
                if (!empty($cleanAnchor)) {
                    $anchors[] = $cleanAnchor;
                }
            }
        }
        
        if (empty($anchors)) {
            return [
                'id' => 'anchor_diversity', 'category' => 'quality', 'status' => 'warning',
                'message' => 'Không có internal link. Google dựa vào anchor text để hiểu cấu trúc site.', 'score' => 1, 'maxScore' => 3,
            ];
        }
        
        $unique = count(array_unique($anchors));
        $total = count($anchors);
        $diversity = $total > 0 ? $unique / $total : 0;
        
        if ($diversity >= 0.7) {
            return [
                'id' => 'anchor_diversity', 'category' => 'quality', 'status' => 'good',
                'message' => "Anchor text đa dạng ({$unique}/{$total} unique). Tự nhiên cho Google!", 'score' => 3, 'maxScore' => 3,
            ];
        } elseif ($diversity >= 0.4) {
            return [
                'id' => 'anchor_diversity', 'category' => 'quality', 'status' => 'warning',
                'message' => "Anchor text hơi lặp ({$unique}/{$total} unique). Nên dùng anchor text đa dạng hơn.", 'score' => 2, 'maxScore' => 3,
            ];
        } else {
            return [
                'id' => 'anchor_diversity', 'category' => 'quality', 'status' => 'bad',
                'message' => "Anchor text quá lặp ({$unique}/{$total} unique). Over-optimization risk!", 'score' => 0, 'maxScore' => 3,
            ];
        }
    }
    
    /**
     * Detect FAQ and HowTo schemas from content patterns
     */
    public function detectSchemas(string $html, string $title): array
    {
        $schemas = [];
        
        // Detect FAQ patterns
        $faqItems = $this->extractFaqItems($html);
        if (count($faqItems) >= 2) {
            $schemas['faq'] = [
                'detected' => true,
                'count' => count($faqItems),
                'items' => $faqItems,
                'jsonLd' => $this->generateFaqSchema($faqItems),
            ];
        }
        
        // Detect HowTo patterns
        $howToSteps = $this->extractHowToSteps($html, $title);
        if (count($howToSteps) >= 2) {
            $schemas['howTo'] = [
                'detected' => true,
                'count' => count($howToSteps),
                'steps' => $howToSteps,
                'jsonLd' => $this->generateHowToSchema($howToSteps, $title),
            ];
        }
        
        return $schemas;
    }
    
    /**
     * Extract FAQ items from content (Q&A patterns)
     */
    private function extractFaqItems(string $html): array
    {
        $items = [];
        
        // Pattern 1: Headings with question marks followed by content
        preg_match_all('/<h[2-4][^>]*>(.*?\?)<\/h[2-4]>(.*?)(?=<h[2-4]|$)/is', $html, $matches);
        foreach ($matches[1] as $i => $question) {
            $answer = trim(strip_tags($matches[2][$i]));
            if (mb_strlen($answer) > 20) {
                $items[] = [
                    'question' => trim(strip_tags($question)),
                    'answer' => mb_substr($answer, 0, 500),
                ];
            }
        }
        
        // Pattern 2: <strong> Q: / <strong> Hỏi: patterns
        preg_match_all('/<(?:strong|b)>\s*(?:Q|H[oỏ]i|C[aâ]u\s*h[oỏ]i)\s*[:\.]?\s*(.*?)<\/(?:strong|b)>\s*(.*?)(?=<(?:strong|b)>\s*(?:Q|H[oỏ]i)|$)/is', $html, $qMatches);
        foreach ($qMatches[1] as $i => $q) {
            $a = trim(strip_tags($qMatches[2][$i]));
            if (mb_strlen($a) > 20) {
                $items[] = [
                    'question' => trim(strip_tags($q)),
                    'answer' => mb_substr($a, 0, 500),
                ];
            }
        }
        
        return array_slice($items, 0, 10);
    }
    
    /**
     * Extract HowTo steps from content
     */
    private function extractHowToSteps(string $html, string $title): array
    {
        $steps = [];
        
        // Pattern 1: Ordered list items
        preg_match_all('/<li>(.*?)<\/li>/is', $html, $liMatches);
        // Only use if inside <ol>
        if (preg_match('/<ol[^>]*>(.*?)<\/ol>/is', $html, $olMatch)) {
            preg_match_all('/<li>(.*?)<\/li>/is', $olMatch[1], $olItems);
            foreach ($olItems[1] as $i => $item) {
                $text = trim(strip_tags($item));
                if (mb_strlen($text) > 10) {
                    $steps[] = [
                        'name' => 'Bước ' . ($i + 1),
                        'text' => mb_substr($text, 0, 300),
                    ];
                }
            }
        }
        
        // Pattern 2: Headings with "Bước X" or "Step X"
        if (empty($steps)) {
            preg_match_all('/<h[2-4][^>]*>(?:B[uư][oớ]c|Step)\s*(\d+)[:\.]?\s*(.*?)<\/h[2-4]>(.*?)(?=<h[2-4]|$)/is', $html, $stepMatches);
            foreach ($stepMatches[2] as $i => $stepTitle) {
                $text = trim(strip_tags($stepMatches[3][$i]));
                $steps[] = [
                    'name' => 'Bước ' . $stepMatches[1][$i] . ': ' . trim(strip_tags($stepTitle)),
                    'text' => mb_substr($text, 0, 300),
                ];
            }
        }
        
        return array_slice($steps, 0, 15);
    }
    
    /**
     * Generate FAQ Schema JSON-LD
     */
    public function generateFaqSchema(array $items): string
    {
        $entities = [];
        foreach ($items as $item) {
            $entities[] = [
                '@type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $item['answer'],
                ],
            ];
        }
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $entities,
        ];
        
        return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    
    /**
     * Generate HowTo Schema JSON-LD
     */
    public function generateHowToSchema(array $steps, string $title): string
    {
        $stepEntities = [];
        foreach ($steps as $step) {
            $stepEntities[] = [
                '@type' => 'HowToStep',
                'name' => $step['name'],
                'text' => $step['text'],
            ];
        }
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'HowTo',
            'name' => $title,
            'step' => $stepEntities,
        ];
        
        return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    
    /**
     * Generate Table of Contents from headings
     */
    public function generateToc(string $html): array
    {
        preg_match_all('/<(h[2-4])[^>]*(?:id=["\']([^"\']+)["\'])?[^>]*>(.*?)<\/\1>/is', $html, $matches);
        
        $toc = [];
        foreach ($matches[3] as $i => $heading) {
            $level = (int) substr($matches[1][$i], 1);
            $text = trim(strip_tags($heading));
            $id = $matches[2][$i] ?? $this->vietnameseToSlug($text);
            
            $toc[] = [
                'level' => $level,
                'text' => $text,
                'id' => $id,
            ];
        }
        
        return $toc;
    }
    
    /**
     * Add IDs to headings in HTML content for TOC anchoring
     */
    public function addHeadingIds(string $html): string
    {
        return preg_replace_callback('/<(h[2-4])([^>]*)>(.*?)<\/\1>/is', function($match) {
            $tag = $match[1];
            $attrs = $match[2];
            $text = $match[3];
            
            // Skip if already has id
            if (preg_match('/id\s*=\s*["\']/', $attrs)) {
                return $match[0];
            }
            
            $id = $this->vietnameseToSlug(strip_tags($text));
            return "<{$tag}{$attrs} id=\"{$id}\">{$text}</{$tag}>";
        }, $html);
    }
    
    /**
     * Count headings helper
     */
    private function countHeadings(string $html): array
    {
        $counts = [];
        for ($i = 1; $i <= 6; $i++) {
            preg_match_all("/<h{$i}[^>]*>/i", $html, $matches);
            $counts["h{$i}"] = count($matches[0]);
        }
        return $counts;
    }

    // ============================================================
    // ADVANCED SEO FEATURES
    // ============================================================
    
    /**
     * Detect stale content (not updated in X months)
     */
    public function staleContentAnalysis(int $months = 6): array
    {
        try {
            $cutoff = now()->subMonths($months);
            $posts = DB::table('blog_posts')
                ->where('status', 'published')
                ->where('updated_at', '<', $cutoff)
                ->orderBy('updated_at', 'asc')
                ->select('id', 'title', 'slug', 'updated_at', 'created_at', 'views')
                ->get();
            
            $results = [];
            foreach ($posts as $post) {
                $lastUpdate = \Carbon\Carbon::parse($post->updated_at);
                $daysSince = $lastUpdate->diffInDays(now());
                $monthsSince = $lastUpdate->diffInMonths(now());
                
                $urgency = 'low';
                if ($monthsSince >= 12) $urgency = 'critical';
                elseif ($monthsSince >= 9) $urgency = 'high';
                elseif ($monthsSince >= 6) $urgency = 'medium';
                
                $results[] = [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'last_updated' => $lastUpdate->format('d/m/Y'),
                    'days_since' => $daysSince,
                    'months_since' => $monthsSince,
                    'urgency' => $urgency,
                    'views' => $post->views ?? 0,
                ];
            }
            
            return $results;
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Detect orphaned content (no internal links pointing to it)
     */
    public function orphanedContentAnalysis(): array
    {
        try {
            $posts = DB::table('blog_posts')
                ->where('status', 'published')
                ->select('id', 'title', 'slug', 'content', 'views')
                ->get();
            
            // Build link map: which posts link to which
            $incomingLinks = []; // slug => count of links pointing to it
            foreach ($posts as $post) {
                $incomingLinks[$post->slug] = 0;
            }
            
            foreach ($posts as $post) {
                preg_match_all('/href=["\']([^"\']*)["\']/', $post->content, $matches);
                foreach ($matches[1] as $href) {
                    foreach ($posts as $target) {
                        if (str_contains($href, '/blog/' . $target->slug) && $target->id !== $post->id) {
                            $incomingLinks[$target->slug] = ($incomingLinks[$target->slug] ?? 0) + 1;
                        }
                    }
                }
            }
            
            $orphaned = [];
            foreach ($posts as $post) {
                if (($incomingLinks[$post->slug] ?? 0) === 0) {
                    $orphaned[] = [
                        'id' => $post->id,
                        'title' => $post->title,
                        'slug' => $post->slug,
                        'views' => $post->views ?? 0,
                        'incoming_links' => 0,
                    ];
                }
            }
            
            // Sort by views desc (high-traffic orphaned posts are priority)
            usort($orphaned, fn($a, $b) => $b['views'] - $a['views']);
            
            return $orphaned;
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Detect keyword cannibalization (multiple posts targeting same keyword)
     */
    public function keywordCannibalization(): array
    {
        try {
            $posts = DB::table('blog_posts')
                ->where('status', 'published')
                ->whereNotNull('focus_keyword')
                ->where('focus_keyword', '!=', '')
                ->select('id', 'title', 'slug', 'focus_keyword', 'views', 'meta_title')
                ->get();
            
            // Group by keyword
            $keywordGroups = [];
            foreach ($posts as $post) {
                $kw = mb_strtolower(trim($post->focus_keyword));
                if (empty($kw)) continue;
                
                if (!isset($keywordGroups[$kw])) {
                    $keywordGroups[$kw] = [];
                }
                $keywordGroups[$kw][] = [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'views' => $post->views ?? 0,
                    'meta_title' => $post->meta_title,
                ];
            }
            
            // Only return keywords with 2+ posts
            $cannibalized = [];
            foreach ($keywordGroups as $keyword => $posts) {
                if (count($posts) >= 2) {
                    $cannibalized[] = [
                        'keyword' => $keyword,
                        'count' => count($posts),
                        'posts' => $posts,
                    ];
                }
            }
            
            // Sort by count desc
            usort($cannibalized, fn($a, $b) => $b['count'] - $a['count']);
            
            return $cannibalized;
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Suggest internal links for a given post
     */
    public function internalLinkingSuggestions(int $postId, string $content, string $title): array
    {
        try {
            $posts = DB::table('blog_posts')
                ->where('status', 'published')
                ->where('id', '!=', $postId)
                ->select('id', 'title', 'slug', 'focus_keyword', 'category')
                ->get();
            
            $contentLower = mb_strtolower(strip_tags($content));
            $titleLower = mb_strtolower($title);
            
            // Extract already-linked slugs
            $alreadyLinked = [];
            preg_match_all('/href=["\']([^"\']*)["\']/', $content, $linkMatches);
            foreach ($linkMatches[1] as $href) {
                foreach ($posts as $p) {
                    if (str_contains($href, '/blog/' . $p->slug)) {
                        $alreadyLinked[] = $p->id;
                    }
                }
            }
            
            $suggestions = [];
            foreach ($posts as $post) {
                if (in_array($post->id, $alreadyLinked)) continue;
                
                $score = 0;
                $reasons = [];
                
                // Check if post keyword appears in content
                if (!empty($post->focus_keyword)) {
                    $kw = mb_strtolower($post->focus_keyword);
                    if (mb_strpos($contentLower, $kw) !== false) {
                        $score += 30;
                        $reasons[] = "Từ khóa \"{$post->focus_keyword}\" xuất hiện trong nội dung";
                    }
                }
                
                // Check if post title words appear in content
                $titleWords = array_filter(explode(' ', mb_strtolower($post->title)), fn($w) => mb_strlen($w) > 3);
                $matchCount = 0;
                foreach ($titleWords as $word) {
                    if (mb_strpos($contentLower, $word) !== false) {
                        $matchCount++;
                    }
                }
                if (count($titleWords) > 0 && $matchCount / count($titleWords) > 0.5) {
                    $score += 20;
                    $reasons[] = "Tiêu đề bài có nhiều từ trùng với nội dung";             
                }
                
                // Same category bonus
                if (!empty($post->category) && mb_strpos($titleLower, mb_strtolower($post->category)) !== false) {
                    $score += 10;
                    $reasons[] = "Cùng chủ đề";
                }
                
                if ($score >= 20) {
                    $suggestions[] = [
                        'id' => $post->id,
                        'title' => $post->title,
                        'slug' => $post->slug,
                        'score' => $score,
                        'url' => '/blog/' . $post->slug,
                        'reasons' => $reasons,
                    ];
                }
            }
            
            // Sort by relevance score
            usort($suggestions, fn($a, $b) => $b['score'] - $a['score']);
            
            return array_slice($suggestions, 0, 10);
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Generate social preview data (Facebook OG + Twitter Card)
     */
    public function socialPreviewData(array $data): array
    {
        $title = $data['meta_title'] ?: $data['title'] ?? '';
        $description = $data['meta_description'] ?: mb_substr(strip_tags($data['content'] ?? ''), 0, 160);
        $image = $data['image'] ?? '';
        $url = 'https://thuetaikhoan.net/blog/' . ($data['slug'] ?? '');
        
        return [
            'facebook' => [
                'title' => mb_substr($title, 0, 60),
                'description' => mb_substr($description, 0, 155),
                'image' => $image,
                'url' => $url,
                'site_name' => 'ThueTaiKhoan.net',
            ],
            'twitter' => [
                'title' => mb_substr($title, 0, 55),
                'description' => mb_substr($description, 0, 125),
                'image' => $image,
                'url' => $url,
                'card' => 'summary_large_image',
            ],
        ];
    }
    
    /**
     * Ping IndexNow API (Bing/Yandex instant indexing)
     */
    public function pingIndexNow(string $url): array
    {
        try {
            $apiKey = '5e4b892f3c7d4a01b8d2f0e6a9c35d78'; // IndexNow key
            $host = 'thuetaikhoan.net';
            
            $indexNowUrl = "https://api.indexnow.org/indexnow?" . http_build_query([
                'url' => $url,
                'key' => $apiKey,
            ]);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $indexNowUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                ],
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $success = in_array($httpCode, [200, 202]);
            
            return [
                'success' => $success,
                'http_code' => $httpCode,
                'message' => $success 
                    ? "IndexNow đã gửi thành công! Bing/Yandex sẽ index sớm." 
                    : "IndexNow gặp lỗi (HTTP {$httpCode}). URL vẫn được index bình thường qua sitemap.",
                'url' => $url,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'http_code' => 0,
                'message' => 'Lỗi kết nối IndexNow: ' . $e->getMessage(),
                'url' => $url,
            ];
        }
    }
    
    /**
     * Calculate sitemap priority based on SEO score
     */
    public function getSitemapPriority(object $post): string
    {
        $analysis = $this->quickScore($post);
        $score = $analysis['score'];
        
        if ($score >= 80) return '0.9';
        if ($score >= 60) return '0.7';
        if ($score >= 40) return '0.5';
        return '0.3';
    }
    
    /**
     * Bulk analysis for dashboard stats
     */
    public function dashboardAnalytics(): array
    {
        try {
            $posts = DB::table('blog_posts')
                ->where('status', 'published')
                ->select('id', 'title', 'slug', 'content', 'meta_title', 'meta_description', 
                         'focus_keyword', 'excerpt', 'views', 'updated_at', 'created_at')
                ->get();
            
            $totalPosts = $posts->count();
            $withKeyword = $posts->filter(fn($p) => !empty($p->focus_keyword))->count();
            $withMeta = $posts->filter(fn($p) => !empty($p->meta_description))->count();
            $withMetaTitle = $posts->filter(fn($p) => !empty($p->meta_title))->count();
            
            // Stale content count (>6 months)
            $staleCount = $posts->filter(fn($p) => 
                \Carbon\Carbon::parse($p->updated_at)->diffInMonths(now()) >= 6
            )->count();
            
            // Average word count
            $totalWords = 0;
            foreach ($posts as $post) {
                $totalWords += $this->countWords(strip_tags($post->content ?? ''));
            }
            $avgWordCount = $totalPosts > 0 ? round($totalWords / $totalPosts) : 0;
            
            return [
                'total_posts' => $totalPosts,
                'with_keyword' => $withKeyword,
                'without_keyword' => $totalPosts - $withKeyword,
                'with_meta_desc' => $withMeta,
                'without_meta_desc' => $totalPosts - $withMeta,
                'with_meta_title' => $withMetaTitle,
                'without_meta_title' => $totalPosts - $withMetaTitle,
                'stale_count' => $staleCount,
                'avg_word_count' => $avgWordCount,
                'keyword_coverage' => $totalPosts > 0 ? round($withKeyword / $totalPosts * 100) : 0,
                'meta_coverage' => $totalPosts > 0 ? round($withMeta / $totalPosts * 100) : 0,
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    // ============================
    // PHASE 4: Core Web Vitals & Technical SEO
    // ============================

    /**
     * Auto-add lazy loading + width/height to images for CWV (CLS fix)
     * Google rewards pages with proper image dimensions (prevents layout shift)
     */
    public function autoLazyLoading(string $html): string
    {
        return preg_replace_callback('/<img([^>]*)>/i', function($match) {
            $attrs = $match[1];
            
            // Add loading="lazy" if not present (skip first image - LCP)
            static $imgIndex = 0;
            $imgIndex++;
            
            if ($imgIndex > 1 && !preg_match('/loading\s*=/i', $attrs)) {
                $attrs .= ' loading="lazy"';
            }
            
            // Add decoding="async" if not present
            if (!preg_match('/decoding\s*=/i', $attrs)) {
                $attrs .= ' decoding="async"';
            }
            
            return '<img' . $attrs . '>';
        }, $html);
    }

    /**
     * Auto-generate alt text from image filename when missing
     * Google Search Essentials: "Use descriptive alt text for images"
     */
    public function autoImageAlt(string $html): string
    {
        return preg_replace_callback('/<img([^>]*)>/i', function($match) {
            $attrs = $match[1];
            
            // Skip if already has non-empty alt
            if (preg_match('/alt\s*=\s*["\']([^"\']+)["\']/i', $attrs)) {
                return $match[0];
            }
            
            // Extract filename from src
            if (preg_match('/src\s*=\s*["\']([^"\']+)["\']/i', $attrs, $srcMatch)) {
                $filename = pathinfo(parse_url($srcMatch[1], PHP_URL_PATH) ?: '', PATHINFO_FILENAME);
                
                // Clean filename: replace dashes/underscores with spaces, capitalize
                $alt = str_replace(['-', '_'], ' ', $filename);
                $alt = mb_convert_case(trim($alt), MB_CASE_TITLE, 'UTF-8');
                
                if (strlen($alt) > 2) {
                    // Remove existing empty alt="" if present
                    $attrs = preg_replace('/alt\s*=\s*["\']["\']/', '', $attrs);
                    $attrs .= ' alt="' . htmlspecialchars($alt, ENT_QUOTES) . '"';
                }
            }
            
            return '<img' . $attrs . '>';
        }, $html);
    }

    /**
     * Detect YouTube/Vimeo embeds and generate VideoObject schema (JSON-LD)
     * Google shows video thumbnails in SERP = higher CTR
     */
    public function detectVideoSchema(string $html, string $pageTitle, string $pageDescription = ''): ?string
    {
        $videos = [];
        
        // YouTube iframes
        if (preg_match_all('/(?:youtube\.com\/embed\/|youtu\.be\/)([a-zA-Z0-9_-]{11})/i', $html, $matches)) {
            foreach ($matches[1] as $videoId) {
                $videos[] = [
                    '@type' => 'VideoObject',
                    'name' => $pageTitle,
                    'description' => $pageDescription ?: $pageTitle,
                    'thumbnailUrl' => "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg",
                    'uploadDate' => date('c'),
                    'contentUrl' => "https://www.youtube.com/watch?v={$videoId}",
                    'embedUrl' => "https://www.youtube.com/embed/{$videoId}",
                ];
            }
        }
        
        // YouTube URLs in content (not iframes)
        if (preg_match_all('/https?:\/\/(?:www\.)?youtube\.com\/watch\?v=([a-zA-Z0-9_-]{11})/i', $html, $matches)) {
            foreach ($matches[1] as $videoId) {
                // Avoid duplicates
                $exists = false;
                foreach ($videos as $v) {
                    if (strpos($v['contentUrl'] ?? '', $videoId) !== false) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $videos[] = [
                        '@type' => 'VideoObject',
                        'name' => $pageTitle,
                        'description' => $pageDescription ?: $pageTitle,
                        'thumbnailUrl' => "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg",
                        'uploadDate' => date('c'),
                        'contentUrl' => "https://www.youtube.com/watch?v={$videoId}",
                        'embedUrl' => "https://www.youtube.com/embed/{$videoId}",
                    ];
                }
            }
        }
        
        if (empty($videos)) return null;
        
        if (count($videos) === 1) {
            $schema = array_merge(['@context' => 'https://schema.org'], $videos[0]);
        } else {
            $schema = [
                '@context' => 'https://schema.org',
                '@graph' => $videos,
            ];
        }
        
        return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    /**
     * Check for broken internal links (returns list of potentially broken URLs)
     * Google Search Quality: "Avoid broken links that create bad user experience"
     */
    public function checkBrokenLinks(string $html): array
    {
        $brokenLinks = [];
        $siteUrl = url('/');
        
        // Extract all internal links
        preg_match_all('/href\s*=\s*["\']([^"\']*)["\']/', $html, $matches);
        
        foreach ($matches[1] as $url) {
            // Skip external links, anchors, mailto, tel, javascript
            if (preg_match('/^(https?:\/\/(?!thuetaikhoan\.net)|mailto:|tel:|javascript:|#)/i', $url)) {
                continue;
            }
            
            // Normalize relative URLs
            if (strpos($url, '/') === 0) {
                $fullUrl = $siteUrl . $url;
            } elseif (strpos($url, 'http') !== 0) {
                continue; // Skip non-URL values
            } else {
                $fullUrl = $url;
            }
            
            // Check blog post links against database
            if (preg_match('/\/blog\/([a-z0-9\-]+)/', $fullUrl, $slugMatch)) {
                $exists = DB::table('blog_posts')
                    ->where('slug', $slugMatch[1])
                    ->where('status', 'published')
                    ->exists();
                
                if (!$exists) {
                    $brokenLinks[] = [
                        'url' => $fullUrl,
                        'type' => 'blog_post',
                        'reason' => 'Post not found or not published',
                    ];
                }
            }
            
            // Check service page links
            $validServicePaths = [
                '/thue-unlocktool', '/thue-vietmap-live-pro', '/thue-griffin',
                '/thue-amt', '/thue-tsm', '/thue-dft', '/thue-samsung-tool',
                '/thue-kg-killer', '/blog', '/',
            ];
            if (preg_match('/^\/(thue-[a-z\-]+)/', $url, $pathMatch)) {
                $found = false;
                foreach ($validServicePaths as $validPath) {
                    if (strpos($url, $validPath) === 0) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $brokenLinks[] = [
                        'url' => $fullUrl,
                        'type' => 'service_page',
                        'reason' => 'Service page may not exist',
                    ];
                }
            }
        }
        
        return $brokenLinks;
    }

    /**
     * Check outbound link quality — warn if linking to suspicious domains
     * Google: "Link to reputable, relevant sources"
     */
    public function checkOutboundLinks(string $html): array
    {
        $score = 10;
        $maxScore = 10;
        $messages = [];
        $externalLinks = [];
        
        preg_match_all('/href\s*=\s*["\']https?:\/\/([^"\'\/]+)/i', $html, $matches);
        
        $ownDomains = ['thuetaikhoan.net', 'www.thuetaikhoan.net'];
        
        foreach ($matches[1] as $domain) {
            $domain = strtolower($domain);
            if (in_array($domain, $ownDomains)) continue;
            
            $externalLinks[] = $domain;
        }
        
        $uniqueDomains = array_unique($externalLinks);
        $externalCount = count($externalLinks);
        
        // Trusted domains (no penalty)
        $trustedDomains = [
            'youtube.com', 'www.youtube.com', 'youtu.be',
            'google.com', 'www.google.com', 'maps.google.com',
            'facebook.com', 'www.facebook.com', 'fb.com',
            'zalo.me', 'tiktok.com', 'www.tiktok.com',
            'apple.com', 'play.google.com',
            'vietmap.vn', 'www.vietmap.vn',
            'github.com', 'stackoverflow.com',
            'wikipedia.org', 'en.wikipedia.org', 'vi.wikipedia.org',
        ];
        
        $suspiciousCount = 0;
        foreach ($uniqueDomains as $domain) {
            $isTrusted = false;
            foreach ($trustedDomains as $trusted) {
                if ($domain === $trusted || str_ends_with($domain, '.' . $trusted)) {
                    $isTrusted = true;
                    break;
                }
            }
            if (!$isTrusted) {
                $suspiciousCount++;
            }
        }
        
        if ($externalCount === 0) {
            $messages[] = 'Không có outbound link. Thêm 1-2 link uy tín để tăng E-E-A-T.';
            $score = 6;
        } elseif ($suspiciousCount > 3) {
            $messages[] = "Có {$suspiciousCount} domain ngoài không rõ uy tín. Kiểm tra lại!";
            $score = 4;
        } else {
            $messages[] = "Có {$externalCount} outbound link tới " . count($uniqueDomains) . " domain. OK!";
        }
        
        // Check for nofollow on external links
        $externalWithoutNofollow = 0;
        preg_match_all('/<a([^>]*)href\s*=\s*["\']https?:\/\/(?!thuetaikhoan\.net)[^"\']+["\']([^>]*)>/i', $html, $linkMatches);
        foreach ($linkMatches[0] as $link) {
            if (!preg_match('/rel\s*=\s*["\'][^"\']*nofollow/i', $link)) {
                $externalWithoutNofollow++;
            }
        }
        
        if ($externalWithoutNofollow > 0) {
            $messages[] = "{$externalWithoutNofollow} link ngoài chưa có rel=\"nofollow\". Nên thêm cho link không chắc chắn.";
            if ($externalWithoutNofollow > 3) $score = max($score - 2, 2);
        }
        
        return [
            'group' => 'Outbound Links',
            'title' => 'Chất lượng link ra ngoài',
            'score' => $score,
            'maxScore' => $maxScore,
            'status' => $score >= 8 ? 'good' : ($score >= 5 ? 'warning' : 'bad'),
            'message' => implode(' ', $messages),
            'details' => [
                'total' => $externalCount,
                'unique_domains' => count($uniqueDomains),
                'suspicious' => $suspiciousCount,
                'without_nofollow' => $externalWithoutNofollow,
            ],
        ];
    }

    /**
     * Auto-generate smart meta description from content
     * Google: "Meta descriptions can influence click-through rates"
     */
    public function autoMetaDescription(string $content, string $title, int $maxLength = 155): string
    {
        $plain = strip_tags($content);
        $plain = preg_replace('/\s+/', ' ', trim($plain));
        
        if (mb_strlen($plain) < 50) {
            return '';
        }
        
        // Try to get first meaningful paragraph (skip very short ones)
        $paragraphs = preg_split('/[.\n]+/', $plain, -1, PREG_SPLIT_NO_EMPTY);
        $desc = '';
        
        foreach ($paragraphs as $p) {
            $p = trim($p);
            if (mb_strlen($p) >= 40) {
                $desc = $p;
                break;
            }
        }
        
        if (empty($desc)) {
            $desc = $plain;
        }
        
        // Truncate to max length at word boundary
        if (mb_strlen($desc) > $maxLength) {
            $desc = mb_substr($desc, 0, $maxLength);
            $lastSpace = mb_strrpos($desc, ' ');
            if ($lastSpace > $maxLength * 0.7) {
                $desc = mb_substr($desc, 0, $lastSpace);
            }
            $desc = rtrim($desc, '.,;:!? ');
        }
        
        return $desc;
    }

    /**
     * Generate Sitelinks Search Box schema for homepage
     * Makes Google show a search box in SERP sitelinks
     */
    public static function generateSitelinksSearchBox(): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'url' => url('/'),
            'name' => 'Thuetaikhoan.net',
            'description' => 'Hệ thống cho thuê tài khoản UnlockTool, Vietmap Live Pro, Griffin, TSM Tool tự động 24/7',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => url('/blog') . '?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
        
        return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    /**
     * Generate Organization schema for homepage
     * Helps Google Knowledge Panel with business info
     */
    public static function generateOrganizationSchema(): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'Thuetaikhoan.net',
            'url' => url('/'),
            'logo' => asset('assets/images/logo.webp'),
            'description' => 'Hệ thống cho thuê tài khoản phần mềm GSM uy tín, tự động 24/7',
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'telephone' => '+84-799-161-640',
                'contactType' => 'customer service',
                'areaServed' => 'VN',
                'availableLanguage' => 'Vietnamese',
            ],
            'sameAs' => [
                'https://www.facebook.com/thuetaikhoan.net',
                'https://zalo.me/0777333763',
            ],
        ];
        
        return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    /**
     * Mobile-friendliness check for content
     * Google uses Mobile-First Indexing since 2021
     */
    public function checkMobileFriendliness(string $html): array
    {
        $score = 10;
        $maxScore = 10;
        $messages = [];
        
        // Check for fixed-width elements
        if (preg_match('/width\s*:\s*\d{4,}px/i', $html)) {
            $messages[] = 'Phát hiện element có fixed width lớn (>999px). Sẽ bị tràn trên mobile.';
            $score -= 3;
        }
        
        // Check for large tables without responsive wrapper
        if (preg_match('/<table[^>]*>/i', $html) && !preg_match('/overflow[-x]?\s*:\s*auto/i', $html)) {
            $messages[] = 'Table không có overflow:auto. Sẽ bị tràn trên mobile.';
            $score -= 2;
        }
        
        // Check for small font sizes
        if (preg_match('/font-size\s*:\s*(\d+)px/i', $html, $fontMatch)) {
            if ((int)$fontMatch[1] < 12) {
                $messages[] = 'Có font-size < 12px. Google khuyến nghị tối thiểu 16px cho body text.';
                $score -= 2;
            }
        }
        
        // Check images without max-width
        preg_match_all('/<img[^>]*style\s*=\s*["\']([^"\']*)["\'][^>]*>/i', $html, $imgStyles);
        foreach ($imgStyles[1] as $style) {
            if (preg_match('/width\s*:\s*\d{4,}px/i', $style) && !preg_match('/max-width/i', $style)) {
                $messages[] = 'Ảnh có fixed width lớn không có max-width. Sẽ bị tràn trên mobile.';
                $score -= 2;
                break;
            }
        }
        
        // Check for tap targets too close together
        if (preg_match_all('/<a[^>]*>/i', $html, $aMatches) && count($aMatches[0]) > 20) {
            // Many links in content could mean tap targets too close on mobile
            $messages[] = 'Có nhiều link (' . count($aMatches[0]) . '). Đảm bảo khoảng cách đủ lớn trên mobile.';
            if (count($aMatches[0]) > 40) $score -= 1;
        }
        
        if (empty($messages)) {
            $messages[] = 'Nội dung phù hợp với mobile. Tốt!';
        }
        
        return [
            'group' => 'Mobile SEO',
            'title' => 'Mobile-friendliness',
            'score' => max($score, 0),
            'maxScore' => $maxScore,
            'status' => $score >= 8 ? 'good' : ($score >= 5 ? 'warning' : 'bad'),
            'message' => implode(' ', $messages),
        ];
    }

    // ============================
    // PHASE 5: Advanced SEO Features
    // ============================

    /**
     * Auto Internal Linking — suggest and auto-insert links to related posts
     * Google: "Internal links help Google understand the structure of your site"
     */
    public function autoInternalLinking(string $html, int $currentPostId = null): array
    {
        $suggestions = [];
        
        try {
            // Get published posts with their titles and slugs
            $posts = DB::table('blog_posts')
                ->where('status', 'published')
                ->when($currentPostId, fn($q) => $q->where('id', '!=', $currentPostId))
                ->select('id', 'title', 'slug', 'category', 'focus_keyword')
                ->get();
            
            $plainContent = strtolower(strip_tags($html));
            
            foreach ($posts as $post) {
                // Check if post title keywords appear in content
                $titleWords = array_filter(explode(' ', strtolower($post->title)), fn($w) => mb_strlen($w) > 3);
                $matchCount = 0;
                
                foreach ($titleWords as $word) {
                    if (mb_strpos($plainContent, $word) !== false) {
                        $matchCount++;
                    }
                }
                
                // If more than 40% of title words match, suggest link
                if (count($titleWords) > 0 && ($matchCount / count($titleWords)) >= 0.4) {
                    $suggestions[] = [
                        'post_id' => $post->id,
                        'title' => $post->title,
                        'slug' => $post->slug,
                        'category' => $post->category,
                        'url' => url('/blog/' . $post->slug),
                        'relevance' => round($matchCount / count($titleWords) * 100),
                        'focus_keyword' => $post->focus_keyword,
                    ];
                }
            }
            
            // Sort by relevance descending
            usort($suggestions, fn($a, $b) => $b['relevance'] <=> $a['relevance']);
            
            // Limit to top 5 suggestions
            $suggestions = array_slice($suggestions, 0, 5);
            
        } catch (\Exception $e) {
            // Silently fail
        }
        
        return $suggestions;
    }

    /**
     * Auto-insert internal links into content for the first mention of related keywords
     * Only inserts if the keyword/phrase isn't already linked
     */
    public function insertInternalLinks(string $html, int $currentPostId = null, int $maxLinks = 3): string
    {
        try {
            $posts = DB::table('blog_posts')
                ->where('status', 'published')
                ->when($currentPostId, fn($q) => $q->where('id', '!=', $currentPostId))
                ->whereNotNull('focus_keyword')
                ->where('focus_keyword', '!=', '')
                ->select('title', 'slug', 'focus_keyword')
                ->get();
            
            $linksInserted = 0;
            
            foreach ($posts as $post) {
                if ($linksInserted >= $maxLinks) break;
                
                $keyword = $post->focus_keyword;
                if (mb_strlen($keyword) < 4) continue;
                
                // Check if keyword appears in content and is NOT already linked
                $pattern = '/(?<!["\'>])(' . preg_quote($keyword, '/') . ')(?![^<]*>)(?![^<]*<\/a>)/iu';
                
                if (preg_match($pattern, $html)) {
                    $url = url('/blog/' . $post->slug);
                    $replacement = '<a href="' . $url . '" title="' . htmlspecialchars($post->title) . '">${1}</a>';
                    
                    // Replace only the first occurrence
                    $html = preg_replace($pattern, $replacement, $html, 1);
                    $linksInserted++;
                }
            }
        } catch (\Exception $e) {
            // Silently fail
        }
        
        return $html;
    }

    /**
     * Content Freshness Score — Google values dateModified signal
     * Google: "Freshness is important for certain queries"
     */
    public function checkContentFreshness(?string $updatedAt, ?string $createdAt): array
    {
        $score = 10;
        $maxScore = 10;
        $messages = [];
        
        if (empty($updatedAt) && empty($createdAt)) {
            return [
                'group' => 'Freshness',
                'title' => 'Độ mới nội dung',
                'score' => 5,
                'maxScore' => $maxScore,
                'status' => 'warning',
                'message' => 'Không có thông tin ngày tạo/cập nhật.',
            ];
        }
        
        $lastUpdate = $updatedAt ?: $createdAt;
        $daysSinceUpdate = now()->diffInDays(\Carbon\Carbon::parse($lastUpdate));
        
        if ($daysSinceUpdate <= 30) {
            $messages[] = "Nội dung mới ({$daysSinceUpdate} ngày). Rất tốt cho SEO!";
        } elseif ($daysSinceUpdate <= 90) {
            $messages[] = "Nội dung đã {$daysSinceUpdate} ngày chưa cập nhật. Vẫn ổn.";
            $score = 7;
        } elseif ($daysSinceUpdate <= 180) {
            $messages[] = "⚠️ Nội dung {$daysSinceUpdate} ngày chưa cập nhật. Nên review và refresh.";
            $score = 5;
        } else {
            $messages[] = "🔴 Nội dung đã {$daysSinceUpdate} ngày lỗi thời! Cập nhật ngay để giữ ranking.";
            $score = 2;
        }
        
        return [
            'group' => 'Freshness',
            'title' => 'Độ mới nội dung',
            'score' => $score,
            'maxScore' => $maxScore,
            'status' => $score >= 8 ? 'good' : ($score >= 5 ? 'warning' : 'bad'),
            'message' => implode(' ', $messages),
            'days_since_update' => $daysSinceUpdate,
        ];
    }

    /**
     * Readability Score for Vietnamese content
     * Based on average sentence length + average word length metrics
     * Google: "Content should be easy to read and understand"
     */
    public function readabilityScore(string $content): array
    {
        $score = 10;
        $maxScore = 10;
        $messages = [];
        
        $plain = strip_tags($content);
        $plain = preg_replace('/\s+/', ' ', trim($plain));
        
        if (mb_strlen($plain) < 100) {
            return [
                'group' => 'Readability',
                'title' => 'Độ dễ đọc',
                'score' => 5,
                'maxScore' => $maxScore,
                'status' => 'warning',
                'message' => 'Nội dung quá ngắn để đánh giá độ dễ đọc.',
            ];
        }
        
        // Count sentences
        $sentences = preg_split('/[.!?。]+/u', $plain, -1, PREG_SPLIT_NO_EMPTY);
        $sentenceCount = count($sentences);
        
        // Count words
        $words = preg_split('/\s+/', $plain, -1, PREG_SPLIT_NO_EMPTY);
        $wordCount = count($words);
        
        // Average sentence length
        $avgSentenceLength = $sentenceCount > 0 ? $wordCount / $sentenceCount : 0;
        
        // Average word length (in characters)
        $totalCharLen = array_sum(array_map('mb_strlen', $words));
        $avgWordLength = $wordCount > 0 ? $totalCharLen / $wordCount : 0;
        
        // Vietnamese readability heuristics
        // Ideal: 15-25 words/sentence, 2-5 chars/word
        
        if ($avgSentenceLength > 35) {
            $avgLen = $sentenceCount > 0 ? round($avgSentenceLength) : '?';
            $messages[] = "Câu quá dài (trung bình {$avgLen} từ/câu). Nên chia nhỏ câu.";
            $score -= 3;
        } elseif ($avgSentenceLength > 25) {
            $messages[] = "Câu hơi dài (trung bình " . round($avgSentenceLength) . " từ/câu). Thêm câu ngắn.";
            $score -= 1;
        } else {
            $messages[] = "Độ dài câu OK (" . round($avgSentenceLength) . " từ/câu).";
        }
        
        // Check for very long paragraphs (>300 words without a break)
        $paragraphs = preg_split('/<\/p>|<br\s*\/?>|\n\n/i', $content, -1, PREG_SPLIT_NO_EMPTY);
        $longParagraphs = 0;
        foreach ($paragraphs as $p) {
            $pWords = str_word_count(strip_tags($p));
            if ($pWords > 300) $longParagraphs++;
        }
        if ($longParagraphs > 0) {
            $messages[] = "{$longParagraphs} đoạn văn quá dài (>300 từ). Nên chia thành nhiều đoạn.";
            $score -= 2;
        }
        
        // Check for subheadings distribution (every 300 words should have a heading)
        preg_match_all('/<h[2-6][^>]*>/i', $content, $headings);
        $headingCount = count($headings[0]);
        $wordsPerHeading = $headingCount > 0 ? $wordCount / $headingCount : $wordCount;
        
        if ($wordCount > 300 && $headingCount === 0) {
            $messages[] = 'Thiếu tiêu đề phụ (H2-H6). Nên thêm để chia nhỏ nội dung.';
            $score -= 2;
        } elseif ($wordsPerHeading > 400) {
            $messages[] = "Khoảng cách giữa các tiêu đề quá xa (" . round($wordsPerHeading) . " từ/heading).";
            $score -= 1;
        }
        
        if (empty($messages) || $score >= 9) {
            $messages[] = 'Nội dung dễ đọc, cấu trúc tốt. 👍';
        }
        
        return [
            'group' => 'Readability',
            'title' => 'Độ dễ đọc',
            'score' => max($score, 0),
            'maxScore' => $maxScore,
            'status' => $score >= 8 ? 'good' : ($score >= 5 ? 'warning' : 'bad'),
            'message' => implode(' ', $messages),
            'details' => [
                'avg_sentence_length' => round($avgSentenceLength, 1),
                'avg_word_length' => round($avgWordLength, 1),
                'sentence_count' => $sentenceCount,
                'word_count' => $wordCount,
                'heading_count' => $headingCount,
            ],
        ];
    }

    /**
     * Phase 5.5: Related Keyphrases Analysis
     * Like Yoast Premium — check up to 5 additional keyphrases
     */
    public function checkRelatedKeyphrases(string $keyphrases, string $plainContentLower, string $title, string $metaDescription): array
    {
        $keywords = array_filter(
            array_map('trim', explode(',', strtolower($keyphrases)))
        );
        
        // Max 5 related keyphrases
        $keywords = array_slice($keywords, 0, 5);
        
        if (empty($keywords)) {
            return [
                'group' => 'Related Keyphrases',
                'title' => 'Từ khóa phụ',
                'score' => 0,
                'maxScore' => 5,
                'status' => 'warning',
                'message' => 'Chưa có từ khóa phụ.',
            ];
        }
        
        $found = 0;
        $details = [];
        $titleLower = mb_strtolower($title);
        $metaLower = mb_strtolower($metaDescription);
        
        foreach ($keywords as $kw) {
            if (empty($kw)) continue;
            
            $inContent = mb_strpos($plainContentLower, $kw) !== false;
            $inTitle = mb_strpos($titleLower, $kw) !== false;
            $inMeta = mb_strpos($metaLower, $kw) !== false;
            
            // Count occurrences in content
            $count = mb_substr_count($plainContentLower, $kw);
            
            if ($inContent) $found++;
            
            $details[] = [
                'keyword' => $kw,
                'in_content' => $inContent,
                'in_title' => $inTitle,
                'in_meta' => $inMeta,
                'occurrences' => $count,
            ];
        }
        
        $total = count($keywords);
        $score = min($found, 5);
        $percentage = round(($found / $total) * 100);
        
        $messages = [];
        foreach ($details as $d) {
            $status = $d['in_content'] ? '✅' : '❌';
            $extra = [];
            if ($d['in_title']) $extra[] = 'title';
            if ($d['in_meta']) $extra[] = 'meta';
            $extraStr = !empty($extra) ? ' (cũng trong ' . implode(', ', $extra) . ')' : '';
            $messages[] = "{$status} \"{$d['keyword']}\" — {$d['occurrences']}x trong nội dung{$extraStr}";
        }
        
        return [
            'group' => 'Related Keyphrases',
            'title' => "Từ khóa phụ ({$found}/{$total})",
            'score' => $score,
            'maxScore' => 5,
            'status' => $percentage >= 80 ? 'good' : ($percentage >= 40 ? 'warning' : 'bad'),
            'message' => implode(' | ', $messages),
            'details' => $details,
        ];
    }

    /**
     * Enhanced Image Sitemap data extraction
     * Google: "Image sitemaps help Google discover images on your site"
     */
    public function extractImageSitemapData(string $html, string $pageUrl, string $pageTitle): array
    {
        $images = [];
        
        preg_match_all('/<img[^>]*src\s*=\s*["\']([^"\']+)["\'][^>]*>/i', $html, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $src = $match[1];
            
            // Skip data URIs and external images
            if (strpos($src, 'data:') === 0) continue;
            
            // Make absolute URL
            if (strpos($src, 'http') !== 0) {
                if (strpos($src, '/') === 0) {
                    $src = url($src);
                } else {
                    continue;
                }
            }
            
            // Extract alt and title
            $alt = '';
            if (preg_match('/alt\s*=\s*["\']([^"\']*)["\']/', $match[0], $altMatch)) {
                $alt = $altMatch[1];
            }
            
            $title = '';
            if (preg_match('/title\s*=\s*["\']([^"\']*)["\']/', $match[0], $titleMatch)) {
                $title = $titleMatch[1];
            }
            
            $images[] = [
                'loc' => $src,
                'caption' => $alt ?: $title ?: $pageTitle,
                'title' => $title ?: $alt ?: $pageTitle,
                'page_url' => $pageUrl,
            ];
        }
        
        return $images;
    }

    /**
     * Get all 301 redirects from database
     * Support for admin Redirect Manager
     */
    public static function getRedirects(): array
    {
        try {
            return DB::table('seo_redirects')
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Add a 301 redirect
     */
    public static function addRedirect(string $fromUrl, string $toUrl, int $statusCode = 301): bool
    {
        try {
            DB::table('seo_redirects')->insert([
                'from_url' => $fromUrl,
                'to_url' => $toUrl,
                'status_code' => $statusCode,
                'hits' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Delete a redirect
     */
    public static function deleteRedirect(int $id): bool
    {
        try {
            return DB::table('seo_redirects')->where('id', $id)->delete() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Process redirect — check if current URL has a redirect rule
     */
    public static function processRedirect(string $path): ?array
    {
        try {
            $redirect = DB::table('seo_redirects')
                ->where('from_url', $path)
                ->first();
            
            if ($redirect) {
                // Increment hit counter
                DB::table('seo_redirects')
                    ->where('id', $redirect->id)
                    ->increment('hits');
                
                return [
                    'to_url' => $redirect->to_url,
                    'status_code' => $redirect->status_code,
                ];
            }
        } catch (\Exception $e) {
            // Table doesn't exist yet
        }
        
        return null;
    }

    /**
     * Generate Structured Data Test URL for Google Rich Results Test
     */
    public static function getStructuredDataTestUrl(string $pageUrl): string
    {
        return 'https://search.google.com/test/rich-results?url=' . urlencode($pageUrl);
    }

    // ============================
    // PHASE 6: Yoast Premium Parity
    // ============================

    /**
     * Content Insights — analyze most prominent words/phrases in content
     * Like Yoast Premium: shows how search engines perceive the content
     * Returns top words and 2-gram phrases with frequencies
     */
    public function contentInsights(string $content): array
    {
        $plain = strip_tags($content);
        $plain = preg_replace('/\s+/', ' ', trim($plain));
        $plain = mb_strtolower($plain);
        
        if (mb_strlen($plain) < 50) {
            return [
                'group' => 'Content Insights',
                'title' => 'Phân tích nội dung',
                'score' => 0,
                'maxScore' => 10,
                'status' => 'warning',
                'message' => 'Nội dung quá ngắn để phân tích.',
                'top_words' => [],
                'top_phrases' => [],
            ];
        }
        
        // Vietnamese stop words to filter out
        $stopWords = [
            'và', 'của', 'là', 'có', 'được', 'cho', 'này', 'với', 'các', 'những',
            'trong', 'đã', 'không', 'một', 'như', 'từ', 'để', 'khi', 'thì', 'bạn',
            'đến', 'nên', 'hay', 'hoặc', 'cũng', 'nhưng', 'mà', 'vì', 'nếu', 'thì',
            'bởi', 'tại', 'do', 'sẽ', 'về', 'qua', 'theo', 'trên', 'dưới', 'nào',
            'đó', 'rất', 'hơn', 'lại', 'còn', 'chỉ', 'đang', 'vẫn', 'nó', 'mới',
            'hết', 'tôi', 'chúng', 'bị', 'làm', 'cần', 'phải', 'giúp', 'việc',
            'sau', 'trước', 'lên', 'xuống', 'vào', 'ra', 'ở', 'đây', 'đều',
            'the', 'and', 'for', 'are', 'but', 'not', 'you', 'all', 'can', 'her',
            'was', 'one', 'our', 'out', 'this', 'that', 'with', 'have', 'from',
        ];
        
        // Split into words
        $words = preg_split('/[\s,;:.!?()"\[\]{}\/\\\\]+/u', $plain, -1, PREG_SPLIT_NO_EMPTY);
        
        // Count single word frequencies (filter stop words and short words)
        $wordFreq = [];
        foreach ($words as $word) {
            if (mb_strlen($word) < 2) continue;
            if (in_array($word, $stopWords)) continue;
            if (is_numeric($word)) continue;
            $wordFreq[$word] = ($wordFreq[$word] ?? 0) + 1;
        }
        arsort($wordFreq);
        $topWords = array_slice($wordFreq, 0, 15, true);
        
        // Count 2-gram phrase frequencies
        $phraseFreq = [];
        for ($i = 0; $i < count($words) - 1; $i++) {
            $w1 = $words[$i];
            $w2 = $words[$i + 1];
            if (mb_strlen($w1) < 2 || mb_strlen($w2) < 2) continue;
            if (in_array($w1, $stopWords) && in_array($w2, $stopWords)) continue;
            $phrase = $w1 . ' ' . $w2;
            $phraseFreq[$phrase] = ($phraseFreq[$phrase] ?? 0) + 1;
        }
        arsort($phraseFreq);
        // Only keep phrases appearing 2+ times
        $topPhrases = array_filter($phraseFreq, fn($count) => $count >= 2);
        $topPhrases = array_slice($topPhrases, 0, 10, true);
        
        // Build results
        $topWordsList = [];
        foreach ($topWords as $word => $count) {
            $topWordsList[] = ['word' => $word, 'count' => $count];
        }
        $topPhrasesList = [];
        foreach ($topPhrases as $phrase => $count) {
            $topPhrasesList[] = ['phrase' => $phrase, 'count' => $count];
        }
        
        // Score: having clear topic focus = good
        $topCount = !empty($topWords) ? reset($topWords) : 0;
        $totalWords = count($words);
        $focusRatio = $totalWords > 0 ? $topCount / $totalWords : 0;
        
        $score = 7; // Default decent
        $messages = [];
        
        if ($focusRatio > 0.03) {
            $messages[] = 'Nội dung có chủ đề rõ ràng. Google dễ hiểu bài viết.' ;
            $score = 9;
        } elseif ($focusRatio > 0.015) {
            $messages[] = 'Chủ đề khá rõ. Có thể tăng mật độ từ khóa chính.';
            $score = 7;
        } else {
            $messages[] = 'Nội dung lan man, thiếu tập trung vào chủ đề. Nên lặp lại từ khóa chính nhiều hơn.';
            $score = 4;
        }
        
        $topWord = !empty($topWordsList) ? $topWordsList[0]['word'] : '—';
        $messages[] = "Từ nổi bật nhất: \"{$topWord}\" ({$topCount} lần).";
        
        return [
            'group' => 'Content Insights',
            'title' => 'Phân tích nội dung (Content Insights)',
            'score' => $score,
            'maxScore' => 10,
            'status' => $score >= 8 ? 'good' : ($score >= 5 ? 'warning' : 'bad'),
            'message' => implode(' ', $messages),
            'top_words' => $topWordsList,
            'top_phrases' => $topPhrasesList,
        ];
    }

    /**
     * Transition Words Check — like Yoast Premium readability check
     * Checks for Vietnamese transition words that improve content flow
     * Google: "Well-structured content with good transitions ranks better"
     */
    public function checkTransitionWords(string $content): array
    {
        $plain = strip_tags($content);
        $plain = preg_replace('/\s+/', ' ', trim($plain));
        $plainLower = mb_strtolower($plain);
        
        // Comprehensive Vietnamese transition words list
        $transitionWords = [
            // Liệt kê / Addition
            'ngoài ra', 'hơn nữa', 'bên cạnh đó', 'thêm vào đó', 'đặc biệt',
            'cụ thể', 'ví dụ', 'chẳng hạn', 'đồng thời', 'không chỉ',
            // Tương phản / Contrast
            'tuy nhiên', 'ngược lại', 'mặc dù', 'dù vậy', 'nhưng mà',
            'trái lại', 'mặt khác', 'trong khi đó', 'thay vì',
            // Nguyên nhân-kết quả / Cause-Effect
            'do đó', 'vì vậy', 'vì thế', 'kết quả là', 'bởi vì',
            'cho nên', 'nhờ đó', 'dẫn đến', 'từ đó',
            // Thời gian / Time
            'trước tiên', 'tiếp theo', 'sau đó', 'cuối cùng', 'ban đầu',
            'hiện nay', 'đầu tiên', 'bước tiếp', 'ngay sau',
            // Kết luận / Conclusion
            'tóm lại', 'nói chung', 'nhìn chung', 'tổng kết', 'kết luận',
            'như vậy', 'có thể thấy', 'rõ ràng', 'chắc chắn',
            // Nhấn mạnh / Emphasis
            'đặc biệt là', 'quan trọng', 'lưu ý', 'chú ý', 'cần nhớ',
            'thực tế', 'thật ra', 'trên thực tế',
        ];
        
        // Count sentences
        $sentences = preg_split('/[.!?。]+/u', $plain, -1, PREG_SPLIT_NO_EMPTY);
        $sentenceCount = max(count($sentences), 1);
        
        // Count transitions found
        $foundTransitions = [];
        $transitionsUsed = 0;
        
        foreach ($transitionWords as $tw) {
            $count = mb_substr_count($plainLower, $tw);
            if ($count > 0) {
                $foundTransitions[$tw] = $count;
                $transitionsUsed += $count;
            }
        }
        
        // Calculate percentage of sentences with transition words
        $percentage = round(($transitionsUsed / $sentenceCount) * 100);
        
        $score = 10;
        $messages = [];
        
        if ($percentage >= 30) {
            $messages[] = "Tuyệt vời! {$percentage}% câu có từ nối. Nội dung mạch lạc. 👍";
        } elseif ($percentage >= 20) {
            $messages[] = "{$percentage}% câu có từ nối. Tốt nhưng có thể cải thiện.";
            $score = 8;
        } elseif ($percentage >= 10) {
            $messages[] = "Chỉ {$percentage}% câu có từ nối. Nên thêm (tuy nhiên, ngoài ra, do đó...).";
            $score = 5;
        } else {
            $messages[] = "⚠️ Chỉ {$percentage}% câu có từ nối! Bài viết thiếu liên kết giữa các ý.";
            $score = 3;
        }
        
        // Show top transitions used
        if (!empty($foundTransitions)) {
            arsort($foundTransitions);
            $topUsed = array_slice($foundTransitions, 0, 5, true);
            $topList = [];
            foreach ($topUsed as $tw => $count) {
                $topList[] = "\"{$tw}\" ({$count}x)";
            }
            $messages[] = "Từ nối sử dụng: " . implode(', ', $topList);
        }
        
        return [
            'group' => 'Readability',
            'title' => 'Từ nối (Transition Words)',
            'score' => $score,
            'maxScore' => 10,
            'status' => $score >= 8 ? 'good' : ($score >= 5 ? 'warning' : 'bad'),
            'message' => implode(' ', $messages),
            'details' => [
                'percentage' => $percentage,
                'transitions_count' => $transitionsUsed,
                'sentence_count' => $sentenceCount,
                'transitions_found' => $foundTransitions,
            ],
        ];
    }

    /**
     * Focus Keyword Export — export all keywords + URLs as CSV
     * Like Yoast Premium: useful for managing large sites
     */
    public function exportFocusKeywords(): array
    {
        try {
            $posts = DB::table('blog_posts')
                ->whereNotNull('focus_keyword')
                ->where('focus_keyword', '!=', '')
                ->select('id', 'title', 'slug', 'focus_keyword', 'meta_title', 'meta_description', 'status', 'is_cornerstone', 'updated_at')
                ->orderBy('updated_at', 'desc')
                ->get();
            
            $rows = [];
            foreach ($posts as $post) {
                $rows[] = [
                    'id' => $post->id,
                    'title' => $post->title,
                    'url' => url('/blog/' . $post->slug),
                    'focus_keyword' => $post->focus_keyword,
                    'meta_title' => $post->meta_title ?? '',
                    'meta_description' => $post->meta_description ?? '',
                    'status' => $post->status,
                    'is_cornerstone' => $post->is_cornerstone ?? false,
                    'updated_at' => $post->updated_at,
                ];
            }
            
            return $rows;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Generate CSV content from focus keywords export
     */
    public function exportFocusKeywordsCsv(): string
    {
        $rows = $this->exportFocusKeywords();
        
        $csv = "ID,Title,URL,Focus Keyword,Meta Title,Meta Description,Status,Cornerstone,Last Updated\n";
        
        foreach ($rows as $row) {
            $csv .= implode(',', [
                $row['id'],
                '"' . str_replace('"', '""', $row['title']) . '"',
                $row['url'],
                '"' . str_replace('"', '""', $row['focus_keyword']) . '"',
                '"' . str_replace('"', '""', $row['meta_title']) . '"',
                '"' . str_replace('"', '""', mb_substr($row['meta_description'], 0, 100)) . '"',
                $row['status'],
                $row['is_cornerstone'] ? 'Yes' : 'No',
                $row['updated_at'] ?? '',
            ]) . "\n";
        }
        
        return $csv;
    }

    /**
     * Keyword Synonym/Variation Detection — like Yoast Premium
     * Detects keyword variations, synonyms, and related forms
     * Vietnamese doesn't have verb conjugations, but variations exist
     */
    public function detectKeywordVariations(string $focusKeyword): array
    {
        $keyword = mb_strtolower(trim($focusKeyword));
        $variations = [$keyword];
        
        if (mb_strlen($keyword) < 2) return $variations;
        
        // 1. Vietnamese abbreviation patterns
        $abbreviations = [
            'thuê tài khoản' => ['thuê tk', 'cho thuê tài khoản', 'cho thuê tk'],
            'tài khoản' => ['tk', 'acc', 'account'],
            'phần mềm' => ['pm', 'soft', 'software', 'tool'],
            'điện thoại' => ['đt', 'phone', 'dien thoai'],
            'hướng dẫn' => ['hdsd', 'hd', 'cách', 'tutorial'],
            'giá rẻ' => ['giá tốt', 'rẻ nhất', 'giá ưu đãi', 'giá hời'],
            'uy tín' => ['đáng tin', 'tin cậy', 'chất lượng'],
            'tự động' => ['auto', '24/7', 'tức thì'],
            'mở khóa' => ['unlock', 'mo khoa', 'giải mã'],
            'cài đặt' => ['setup', 'cai dat', 'thiết lập'],
        ];
        foreach ($abbreviations as $full => $abbrevs) {
            if (mb_strpos($keyword, $full) !== false) {
                foreach ($abbrevs as $abbr) {
                    $variations[] = str_replace($full, $abbr, $keyword);
                }
            }
            foreach ($abbrevs as $abbr) {
                if (mb_strpos($keyword, $abbr) !== false) {
                    $variations[] = str_replace($abbr, $full, $keyword);
                }
            }
        }
        
        // 2. With/without diacritics pattern
        $noDiacritics = $this->removeDiacritics($keyword);
        if ($noDiacritics !== $keyword) {
            $variations[] = $noDiacritics;
        }
        
        // 3. Common Vietnamese suffixes/prefixes
        $suffixPatterns = [
            'là gì' => ['nghĩa là gì', 'có nghĩa gì'],
            'ở đâu' => ['chỗ nào', 'tại đâu'],
            'bao nhiêu' => ['giá bao nhiêu', 'bao nhiêu tiền'],
        ];
        foreach ($suffixPatterns as $suffix => $alts) {
            if (str_ends_with($keyword, $suffix)) {
                $base = trim(mb_substr($keyword, 0, mb_strlen($keyword) - mb_strlen($suffix)));
                foreach ($alts as $alt) {
                    $variations[] = $base . ' ' . $alt;
                }
            }
        }
        
        // 4. Product name variations (GSM tools)
        $productVariations = [
            'unlocktool' => ['unlock tool', 'unlock-tool'],
            'unlock tool' => ['unlocktool', 'unlock-tool'],
            'vietmap' => ['viet map', 'vietmap live', 'vietmap pro'],
            'vietmap live' => ['vietmap', 'vietmap live pro'],
            'tsm tool' => ['tsm', 'tsmtool'],
            'griffin' => ['griffin unlocker', 'griffin-unlocker'],
            'samsung tool' => ['samsung tool pro', 'z3x samsung'],
        ];
        foreach ($productVariations as $product => $vars) {
            if (mb_strpos($keyword, $product) !== false) {
                foreach ($vars as $v) {
                    $variations[] = str_replace($product, $v, $keyword);
                }
            }
        }
        
        // 5. Word order swaps for 2-3 word keywords
        $words = explode(' ', $keyword);
        if (count($words) === 2) {
            $variations[] = $words[1] . ' ' . $words[0];
        } elseif (count($words) === 3) {
            $variations[] = $words[2] . ' ' . $words[0] . ' ' . $words[1];
            $variations[] = $words[1] . ' ' . $words[2] . ' ' . $words[0];
        }
        
        // Remove duplicates and empty
        $variations = array_values(array_unique(array_filter($variations)));
        
        return $variations;
    }

    /**
     * Remove Vietnamese diacritics
     */
    private function removeDiacritics(string $str): string
    {
        $from = ['à','á','ả','ã','ạ','ă','ằ','ắ','ẳ','ẵ','ặ','â','ầ','ấ','ẩ','ẫ','ậ',
                 'è','é','ẻ','ẽ','ẹ','ê','ề','ế','ể','ễ','ệ',
                 'ì','í','ỉ','ĩ','ị',
                 'ò','ó','ỏ','õ','ọ','ô','ồ','ố','ổ','ỗ','ộ','ơ','ờ','ớ','ở','ỡ','ợ',
                 'ù','ú','ủ','ũ','ụ','ư','ừ','ứ','ử','ữ','ự',
                 'ỳ','ý','ỷ','ỹ','ỵ','đ',
                 'À','Á','Ả','Ã','Ạ','Ă','Ằ','Ắ','Ẳ','Ẵ','Ặ','Â','Ầ','Ấ','Ẩ','Ẫ','Ậ',
                 'È','É','Ẻ','Ẽ','Ẹ','Ê','Ề','Ế','Ể','Ễ','Ệ',
                 'Ì','Í','Ỉ','Ĩ','Ị',
                 'Ò','Ó','Ỏ','Õ','Ọ','Ô','Ồ','Ố','Ổ','Ỗ','Ộ','Ơ','Ờ','Ớ','Ở','Ỡ','Ợ',
                 'Ù','Ú','Ủ','Ũ','Ụ','Ư','Ừ','Ứ','Ử','Ữ','Ự',
                 'Ỳ','Ý','Ỷ','Ỹ','Ỵ','Đ'];
        $to   = ['a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a',
                 'e','e','e','e','e','e','e','e','e','e','e',
                 'i','i','i','i','i',
                 'o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o',
                 'u','u','u','u','u','u','u','u','u','u','u',
                 'y','y','y','y','y','d',
                 'A','A','A','A','A','A','A','A','A','A','A','A','A','A','A','A','A',
                 'E','E','E','E','E','E','E','E','E','E','E',
                 'I','I','I','I','I',
                 'O','O','O','O','O','O','O','O','O','O','O','O','O','O','O','O','O',
                 'U','U','U','U','U','U','U','U','U','U','U',
                 'Y','Y','Y','Y','Y','D'];
        return str_replace($from, $to, $str);
    }

    /**
     * Check keyword with synonym/variation support — enhanced version
     * Returns how many variations are found in content
     */
    public function checkKeywordVariationsInContent(string $focusKeyword, string $content): array
    {
        $plain = mb_strtolower(strip_tags($content));
        $variations = $this->detectKeywordVariations($focusKeyword);
        
        $found = [];
        $totalOccurrences = 0;
        
        foreach ($variations as $variant) {
            $count = mb_substr_count($plain, $variant);
            if ($count > 0) {
                $found[$variant] = $count;
                $totalOccurrences += $count;
            }
        }
        
        $score = 10;
        $messages = [];
        
        if ($totalOccurrences === 0) {
            $messages[] = 'Không tìm thấy từ khóa hoặc biến thể nào trong nội dung.';
            $score = 0;
        } elseif (count($found) === 1) {
            $messages[] = "Chỉ dùng 1 dạng từ khóa ({$totalOccurrences} lần). Nên thêm biến thể tự nhiên.";
            $score = 6;
        } elseif (count($found) >= 3) {
            $messages[] = "Tuyệt vời! Dùng " . count($found) . " biến thể tự nhiên ({$totalOccurrences} lần tổng). 👍";
            $score = 10;
        } else {
            $messages[] = "Dùng " . count($found) . " biến thể ({$totalOccurrences} lần). Tốt!";
            $score = 8;
        }
        
        // Show found variations
        $varList = [];
        foreach ($found as $v => $c) {
            $varList[] = "\"{$v}\" ({$c}x)";
        }
        if (!empty($varList)) {
            $messages[] = "Biến thể: " . implode(', ', array_slice($varList, 0, 5));
        }
        
        // Show suggested variations not yet used
        $unused = array_diff($variations, array_keys($found));
        if (!empty($unused) && count($found) < 3) {
            $suggestions = array_slice($unused, 0, 3);
            $messages[] = "Gợi ý thêm: " . implode(', ', array_map(fn($s) => "\"{$s}\"", $suggestions));
        }
        
        return [
            'group' => 'Keyword Variations',
            'title' => 'Biến thể từ khóa (Synonym Detection)',
            'score' => $score,
            'maxScore' => 10,
            'status' => $score >= 8 ? 'good' : ($score >= 5 ? 'warning' : 'bad'),
            'message' => implode(' ', $messages),
            'details' => [
                'variations_total' => count($variations),
                'variations_found' => count($found),
                'occurrences' => $totalOccurrences,
                'found' => $found,
            ],
        ];
    }

    /**
     * Stricter Cornerstone Content Analysis — like Yoast Premium
     * Cornerstone content should have higher standards:
     * - Longer content (900+ words instead of 300)
     * - More internal links (2+ instead of 1)
     * - Better heading structure (3+ subheadings)
     * - Must have images
     * - Must have meta description
     */
    public function cornerstoneAnalysis(array $data): array
    {
        $content = $data['content'] ?? '';
        $metaDescription = $data['meta_description'] ?? '';
        $plain = strip_tags($content);
        $wordCount = $this->countWords($plain);
        
        $score = 10;
        $maxScore = 10;
        $messages = [];
        
        // 1. Word count: cornerstone must be 900+ words
        if ($wordCount < 900) {
            $messages[] = "⚠️ Cornerstone cần tối thiểu 900 từ (hiện có {$wordCount}). Nội dung dài = Google đánh giá cao hơn.";
            $score -= 3;
        } elseif ($wordCount < 1500) {
            $messages[] = "Cornerstone có {$wordCount} từ. Tốt, nhưng 1500+ từ lý tưởng.";
            $score -= 1;
        } else {
            $messages[] = "✅ Cornerstone có {$wordCount} từ. Xuất sắc!";
        }
        
        // 2. Heading count: need 3+ subheadings
        preg_match_all('/<h[2-6][^>]*>/i', $content, $headings);
        $headingCount = count($headings[0]);
        if ($headingCount < 3) {
            $messages[] = "⚠️ Cornerstone cần tối thiểu 3 heading phụ (hiện có {$headingCount}).";
            $score -= 2;
        } else {
            $messages[] = "✅ Có {$headingCount} heading phụ.";
        }
        
        // 3. Internal links: need 2+ for cornerstone
        preg_match_all('/href\s*=\s*["\'][^"\']*thuetaikhoan\.net[^"\']*["\']/i', $content, $internalLinks);
        preg_match_all('/href\s*=\s*["\']\/[^"\']*["\']/i', $content, $relativeLinks);
        $linkCount = count($internalLinks[0]) + count($relativeLinks[0]);
        if ($linkCount < 2) {
            $messages[] = "⚠️ Cornerstone cần tối thiểu 2 internal link (hiện có {$linkCount}).";
            $score -= 2;
        } else {
            $messages[] = "✅ Có {$linkCount} internal link.";
        }
        
        // 4. Must have images
        preg_match_all('/<img[^>]+>/i', $content, $images);
        $imageCount = count($images[0]);
        if ($imageCount === 0) {
            $messages[] = "⚠️ Cornerstone phải có ít nhất 1 hình ảnh.";
            $score -= 2;
        } else {
            $messages[] = "✅ Có {$imageCount} hình ảnh.";
        }
        
        // 5. Must have meta description
        if (empty($metaDescription) || mb_strlen($metaDescription) < 50) {
            $messages[] = "⚠️ Cornerstone phải có meta description đầy đủ (>50 ký tự).";
            $score -= 1;
        }
        
        return [
            'group' => 'Cornerstone',
            'title' => '📌 Tiêu chuẩn Cornerstone (Nội dung trụ cột)',
            'score' => max($score, 0),
            'maxScore' => $maxScore,
            'status' => $score >= 8 ? 'good' : ($score >= 5 ? 'warning' : 'bad'),
            'message' => implode(' ', $messages),
            'details' => [
                'word_count' => $wordCount,
                'heading_count' => $headingCount,
                'internal_link_count' => $linkCount,
                'image_count' => $imageCount,
                'has_meta_desc' => !empty($metaDescription),
            ],
        ];
    }

    /**
     * Word Complexity Check — like Yoast Premium (July 2022)
     * Highlights complex/difficult words and suggests simpler alternatives
     * Vietnamese-adapted: checks syllable count and technical terms
     */
    public function wordComplexityCheck(string $content): array
    {
        $plain = strip_tags($content);
        $plain = preg_replace('/\s+/', ' ', trim($plain));
        $plainLower = mb_strtolower($plain);
        
        // Vietnamese complex/technical words → simpler alternatives
        $complexWords = [
            'khuyến nghị' => 'nên',
            'phương pháp' => 'cách',
            'thực hiện' => 'làm',
            'sự kiện' => 'việc',
            'trường hợp' => 'khi',
            'phối hợp' => 'kết hợp',
            'tiến hành' => 'bắt đầu',
            'triển khai' => 'thực hiện',
            'tham khảo' => 'xem',
            'sử dụng' => 'dùng',
            'hiện tượng' => 'tình trạng',
            'giải pháp' => 'cách giải quyết',
            'yêu cầu' => 'cần',
            'hỗ trợ' => 'giúp',
            'liên hệ' => 'gọi',
            'đảm bảo' => 'chắc chắn',
            'xác nhận' => 'kiểm tra',
            'cập nhật' => 'update',
            'tương thích' => 'hợp',
            'tối ưu hóa' => 'cải thiện',
            'khắc phục' => 'sửa',
            'phát sinh' => 'xảy ra',
            'thao tác' => 'bước',
            'chức năng' => 'tính năng',
            'giao diện' => 'màn hình',
            'kích hoạt' => 'bật',
            'vô hiệu hóa' => 'tắt',
            'truy cập' => 'vào',
            'tải xuống' => 'tải',
            'thiết lập' => 'cài đặt',
        ];
        
        $foundComplex = [];
        $totalComplexCount = 0;
        
        foreach ($complexWords as $complex => $simple) {
            $count = mb_substr_count($plainLower, $complex);
            if ($count > 0) {
                $foundComplex[] = [
                    'word' => $complex,
                    'count' => $count,
                    'suggestion' => $simple,
                ];
                $totalComplexCount += $count;
            }
        }
        
        // Also check for very long words (>7 syllables approximation: >14 chars for Vietnamese)
        $words = preg_split('/[\s,;:.!?()\[\]{}]+/u', $plainLower, -1, PREG_SPLIT_NO_EMPTY);
        $totalWords = count($words);
        $longWords = 0;
        foreach ($words as $word) {
            if (mb_strlen($word) > 14) {
                $longWords++;
            }
        }
        
        // Calculate complexity ratio
        $complexRatio = $totalWords > 0 ? ($totalComplexCount + $longWords) / $totalWords * 100 : 0;
        
        $score = 10;
        $messages = [];
        
        if ($complexRatio > 15) {
            $messages[] = "⚠️ Nội dung quá phức tạp ({$totalComplexCount} từ phức tạp, ~" . round($complexRatio) . "%). Đơn giản hóa để dễ đọc hơn.";
            $score = 3;
        } elseif ($complexRatio > 8) {
            $messages[] = "Có {$totalComplexCount} từ phức tạp (~" . round($complexRatio) . "%). Có thể thay bằng từ đơn giản hơn.";
            $score = 6;
        } elseif ($complexRatio > 3) {
            $messages[] = "Từ phức tạp ở mức chấp nhận ({$totalComplexCount} từ). Tốt!";
            $score = 8;
        } else {
            $messages[] = "✅ Nội dung dễ hiểu! Từ phức tạp rất ít. 👍";
            $score = 10;
        }
        
        // Show top replaceable words
        if (!empty($foundComplex)) {
            usort($foundComplex, fn($a, $b) => $b['count'] - $a['count']);
            $topSuggestions = array_slice($foundComplex, 0, 5);
            $sugList = [];
            foreach ($topSuggestions as $item) {
                $sugList[] = "\"{$item['word']}\" → \"{$item['suggestion']}\" ({$item['count']}x)";
            }
            $messages[] = "Gợi ý thay: " . implode(', ', $sugList);
        }
        
        return [
            'group' => 'Readability',
            'title' => 'Độ phức tạp từ (Word Complexity)',
            'score' => $score,
            'maxScore' => 10,
            'status' => $score >= 8 ? 'good' : ($score >= 5 ? 'warning' : 'bad'),
            'message' => implode(' ', $messages),
            'details' => [
                'complex_words_count' => $totalComplexCount,
                'long_words_count' => $longWords,
                'total_words' => $totalWords,
                'complexity_ratio' => round($complexRatio, 1),
                'suggestions' => array_slice($foundComplex, 0, 10),
            ],
        ];
    }

    /**
     * Estimated Reading Time — like Yoast Premium (Jan 2021)
     * Calculates and returns reading time analysis
     * Vietnamese reading speed: ~200 words/min (slower than English due to tonal complexity)
     */
    public function estimatedReadingTime(string $content): array
    {
        $plain = strip_tags($content);
        $wordCount = $this->countWords($plain);
        
        // Vietnamese reading speed: ~200 words per minute
        $readingMinutes = max(1, ceil($wordCount / 200));
        
        $score = 10;
        $messages = [];
        
        if ($readingMinutes <= 2) {
            $messages[] = "⚠️ Bài viết rất ngắn (~{$readingMinutes} phút đọc, {$wordCount} từ). Nên viết dài hơn để Google đánh giá cao.";
            $score = 4;
        } elseif ($readingMinutes <= 5) {
            $messages[] = "Bài viết ~{$readingMinutes} phút đọc ({$wordCount} từ). Độ dài vừa phải.";
            $score = 7;
        } elseif ($readingMinutes <= 10) {
            $messages[] = "✅ Bài viết ~{$readingMinutes} phút đọc ({$wordCount} từ). Độ dài lý tưởng cho SEO! 👍";
            $score = 10;
        } else {
            $messages[] = "Bài viết dài ~{$readingMinutes} phút đọc ({$wordCount} từ). Rất chi tiết — hãy đảm bảo có mục lục.";
            $score = 8;
        }
        
        return [
            'group' => 'Content',
            'title' => 'Thời gian đọc (Estimated Reading Time)',
            'score' => $score,
            'maxScore' => 10,
            'status' => $score >= 8 ? 'good' : ($score >= 5 ? 'warning' : 'bad'),
            'message' => implode(' ', $messages),
            'details' => [
                'reading_minutes' => $readingMinutes,
                'word_count' => $wordCount,
                'words_per_minute' => 200,
            ],
        ];
    }

    // ============================================================
    // VIP FEATURES — BEYOND YOAST PREMIUM
    // ============================================================

    /**
     * VIP #1: Content Decay Detection
     * Phát hiện bài viết đang mất traffic/lượt xem theo thời gian
     * Không có plugin SEO nào (kể cả Yoast) có tính năng này built-in
     */
    public function contentDecayDetection(): array
    {
        try {
            $posts = DB::table('blog_posts')
                ->where('status', 'published')
                ->select('id', 'title', 'slug', 'views', 'created_at', 'updated_at', 'is_cornerstone')
                ->orderBy('views', 'desc')
                ->get();
        } catch (\Exception $e) {
            return ['decaying' => [], 'healthy' => [], 'rising' => []];
        }

        $decaying = [];
        $healthy = [];
        $rising = [];
        $now = now();

        foreach ($posts as $post) {
            $createdAt = \Carbon\Carbon::parse($post->created_at);
            $updatedAt = \Carbon\Carbon::parse($post->updated_at);
            $ageInDays = max($createdAt->diffInDays($now), 1);
            $daysSinceUpdate = $updatedAt->diffInDays($now);
            
            // Ước tính tốc độ xem trung bình/ngày
            $viewsPerDay = $post->views / $ageInDays;
            
            // Bài > 60 ngày tuổi + ít views + không cập nhật lâu = đang suy giảm
            $decayScore = 0;
            $reasons = [];
            
            // Bài cũ nhưng ít views
            if ($ageInDays > 60 && $viewsPerDay < 1) {
                $decayScore += 3;
                $reasons[] = 'Lượt xem rất thấp (' . round($viewsPerDay, 1) . '/ngày)';
            } elseif ($ageInDays > 30 && $viewsPerDay < 2) {
                $decayScore += 1;
                $reasons[] = 'Lượt xem thấp (' . round($viewsPerDay, 1) . '/ngày)';
            }
            
            // Không cập nhật lâu
            if ($daysSinceUpdate > 180) {
                $decayScore += 3;
                $reasons[] = 'Chưa cập nhật ' . $daysSinceUpdate . ' ngày';
            } elseif ($daysSinceUpdate > 90) {
                $decayScore += 1;
                $reasons[] = 'Chưa cập nhật ' . $daysSinceUpdate . ' ngày';
            }
            
            // Cornerstone mà decay = nghiêm trọng hơn
            if ($post->is_cornerstone && $decayScore > 0) {
                $decayScore += 2;
                $reasons[] = '⚠️ Bài trụ cột đang suy giảm!';
            }
            
            $item = [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'views' => $post->views,
                'views_per_day' => round($viewsPerDay, 1),
                'age_days' => $ageInDays,
                'days_since_update' => $daysSinceUpdate,
                'decay_score' => $decayScore,
                'is_cornerstone' => (bool)$post->is_cornerstone,
                'reasons' => $reasons,
                'action' => $decayScore >= 4 ? 'Cần cập nhật GẤP' : ($decayScore >= 2 ? 'Nên cập nhật sớm' : 'Ổn định'),
            ];
            
            if ($decayScore >= 4) {
                $decaying[] = $item;
            } elseif ($decayScore >= 2) {
                $healthy[] = $item; // borderline
            } else {
                $rising[] = $item;
            }
        }
        
        // Sắp xếp decaying theo mức nghiêm trọng
        usort($decaying, fn($a, $b) => $b['decay_score'] - $a['decay_score']);
        
        return [
            'decaying' => $decaying,
            'healthy' => $healthy,
            'rising' => array_slice($rising, 0, 30),
            'summary' => [
                'total_posts' => count($posts),
                'decaying_count' => count($decaying),
                'healthy_count' => count($healthy),
                'rising_count' => count($rising),
                'decay_percentage' => count($posts) > 0 ? round(count($decaying) / count($posts) * 100) : 0,
            ],
        ];
    }

    /**
     * VIP #2: Broken Link Checker
     * Quét tất cả bài viết, tìm link hỏng (internal + external)
     * Yoast KHÔNG có tính năng này — cần plugin riêng ($49/năm)
     */
    public function brokenLinkChecker(int $limit = 0): array
    {
        try {
            $query = DB::table('blog_posts')
                ->where('status', 'published')
                ->select('id', 'title', 'slug', 'content');
            
            if ($limit > 0) {
                $query->limit($limit);
            }
            
            $posts = $query->get();
        } catch (\Exception $e) {
            return ['broken' => [], 'checked' => 0];
        }

        $allLinks = [];
        
        foreach ($posts as $post) {
            // Tìm tất cả link trong nội dung
            preg_match_all('/href\s*=\s*["\']([^"\'#]+)["\']/i', $post->content, $matches);
            
            if (!empty($matches[1])) {
                foreach ($matches[1] as $url) {
                    // Bỏ qua mailto, tel, javascript
                    if (preg_match('/^(mailto:|tel:|javascript:)/i', $url)) continue;
                    
                    // Chuyển relative URL thành absolute
                    if (str_starts_with($url, '/')) {
                        $url = 'https://thuetaikhoan.net' . $url;
                    }
                    
                    if (!isset($allLinks[$url])) {
                        $allLinks[$url] = [];
                    }
                    $allLinks[$url][] = [
                        'post_id' => $post->id,
                        'post_title' => $post->title,
                        'post_slug' => $post->slug,
                    ];
                }
            }
        }

        // === THÊM: Quét Blade templates ===
        $bladeDir = resource_path('views');
        $bladeFiles = glob($bladeDir . '/{,*/,*/*/,*/*/*/}*.blade.php', GLOB_BRACE);
        foreach ($bladeFiles as $file) {
            $bladeContent = file_get_contents($file);
            preg_match_all('/href\s*=\s*["\'](\/[^"\'#]+|https?:\/\/[^"\'#]+)["\']/i', $bladeContent, $matches);
            
            if (!empty($matches[1])) {
                $relPath = str_replace($bladeDir . '/', '', $file);
                foreach ($matches[1] as $url) {
                    if (preg_match('/^(mailto:|tel:|javascript:|{{|#)/i', $url)) continue;
                    if (str_contains($url, '<?php')) continue; // Skip dynamic PHP URLs
                    
                    if (str_starts_with($url, '/')) {
                        $url = 'https://thuetaikhoan.net' . $url;
                    }
                    
                    if (!isset($allLinks[$url])) {
                        $allLinks[$url] = [];
                    }
                    $allLinks[$url][] = [
                        'post_id' => 0,
                        'post_title' => '📄 Template: ' . $relPath,
                        'post_slug' => $relPath,
                    ];
                }
            }
        }

        $broken = [];
        $checked = 0;
        $externalUrls = [];
        $internalNonBlogUrls = []; // NEW: non-blog internal links
        
        // Step 1: Categorize links
        foreach ($allLinks as $url => $postsList) {
            $checked++;
            
            // Check blog internal links via DB (instant)
            if (str_contains($url, 'thuetaikhoan.net/blog/')) {
                $slug = basename(parse_url($url, PHP_URL_PATH));
                $slug = preg_replace('/\.(php|html|htm)$/i', '', $slug);
                $slug = strtok($slug, '?');
                
                $exists = DB::table('blog_posts')->where('slug', $slug)->exists();
                if (!$exists) {
                    $exists = DB::table('blog_posts')->where('slug', 'LIKE', $slug . '%')->exists();
                }
                
                if (!$exists && !empty($slug) && $slug !== 'blog') {
                    $broken[] = [
                        'url' => $url,
                        'status' => 404,
                        'type' => 'internal',
                        'found_in' => array_slice($postsList, 0, 3),
                    ];
                }
                continue;
            }
            
            // Collect non-blog internal links for HTTP check
            if (str_contains($url, 'thuetaikhoan.net')) {
                $internalNonBlogUrls[$url] = $postsList;
                continue;
            }
            
            // Collect external URLs for parallel check
            $externalUrls[$url] = $postsList;
        }
        
        // Step 2: Check non-blog internal links via HTTP HEAD (parallel)
        if (!empty($internalNonBlogUrls)) {
            $mh = curl_multi_init();
            $handles = [];
            
            foreach ($internalNonBlogUrls as $url => $postsList) {
                // Skip asset URLs (images, css, js)
                $path = parse_url($url, PHP_URL_PATH) ?? '';
                if (preg_match('/\.(css|js|png|jpg|jpeg|gif|webp|svg|ico|woff|woff2|ttf|eot|pdf|zip)$/i', $path)) {
                    continue;
                }
                
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_NOBODY => true,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 5,
                    CURLOPT_CONNECTTIMEOUT => 3,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_MAXREDIRS => 5,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; ThueTaiKhoan SEO Bot)',
                ]);
                curl_multi_add_handle($mh, $ch);
                $handles[$url] = $ch;
            }
            
            $running = null;
            do {
                curl_multi_exec($mh, $running);
                curl_multi_select($mh, 1);
            } while ($running > 0);
            
            foreach ($handles as $url => $ch) {
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($httpCode === 404 || $httpCode === 410) {
                    $broken[] = [
                        'url' => $url,
                        'status' => $httpCode,
                        'type' => 'internal',
                        'found_in' => array_slice($internalNonBlogUrls[$url], 0, 3),
                    ];
                }
                curl_multi_remove_handle($mh, $ch);
                curl_close($ch);
            }
            curl_multi_close($mh);
        }
        
        // Step 3: Check external links in PARALLEL batches (curl_multi)
        $batchSize = 20; // 20 links đồng thời
        $externalBatches = array_chunk($externalUrls, $batchSize, true);
        $externalChecked = 0;
        
        foreach ($externalBatches as $batch) {
            $mh = curl_multi_init();
            $handles = [];
            
            foreach ($batch as $url => $postsList) {
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_NOBODY => true,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 5,
                    CURLOPT_CONNECTTIMEOUT => 3,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_MAXREDIRS => 3,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; ThueTaiKhoan SEO Bot)',
                ]);
                curl_multi_add_handle($mh, $ch);
                $handles[$url] = $ch;
            }
            
            // Execute all requests in parallel
            $running = null;
            do {
                curl_multi_exec($mh, $running);
                curl_multi_select($mh, 1);
            } while ($running > 0);
            
            // Collect results
            foreach ($handles as $url => $ch) {
                $externalChecked++;
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                
                // Chỉ báo 404 và timeout là link hỏng thật
                // 403/405/521 = site chặn bot, người dùng vào bình thường
                $isTrulyBroken = ($httpCode === 404 || $httpCode === 410 || $httpCode === 0);
                
                if ($isTrulyBroken) {
                    $broken[] = [
                        'url' => $url,
                        'status' => $httpCode ?: 'timeout',
                        'type' => 'external',
                        'error' => $error ?: null,
                        'found_in' => array_slice($externalUrls[$url], 0, 3),
                    ];
                }
                
                curl_multi_remove_handle($mh, $ch);
                curl_close($ch);
            }
            
            curl_multi_close($mh);
        }
        
        return [
            'broken' => $broken,
            'checked' => $checked,
            'total_links' => count($allLinks),
            'broken_count' => count($broken),
            'external_checked' => $externalChecked,
        ];
    }

    /**
     * VIP #3: Topical Authority Map
     * Phân tích phủ sóng chủ đề — cho biết lĩnh vực nào mạnh, lĩnh vực nào cần thêm bài
     * KHÔNG có plugin nào có tính năng này — chỉ các tool SEO đắt tiền ($99-499/tháng) mới có
     */
    public function topicalAuthorityMap(): array
    {
        try {
            $posts = DB::table('blog_posts')
                ->where('status', 'published')
                ->select('id', 'title', 'slug', 'content', 'category', 'views', 'focus_keyword', 'is_cornerstone', 'created_at')
                ->get();
        } catch (\Exception $e) {
            return ['topics' => [], 'gaps' => [], 'recommendations' => []];
        }

        $topics = [];
        
        foreach ($posts as $post) {
            $category = $post->category ?: 'Chưa phân loại';
            
            if (!isset($topics[$category])) {
                $topics[$category] = [
                    'name' => $category,
                    'post_count' => 0,
                    'total_views' => 0,
                    'total_words' => 0,
                    'cornerstone_count' => 0,
                    'keywords' => [],
                    'posts' => [],
                    'avg_views' => 0,
                    'avg_words' => 0,
                    'authority_score' => 0,
                    'authority_level' => '',
                ];
            }
            
            $wordCount = $this->countWords(strip_tags($post->content ?? ''));
            
            $topics[$category]['post_count']++;
            $topics[$category]['total_views'] += $post->views;
            $topics[$category]['total_words'] += $wordCount;
            if ($post->is_cornerstone) $topics[$category]['cornerstone_count']++;
            if ($post->focus_keyword) {
                $topics[$category]['keywords'][] = $post->focus_keyword;
            }
            $topics[$category]['posts'][] = [
                'id' => $post->id,
                'title' => $post->title,
                'views' => $post->views,
                'words' => $wordCount,
            ];
        }
        
        // Tính authority score cho mỗi chủ đề
        foreach ($topics as $name => &$topic) {
            $topic['avg_views'] = $topic['post_count'] > 0 ? round($topic['total_views'] / $topic['post_count']) : 0;
            $topic['avg_words'] = $topic['post_count'] > 0 ? round($topic['total_words'] / $topic['post_count']) : 0;
            
            // Authority Score (0-100):
            // - Số bài viết (max 30 điểm): 1 bài = 3đ, tối đa 10 bài = 30đ
            // - Có cornerstone (20 điểm)
            // - Tổng lượt xem (max 25 điểm)
            // - Độ dài trung bình (max 25 điểm)
            
            $postScore = min($topic['post_count'] * 3, 30);
            $cornerstoneScore = $topic['cornerstone_count'] > 0 ? 20 : 0;
            $viewScore = min(round($topic['total_views'] / 100), 25);
            $depthScore = $topic['avg_words'] >= 1000 ? 25 : ($topic['avg_words'] >= 500 ? 15 : ($topic['avg_words'] >= 300 ? 8 : 3));
            
            $topic['authority_score'] = min($postScore + $cornerstoneScore + $viewScore + $depthScore, 100);
            
            // Authority level
            if ($topic['authority_score'] >= 70) {
                $topic['authority_level'] = '🟢 Chuyên gia';
            } elseif ($topic['authority_score'] >= 40) {
                $topic['authority_level'] = '🟡 Đang phát triển';
            } else {
                $topic['authority_level'] = '🔴 Yếu — cần thêm bài';
            }
            
            // Sắp xếp posts theo views
            usort($topic['posts'], fn($a, $b) => $b['views'] - $a['views']);
            $topic['posts'] = array_slice($topic['posts'], 0, 5); // Top 5
            $topic['unique_keywords'] = count(array_unique($topic['keywords']));
            unset($topic['keywords']); // Giảm data
        }
        unset($topic);
        
        // Sắp xếp theo authority score
        uasort($topics, fn($a, $b) => $b['authority_score'] - $a['authority_score']);
        
        // Phát hiện gaps (chủ đề yếu)
        $gaps = [];
        foreach ($topics as $topic) {
            if ($topic['authority_score'] < 40) {
                $gaps[] = [
                    'topic' => $topic['name'],
                    'current_posts' => $topic['post_count'],
                    'needed_posts' => max(5 - $topic['post_count'], 2),
                    'has_cornerstone' => $topic['cornerstone_count'] > 0,
                    'suggestion' => $topic['cornerstone_count'] === 0
                        ? "Cần viết 1 bài trụ cột + " . max(4 - $topic['post_count'], 1) . " bài hỗ trợ"
                        : "Cần thêm " . max(5 - $topic['post_count'], 2) . " bài chi tiết hơn",
                ];
            }
        }
        
        // Gợi ý tổng thể
        $recommendations = [];
        $totalTopics = count($topics);
        $strongTopics = count(array_filter($topics, fn($t) => $t['authority_score'] >= 70));
        $weakTopics = count(array_filter($topics, fn($t) => $t['authority_score'] < 40));
        
        if ($weakTopics > $strongTopics) {
            $recommendations[] = "⚠️ Có {$weakTopics}/{$totalTopics} chủ đề yếu. Tập trung viết thêm bài cho các chủ đề này.";
        }
        if ($strongTopics >= 3) {
            $recommendations[] = "✅ Có {$strongTopics} chủ đề mạnh. Google sẽ coi trang là chuyên gia trong lĩnh vực này.";
        }
        
        $cornerstoneTotal = array_sum(array_column($topics, 'cornerstone_count'));
        $noCornerstoneTopics = array_filter($topics, fn($t) => $t['cornerstone_count'] === 0 && $t['post_count'] >= 3);
        if (!empty($noCornerstoneTopics)) {
            $names = implode(', ', array_column(array_slice($noCornerstoneTopics, 0, 3), 'name'));
            $recommendations[] = "💡 Chủ đề \"{$names}\" có nhiều bài nhưng chưa có bài trụ cột. Nên đánh dấu 1 bài cornerstone.";
        }
        
        return [
            'topics' => array_values($topics),
            'gaps' => $gaps,
            'recommendations' => $recommendations,
            'summary' => [
                'total_topics' => $totalTopics,
                'strong_topics' => $strongTopics,
                'weak_topics' => $weakTopics,
                'total_posts' => count($posts),
                'total_cornerstone' => $cornerstoneTotal,
            ],
        ];
    }

    /**
     * VIP AUTO-FIX: Tự động sửa lỗi SEO cho tất cả bài viết
     * Fix được: meta_title, heading, image alt, keyword density
     * Không fix được: nội dung bài quá ngắn, thiếu nội dung chất lượng
     */
    public function seoAutoFixAll(): array
    {
        $posts = DB::table('blog_posts')
            ->where('status', 'published')
            ->whereNotNull('focus_keyword')
            ->where('focus_keyword', '!=', '')
            ->select('id', 'title', 'slug', 'content', 'meta_title', 'meta_description', 'focus_keyword')
            ->get();

        $fixed = [];
        $cannotFix = [];
        $alreadyGood = [];

        foreach ($posts as $post) {
            $keyword = mb_strtolower(trim($post->focus_keyword));
            if (mb_strlen($keyword) < 2) continue;

            $postFixes = [];
            $postCannotFix = [];
            $content = $post->content;
            $metaTitle = $post->meta_title ?: $post->title;
            $metaDescription = $post->meta_description ?: '';
            $contentChanged = false;
            $metaTitleChanged = false;
            $metaDescChanged = false;

            // ---- FIX 1: Keyword không có trong Meta Title ----
            if (!mb_stripos($metaTitle, $keyword) !== false && mb_stripos($metaTitle, $keyword) === false) {
                // Thêm keyword vào đầu meta title
                $oldTitle = $metaTitle;
                $keywordCapitalized = mb_strtoupper(mb_substr($keyword, 0, 1)) . mb_substr($keyword, 1);
                $newTitle = $keywordCapitalized . ' — ' . $metaTitle;
                
                // Giới hạn 60 ký tự
                if (mb_strlen($newTitle) > 60) {
                    $newTitle = mb_substr($newTitle, 0, 57) . '...';
                }
                
                $metaTitle = $newTitle;
                $metaTitleChanged = true;
                $postFixes[] = [
                    'type' => 'meta_title',
                    'issue' => 'Keyword không có trong meta title',
                    'before' => $oldTitle,
                    'after' => $newTitle,
                ];
            }

            // ---- FIX 2: Keyword không có trong heading (H2/H3) ----
            $hasKeywordInHeading = (bool) preg_match('/<h[2-3][^>]*>.*?' . preg_quote($keyword, '/') . '.*?<\/h[2-3]>/isu', $content);
            if (!$hasKeywordInHeading) {
                // Tìm heading H2 đầu tiên và thêm keyword vào
                if (preg_match('/<h2([^>]*)>(.*?)<\/h2>/isu', $content, $match)) {
                    $oldHeading = $match[2];
                    // Chỉ thêm nếu heading không quá dài
                    if (mb_strlen($oldHeading) < 60) {
                        $keywordCapitalized = mb_strtoupper(mb_substr($keyword, 0, 1)) . mb_substr($keyword, 1);
                        $newHeading = $oldHeading . ' — ' . $keywordCapitalized;
                        $content = preg_replace(
                            '/<h2(' . preg_quote($match[1], '/') . ')>' . preg_quote($match[2], '/') . '<\/h2>/isu',
                            '<h2$1>' . $newHeading . '</h2>',
                            $content,
                            1
                        );
                        $contentChanged = true;
                        $postFixes[] = [
                            'type' => 'heading',
                            'issue' => 'Keyword không có trong heading',
                            'before' => $oldHeading,
                            'after' => $newHeading,
                        ];
                    }
                } elseif (preg_match('/<h3([^>]*)>(.*?)<\/h3>/isu', $content, $match)) {
                    $oldHeading = $match[2];
                    if (mb_strlen($oldHeading) < 60) {
                        $keywordCapitalized = mb_strtoupper(mb_substr($keyword, 0, 1)) . mb_substr($keyword, 1);
                        $newHeading = $oldHeading . ' — ' . $keywordCapitalized;
                        $content = preg_replace(
                            '/<h3(' . preg_quote($match[1], '/') . ')>' . preg_quote($match[2], '/') . '<\/h3>/isu',
                            '<h3$1>' . $newHeading . '</h3>',
                            $content,
                            1
                        );
                        $contentChanged = true;
                        $postFixes[] = [
                            'type' => 'heading',
                            'issue' => 'Keyword không có trong heading',
                            'before' => $oldHeading,
                            'after' => $newHeading,
                        ];
                    }
                } else {
                    $postCannotFix[] = [
                        'type' => 'heading',
                        'reason' => 'Bài không có heading H2/H3 để thêm keyword',
                    ];
                }
            }

            // ---- FIX 3: Keyword không có trong alt text ảnh ----
            $hasKeywordInAlt = (bool) preg_match('/alt\s*=\s*["\'][^"\']*' . preg_quote($keyword, '/') . '[^"\']*["\']/isu', $content);
            if (!$hasKeywordInAlt) {
                // Tìm ảnh đầu tiên có alt rỗng hoặc không có alt
                $imgFixed = false;
                
                // Case 1: <img ... alt="" ...> hoặc <img ... alt='' ...>
                if (preg_match('/<img([^>]*)\s+alt\s*=\s*["\'][\s]*["\']/iu', $content, $imgMatch)) {
                    $keywordCapitalized = mb_strtoupper(mb_substr($keyword, 0, 1)) . mb_substr($keyword, 1);
                    $content = preg_replace(
                        '/<img(' . preg_quote($imgMatch[1], '/') . ')\s+alt\s*=\s*["\'][\s]*["\']/iu',
                        '<img$1 alt="' . htmlspecialchars($keywordCapitalized) . '"',
                        $content,
                        1
                    );
                    $contentChanged = true;
                    $imgFixed = true;
                    $postFixes[] = [
                        'type' => 'image_alt',
                        'issue' => 'Keyword không có trong alt text ảnh',
                        'before' => 'alt=""',
                        'after' => 'alt="' . $keywordCapitalized . '"',
                    ];
                }
                
                // Case 2: <img ...> không có alt attribute
                if (!$imgFixed && preg_match('/<img\s+([^>]*?)(?<!\/)>/iu', $content, $imgMatch2)) {
                    if (stripos($imgMatch2[0], 'alt=') === false) {
                        $keywordCapitalized = mb_strtoupper(mb_substr($keyword, 0, 1)) . mb_substr($keyword, 1);
                        $content = preg_replace(
                            '/<img\s+(' . preg_quote($imgMatch2[1], '/') . ')(?<!\/)>/iu',
                            '<img $1 alt="' . htmlspecialchars($keywordCapitalized) . '">',
                            $content,
                            1
                        );
                        $contentChanged = true;
                        $imgFixed = true;
                        $postFixes[] = [
                            'type' => 'image_alt',
                            'issue' => 'Ảnh không có alt attribute',
                            'before' => '(no alt)',
                            'after' => 'alt="' . $keywordCapitalized . '"',
                        ];
                    }
                }
                
                // Case 3: Thêm keyword vào alt của ảnh đầu tiên
                if (!$imgFixed && preg_match('/<img([^>]*)\s+alt\s*=\s*["\']([^"\']+)["\']/iu', $content, $imgMatch3)) {
                    $oldAlt = $imgMatch3[2];
                    if (mb_stripos($oldAlt, $keyword) === false) {
                        $newAlt = $oldAlt . ' - ' . $keyword;
                        if (mb_strlen($newAlt) <= 125) {
                            $content = preg_replace(
                                '/(<img' . preg_quote($imgMatch3[1], '/') . '\s+alt\s*=\s*["\'])' . preg_quote($oldAlt, '/') . '(["\'])/iu',
                                '$1' . htmlspecialchars($newAlt) . '$2',
                                $content,
                                1
                            );
                            $contentChanged = true;
                            $imgFixed = true;
                            $postFixes[] = [
                                'type' => 'image_alt',
                                'issue' => 'Keyword không có trong alt text ảnh',
                                'before' => 'alt="' . $oldAlt . '"',
                                'after' => 'alt="' . $newAlt . '"',
                            ];
                        }
                    }
                }
                
                if (!$imgFixed) {
                    $totalImages = preg_match_all('/<img/i', $content);
                    if ($totalImages === 0) {
                        $postCannotFix[] = [
                            'type' => 'image_alt',
                            'reason' => 'Bài không có ảnh nào',
                        ];
                    }
                }
            }

            // ---- FIX 4: Mật độ từ khóa thấp ----
            $plainContent = mb_strtolower(strip_tags($content));
            $totalWords = $this->countWords($plainContent);
            $keywordCount = mb_substr_count($plainContent, $keyword);
            $density = $totalWords > 0 ? ($keywordCount / $totalWords) * 100 : 0;
            
            if ($density < 0.5 && $totalWords > 100) {
                // Thêm 1 câu ngữ cảnh chứa keyword trước H2 đầu tiên
                $keywordCapitalized = mb_strtoupper(mb_substr($keyword, 0, 1)) . mb_substr($keyword, 1);
                $contextSentence = '<p><strong>' . $keywordCapitalized . '</strong> là một trong những chủ đề được nhiều người quan tâm hiện nay. Trong bài viết này, chúng tôi sẽ chia sẻ thông tin chi tiết về ' . $keyword . ' để bạn có cái nhìn tổng quan nhất.</p>';
                
                // Chèn trước <h2> đầu tiên
                if (preg_match('/<h2/iu', $content)) {
                    $content = preg_replace('/<h2/iu', $contextSentence . "\n<h2", $content, 1);
                    $contentChanged = true;
                    $postFixes[] = [
                        'type' => 'keyword_density',
                        'issue' => 'Mật độ từ khóa quá thấp (' . round($density, 1) . '%)',
                        'before' => round($density, 1) . '% (' . $keywordCount . ' lần)',
                        'after' => 'Thêm 1 đoạn chứa keyword (ước tính ~' . round($density + 0.3, 1) . '%)',
                    ];
                } else {
                    $postCannotFix[] = [
                        'type' => 'keyword_density',
                        'reason' => 'Mật độ từ khóa thấp (' . round($density, 1) . '%) — bài không có H2 nên không thể chèn đoạn bổ sung',
                    ];
                }
            }

            // ---- Lưu thay đổi ----
            if (!empty($postFixes)) {
                $updateData = [];
                if ($contentChanged) $updateData['content'] = $content;
                if ($metaTitleChanged) $updateData['meta_title'] = $metaTitle;
                if ($metaDescChanged) $updateData['meta_description'] = $metaDescription;
                
                if (!empty($updateData)) {
                    DB::table('blog_posts')->where('id', $post->id)->update($updateData);
                }
                
                $fixed[] = [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'fixes' => $postFixes,
                    'cannot_fix' => $postCannotFix,
                ];
            } elseif (!empty($postCannotFix)) {
                $cannotFix[] = [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'issues' => $postCannotFix,
                ];
            } else {
                $alreadyGood[] = [
                    'id' => $post->id,
                    'title' => $post->title,
                ];
            }
        }

        return [
            'fixed' => $fixed,
            'cannot_fix' => $cannotFix,
            'already_good' => $alreadyGood,
            'summary' => [
                'total_posts' => count($posts),
                'fixed_count' => count($fixed),
                'cannot_fix_count' => count($cannotFix),
                'already_good_count' => count($alreadyGood),
                'total_fixes_applied' => array_sum(array_map(fn($f) => count($f['fixes']), $fixed)),
            ],
        ];
    }

    /**
     * VIP AUTO-FIX V2: Sửa toàn diện các cảnh báo SEO — viết tự nhiên, chuẩn tiếng Việt
     * 
     * Fixes:
     * 1. Revert V1 ugly heading fixes (remove " — keyword" patterns)
     * 2. keyword_in_intro: Thêm keyword vào đoạn mở đầu
     * 3. keyword_in_meta: Thêm keyword vào meta description
     * 4. keyword_density: Tăng mật độ từ khóa (target 1-3%)
     * 5. keyword_in_headings: Viết lại heading chứa keyword tự nhiên
     * 6. meta_title_set: Tạo meta title riêng nếu chưa có
     * 7. meta_desc_length: Tối ưu độ dài meta description (120-160)
     * 8. keyword_in_img_alt: Sửa alt text hình ảnh
     */
    public function seoAutoFixAllV2(bool $dryRun = false): array
    {
        set_time_limit(300);
        
        $posts = DB::table('blog_posts')
            ->where('status', 'published')
            ->whereNotNull('focus_keyword')
            ->where('focus_keyword', '!=', '')
            ->select('id', 'title', 'slug', 'content', 'meta_title', 'meta_description', 'focus_keyword')
            ->get();

        $fixed = [];
        $cannotFix = [];
        $alreadyGood = [];

        foreach ($posts as $post) {
            $keyword = mb_strtolower(trim($post->focus_keyword));
            if (mb_strlen($keyword) < 2) continue;

            $postFixes = [];
            $postCannotFix = [];
            $content = $post->content;
            $metaTitle = $post->meta_title ?: '';
            $metaDescription = $post->meta_description ?: '';
            $title = $post->title;
            $contentChanged = false;
            $metaTitleChanged = false;
            $metaDescChanged = false;

            // ================================================================
            // STEP 0: Revert ugly V1 heading fixes (remove " — keyword" patterns)
            // ================================================================
            $v1Pattern = '/ — ' . preg_quote(mb_strtoupper(mb_substr($keyword, 0, 1)) . mb_substr($keyword, 1), '/') . '/iu';
            $v1PatternLower = '/ — ' . preg_quote($keyword, '/') . '/iu';
            if (preg_match($v1Pattern, $content) || preg_match($v1PatternLower, $content)) {
                $oldContent = $content;
                $content = preg_replace($v1Pattern, '', $content);
                $content = preg_replace($v1PatternLower, '', $content);
                if ($content !== $oldContent) {
                    $contentChanged = true;
                    $postFixes[] = [
                        'type' => 'revert_v1',
                        'issue' => 'Hoàn tác heading V1 không tự nhiên',
                        'before' => 'Heading có " — keyword" pattern',
                        'after' => 'Đã xóa pattern không tự nhiên',
                    ];
                }
            }
            
            // Revert V1 meta title fixes (remove "Keyword — " prefix)
            $v1MetaPrefix = mb_strtoupper(mb_substr($keyword, 0, 1)) . mb_substr($keyword, 1) . ' — ';
            if (mb_strpos($metaTitle, $v1MetaPrefix) === 0) {
                $oldMeta = $metaTitle;
                $metaTitle = mb_substr($metaTitle, mb_strlen($v1MetaPrefix));
                // Remove trailing "..." if was truncated
                if (mb_substr($metaTitle, -3) === '...') {
                    $metaTitle = $title; // Just reset to original title
                }
                $metaTitleChanged = true;
                $postFixes[] = [
                    'type' => 'revert_v1_meta',
                    'issue' => 'Hoàn tác meta title V1 không tự nhiên',
                    'before' => $oldMeta,
                    'after' => $metaTitle,
                ];
            }
            
            // Revert V1 image alt fixes (remove " - keyword" suffix)
            $v1AltPattern = '/ - ' . preg_quote($keyword, '/') . '/iu';
            if (preg_match($v1AltPattern, $content)) {
                $oldContent = $content;
                $content = preg_replace($v1AltPattern, '', $content);
                if ($content !== $oldContent) {
                    $contentChanged = true;
                    $postFixes[] = [
                        'type' => 'revert_v1_alt',
                        'issue' => 'Hoàn tác alt text V1 không tự nhiên',
                        'before' => 'Alt có " - keyword" suffix',
                        'after' => 'Đã xóa prefix/suffix không tự nhiên',
                    ];
                }
            }

            // ================================================================
            // STEP 1: Keyword trong Meta Title — Viết lại tự nhiên
            // ================================================================
            if (mb_stripos($metaTitle, $keyword) === false) {
                $oldMetaTitle = $metaTitle;
                // Nếu chưa có meta title riêng, tạo mới từ title + keyword
                if (empty($metaTitle) || $metaTitle === $title) {
                    // Title thường đã chứa keyword rồi, check lại
                    if (mb_stripos($title, $keyword) !== false) {
                        $metaTitle = $title;
                    } else {
                        // Viết meta title tự nhiên chứa keyword
                        $metaTitle = $this->generateNaturalMetaTitle($title, $keyword);
                    }
                } else {
                    $metaTitle = $this->generateNaturalMetaTitle($metaTitle, $keyword);
                }
                
                // Giới hạn 60 ký tự
                if (mb_strlen($metaTitle) > 60) {
                    $metaTitle = mb_substr($metaTitle, 0, 57) . '...';
                }
                
                if ($oldMetaTitle !== $metaTitle) {
                    $metaTitleChanged = true;
                    $postFixes[] = [
                        'type' => 'meta_title',
                        'issue' => 'Keyword không có trong meta title',
                        'before' => $oldMetaTitle,
                        'after' => $metaTitle,
                    ];
                }
            }

            // ================================================================
            // STEP 2: Keyword trong đoạn mở đầu (first paragraph)
            // ================================================================
            $firstPara = '';
            if (preg_match('/<p[^>]*>(.*?)<\/p>/is', $content, $paraMatch)) {
                $firstPara = mb_strtolower(strip_tags($paraMatch[1]));
            }
            
            if (mb_strpos($firstPara, $keyword) === false && !empty($firstPara)) {
                // Thêm câu chứa keyword vào đầu paragraph đầu tiên
                $introSentence = $this->generateIntroSentence($keyword, $title);
                if (preg_match('/<p([^>]*)>(.*?)<\/p>/is', $content, $paraMatch)) {
                    $oldPara = $paraMatch[2];
                    $newPara = $introSentence . ' ' . $oldPara;
                    $content = preg_replace(
                        '/<p' . preg_quote($paraMatch[1], '/') . '>' . preg_quote($paraMatch[2], '/') . '<\/p>/is',
                        '<p' . $paraMatch[1] . '>' . $newPara . '</p>',
                        $content,
                        1
                    );
                    $contentChanged = true;
                    $postFixes[] = [
                        'type' => 'keyword_in_intro',
                        'issue' => 'Keyword chưa có trong đoạn mở đầu',
                        'before' => mb_substr(strip_tags($oldPara), 0, 80) . '...',
                        'after' => mb_substr(strip_tags($newPara), 0, 80) . '...',
                    ];
                }
            }

            // ================================================================
            // STEP 3: Keyword trong heading (H2) — Viết lại tự nhiên
            // ================================================================
            $hasKeywordInHeading = (bool) preg_match('/<h[2-3][^>]*>.*?' . preg_quote($keyword, '/') . '.*?<\/h[2-3]>/isu', $content);
            if (!$hasKeywordInHeading) {
                // Tìm heading H2 phù hợp nhất (liên quan đến keyword) để viết lại
                preg_match_all('/<h2([^>]*)>(.*?)<\/h2>/isu', $content, $h2Matches, PREG_SET_ORDER);
                
                $headingFixed = false;
                if (!empty($h2Matches)) {
                    // Chọn heading đầu tiên phù hợp (không quá dài)
                    foreach ($h2Matches as $h2) {
                        $oldHeading = strip_tags($h2[2]);
                        if (mb_strlen($oldHeading) < 50 && mb_strlen($oldHeading) > 3) {
                            $newHeading = $this->rewriteHeadingWithKeyword($oldHeading, $keyword, $title);
                            if ($newHeading !== $oldHeading) {
                                $content = str_replace(
                                    '<h2' . $h2[1] . '>' . $h2[2] . '</h2>',
                                    '<h2' . $h2[1] . '>' . $newHeading . '</h2>',
                                    $content
                                );
                                $contentChanged = true;
                                $headingFixed = true;
                                $postFixes[] = [
                                    'type' => 'heading',
                                    'issue' => 'Keyword không có trong heading',
                                    'before' => $oldHeading,
                                    'after' => $newHeading,
                                ];
                                break;
                            }
                        }
                    }
                }
                
                if (!$headingFixed) {
                    // Không tìm được H2 phù hợp → không thể fix
                    $postCannotFix[] = [
                        'type' => 'heading',
                        'reason' => 'Không tìm được heading phù hợp để thêm keyword tự nhiên',
                    ];
                }
            }

            // ================================================================
            // STEP 4: Keyword trong Meta Description
            // ================================================================
            if (!empty($metaDescription) && mb_stripos($metaDescription, $keyword) === false) {
                $oldMeta = $metaDescription;
                $metaDescription = $this->addKeywordToMetaDesc($metaDescription, $keyword);
                if ($oldMeta !== $metaDescription) {
                    $metaDescChanged = true;
                    $postFixes[] = [
                        'type' => 'keyword_in_meta_desc',
                        'issue' => 'Keyword không có trong meta description',
                        'before' => $oldMeta,
                        'after' => $metaDescription,
                    ];
                }
            }

            // ================================================================
            // STEP 5: Tối ưu độ dài Meta Description (120-160 chars)
            // ================================================================
            $metaLen = mb_strlen($metaDescription);
            if ($metaLen > 0 && ($metaLen < 120 || $metaLen > 160)) {
                $oldMeta = $metaDescription;
                if ($metaLen > 160) {
                    // Cắt bớt cho vừa 155 chars, giữ câu hoàn chỉnh
                    $metaDescription = $this->truncateMetaDesc($metaDescription, 155);
                    $metaDescChanged = true;
                    $postFixes[] = [
                        'type' => 'meta_desc_length',
                        'issue' => "Meta description quá dài ({$metaLen} ký tự)",
                        'before' => $oldMeta,
                        'after' => $metaDescription . ' (' . mb_strlen($metaDescription) . ' ký tự)',
                    ];
                }
                // Nếu quá ngắn, thêm call-to-action
                elseif ($metaLen < 120 && $metaLen > 50) {
                    $metaDescription = $this->extendMetaDesc($metaDescription, $keyword, $title);
                    if (mb_strlen($metaDescription) >= 120 && mb_strlen($metaDescription) <= 160) {
                        $metaDescChanged = true;
                        $postFixes[] = [
                            'type' => 'meta_desc_length',
                            'issue' => "Meta description quá ngắn ({$metaLen} ký tự)",
                            'before' => $oldMeta,
                            'after' => $metaDescription . ' (' . mb_strlen($metaDescription) . ' ký tự)',
                        ];
                    } else {
                        $metaDescription = $oldMeta; // revert nếu kêt quả không tốt
                    }
                }
            }

            // ================================================================
            // STEP 6: Mật độ từ khóa — Thêm tự nhiên nếu < 1%
            // ================================================================
            $plainContent = mb_strtolower(strip_tags($content));
            $wordCount = $this->countWords($plainContent);
            $keywordCount = mb_substr_count($plainContent, $keyword);
            $keywordWords = $this->countWords($keyword);
            $density = $wordCount > 0 ? ($keywordCount * $keywordWords / $wordCount) * 100 : 0;
            
            if ($density < 1 && $wordCount > 100) {
                // Tính cần thêm bao nhiêu lần keyword để đạt ~1.2%
                $targetDensity = 1.2;
                $targetCount = ceil(($targetDensity / 100) * $wordCount / $keywordWords);
                $needMore = max(1, (int)($targetCount - $keywordCount));
                $needMore = min($needMore, 3); // Tối đa thêm 3 lần
                
                $densitySentences = $this->generateDensitySentences($keyword, $title, $needMore);
                if (!empty($densitySentences)) {
                    $insertedCount = 0;
                    foreach ($densitySentences as $sentence) {
                        // Tìm vị trí H2 để chèn trước
                        $insertPos = $this->findInsertPosition($content, $insertedCount);
                        if ($insertPos !== false) {
                            $content = mb_substr($content, 0, $insertPos) 
                                . '<p>' . $sentence . '</p>' . "\n"
                                . mb_substr($content, $insertPos);
                            $insertedCount++;
                            $contentChanged = true;
                        }
                    }
                    
                    if ($insertedCount > 0) {
                        $newPlain = mb_strtolower(strip_tags($content));
                        $newCount = mb_substr_count($newPlain, $keyword);
                        $newWordCount = $this->countWords($newPlain);
                        $newDensity = ($newCount * $keywordWords / $newWordCount) * 100;
                        
                        $postFixes[] = [
                            'type' => 'keyword_density',
                            'issue' => 'Mật độ từ khóa quá thấp (' . round($density, 1) . '%)',
                            'before' => round($density, 1) . '% (' . $keywordCount . ' lần)',
                            'after' => 'Thêm ' . $insertedCount . ' câu → ước tính ~' . round($newDensity, 1) . '%',
                        ];
                    }
                }
            }

            // ================================================================
            // STEP 7: Alt text hình ảnh — Viết alt mô tả tự nhiên
            // ================================================================
            $hasKeywordInAlt = (bool) preg_match('/alt\s*=\s*["\'][^"\']*' . preg_quote($keyword, '/') . '[^"\']*["\']/isu', $content);
            if (!$hasKeywordInAlt) {
                // Tìm ảnh đầu tiên
                if (preg_match('/<img([^>]*?)alt\s*=\s*["\']([^"\']*)["\']([^>]*?)>/iu', $content, $imgMatch)) {
                    $oldAlt = $imgMatch[2];
                    $newAlt = $this->generateImageAlt($keyword, $title, $oldAlt);
                    $content = str_replace(
                        $imgMatch[0],
                        '<img' . $imgMatch[1] . 'alt="' . $newAlt . '"' . $imgMatch[3] . '>',
                        $content
                    );
                    $contentChanged = true;
                    $postFixes[] = [
                        'type' => 'image_alt',
                        'issue' => 'Keyword không có trong alt text ảnh',
                        'before' => 'alt="' . $oldAlt . '"',
                        'after' => 'alt="' . $newAlt . '"',
                    ];
                } elseif (preg_match('/<img([^>]*?)(?!alt)[^>]*>/iu', $content, $imgNoAlt)) {
                    // Ảnh không có alt attribute → thêm mới
                    $newAlt = $this->generateImageAlt($keyword, $title, '');
                    $newImg = str_replace('<img', '<img alt="' . $newAlt . '"', $imgNoAlt[0]);
                    $content = str_replace($imgNoAlt[0], $newImg, $content);
                    $contentChanged = true;
                    $postFixes[] = [
                        'type' => 'image_alt',
                        'issue' => 'Ảnh thiếu alt text',
                        'before' => 'Không có alt',
                        'after' => 'alt="' . $newAlt . '"',
                    ];
                } else {
                    $postCannotFix[] = [
                        'type' => 'image_alt',
                        'reason' => 'Bài không có ảnh nào',
                    ];
                }
            }

            // ================================================================
            // Lưu thay đổi
            // ================================================================
            if (!empty($postFixes)) {
                $updateData = [];
                if ($contentChanged) $updateData['content'] = $content;
                if ($metaTitleChanged) $updateData['meta_title'] = $metaTitle;
                if ($metaDescChanged) $updateData['meta_description'] = $metaDescription;
                
                if (!empty($updateData) && !$dryRun) {
                    DB::table('blog_posts')->where('id', $post->id)->update($updateData);
                }
                
                $entry = [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'fixes' => $postFixes,
                ];
                if (!empty($postCannotFix)) $entry['cannot_fix'] = $postCannotFix;
                $fixed[] = $entry;
            } else {
                if (!empty($postCannotFix)) {
                    $cannotFix[] = [
                        'id' => $post->id,
                        'title' => $post->title,
                        'issues' => $postCannotFix,
                    ];
                } else {
                    $alreadyGood[] = [
                        'id' => $post->id,
                        'title' => $post->title,
                    ];
                }
            }
        }

        return [
            'mode' => $dryRun ? 'preview' : 'applied',
            'fixed' => $fixed,
            'cannot_fix' => $cannotFix,
            'already_good_count' => count($alreadyGood),
            'summary' => [
                'total_posts' => count($posts),
                'fixed_count' => count($fixed),
                'cannot_fix_count' => count($cannotFix),
                'already_good_count' => count($alreadyGood),
                'total_fixes_applied' => array_sum(array_map(fn($f) => count($f['fixes']), $fixed)),
            ],
        ];
    }

    // ============================================================
    // V2 Helper Methods — Natural Vietnamese Text Generation
    // ============================================================

    /**
     * Tạo meta title tự nhiên chứa keyword
     */
    private function generateNaturalMetaTitle(string $currentTitle, string $keyword): string
    {
        // Nếu title đã chứa keyword → giữ nguyên
        if (mb_stripos($currentTitle, $keyword) !== false) {
            return $currentTitle;
        }

        // Keyword patterns phổ biến
        $kwLower = mb_strtolower($keyword);
        
        // Check nếu keyword là tên tool (chứa "tool", "license", etc)
        if (preg_match('/(tool|license|unlock|bypass|flash|repair|frp|mdm|knox)/i', $keyword)) {
            return $currentTitle; // Thường title đã đúng cho tool posts
        }
        
        // Thêm keyword:  "Title | Keyword 2026"
        $keywordCap = mb_convert_case($keyword, MB_CASE_TITLE, 'UTF-8');
        $result = $currentTitle . ' | ' . $keywordCap;
        
        if (mb_strlen($result) > 60) {
            $result = mb_substr($currentTitle, 0, 50) . ' | ' . $keywordCap;
        }
        
        return $result;
    }

    /**
     * Tạo câu giới thiệu chứa keyword cho đoạn mở đầu
     */
    private function generateIntroSentence(string $keyword, string $title): string
    {
        $kwCap = mb_convert_case($keyword, MB_CASE_TITLE, 'UTF-8');
        $kwLower = mb_strtolower($keyword);
        
        // Detect loại bài viết
        if (preg_match('/mua license|mua credit/iu', $title)) {
            return "Bạn đang tìm cách mua {$kwLower} chính hãng với giá tốt nhất?";
        }
        if (preg_match('/hướng dẫn/iu', $title)) {
            return "Trong bài viết này, chúng tôi sẽ hướng dẫn chi tiết về {$kwLower} từ A đến Z.";
        }
        if (preg_match('/so sánh/iu', $title)) {
            return "Bài viết này sẽ giúp bạn hiểu rõ về {$kwLower} để đưa ra lựa chọn phù hợp nhất.";
        }
        if (preg_match('/cách (xóa|bypass|unlock|fix|gỡ|remove)/iu', $title)) {
            return "Tìm hiểu cách thực hiện {$kwLower} nhanh chóng và an toàn với hướng dẫn chi tiết dưới đây.";
        }
        if (preg_match('/test ?point/iu', $keyword)) {
            return "Dưới đây là hướng dẫn chi tiết vị trí {$kwLower} với hình ảnh minh họa rõ ràng.";
        }
        if (preg_match('/vietmap/iu', $keyword)) {
            return "Tìm hiểu mọi thông tin về {$kwLower} với hướng dẫn chi tiết từ Thuetaikhoan.net.";
        }
        
        // Default
        return "Tìm hiểu chi tiết về {$kwLower} với hướng dẫn đầy đủ và cập nhật mới nhất 2026.";
    }

    /**
     * Viết lại heading chứa keyword một cách tự nhiên
     */
    private function rewriteHeadingWithKeyword(string $heading, string $keyword, string $title): string
    {
        $kwLower = mb_strtolower($keyword);
        $headingLower = mb_strtolower($heading);
        
        // Nếu heading đã chứa keyword → giữ nguyên
        if (mb_strpos($headingLower, $kwLower) !== false) {
            return $heading;
        }

        // Tách keyword thành các từ quan trọng
        $keywordParts = array_filter(explode(' ', $kwLower), fn($w) => mb_strlen($w) > 2);
        
        // Kiểm tra xem keyword có phải dạng tool name không
        $isToolKeyword = (bool) preg_match('/(tool|license|unlock|bypass|flash|repair|frp|mdm|knox|vietmap|test\s*point)/iu', $kwLower);
        
        // Patterns viết lại heading tự nhiên
        $headingStrip = strip_tags($heading);
        
        // Nếu heading là "Câu hỏi thường gặp", "Kết luận", etc → viết lại cho phù hợp
        if (preg_match('/^(câu hỏi|faq|q&a)/iu', $headingStrip)) {
            return 'Câu hỏi thường gặp về ' . $kwLower;
        }
        if (preg_match('/^kết luận/iu', $headingStrip)) {
            return 'Kết luận về ' . $kwLower;
        }
        if (preg_match('/^tổng kết/iu', $headingStrip)) {
            return 'Tổng kết về ' . $kwLower;
        }
        if (preg_match('/^giới thiệu/iu', $headingStrip)) {
            return 'Giới thiệu về ' . $kwLower;
        }
        if (preg_match('/^lưu ý/iu', $headingStrip)) {
            return 'Lưu ý khi sử dụng ' . $kwLower;
        }
        if (preg_match('/^(ưu|nhược) điểm/iu', $headingStrip)) {
            return $headingStrip . ' của ' . $kwLower;
        }
        if (preg_match('/^bảng so sánh/iu', $headingStrip)) {
            return 'Bảng so sánh chi tiết ' . $kwLower;
        }
        if (preg_match('/^(bước|step)\s*\d/iu', $headingStrip)) {
            // Không sửa heading dạng "Bước 1: ..."
            return $heading;
        }
        
        // Nếu heading dạng số danh sách "1. xxx", "2. xxx" → thêm keyword context
        if (preg_match('/^\d+\.\s+(.+)/u', $headingStrip, $numMatch)) {
            $body = $numMatch[1];
            // Chỉ thêm nếu body ngắn và không liên quan keyword
            if (mb_strlen($body) < 30) {
                // Thử thêm " với/cho keyword" nếu hợp lý
                if (preg_match('/(là gì|definition|tính năng|feature|cách|phương pháp)/iu', $body)) {
                    return $heading; // Để nguyên, thêm sẽ lạ
                }
            }
        }

        // Nếu heading ngắn (< 20 chars) → append " cho keyword"
        if (mb_strlen($headingStrip) < 20) {
            if ($isToolKeyword) {
                return $headingStrip . ' cho ' . $kwLower;
            } else {
                return $headingStrip . ' về ' . $kwLower;
            }
        }

        // Default: không sửa nếu không tìm được cách tự nhiên
        return $heading;
    }

    /**
     * Thêm keyword vào meta description tự nhiên
     */
    private function addKeywordToMetaDesc(string $meta, string $keyword): string
    {
        if (mb_stripos($meta, $keyword) !== false) return $meta;
        
        $kwLower = mb_strtolower($keyword);
        
        // Thêm vào cuối nếu còn chỗ
        $suffix = ' Tìm hiểu chi tiết về ' . $kwLower . ' tại đây.';
        $result = rtrim($meta, '.') . '.' . $suffix;
        
        if (mb_strlen($result) <= 160) {
            return $result;
        }
        
        // Nếu quá dài, nhúng keyword ngắn hơn
        $suffix = ' Cập nhật ' . $kwLower . '.';
        $result = rtrim($meta, '.') . '.' . $suffix;
        
        if (mb_strlen($result) <= 160) {
            return $result;
        }
        
        // Không thể thêm → trả về nguyên bản
        return $meta;
    }

    /**
     * Cắt meta description cho vừa với limit, giữ câu hoàn chỉnh
     */
    private function truncateMetaDesc(string $meta, int $limit): string
    {
        if (mb_strlen($meta) <= $limit) return $meta;
        
        $truncated = mb_substr($meta, 0, $limit);
        
        // Cắt tại vị trí dấu câu hoặc khoảng trắng cuối cùng
        $lastPeriod = mb_strrpos($truncated, '.');
        $lastComma = mb_strrpos($truncated, ',');
        $lastSpace = mb_strrpos($truncated, ' ');
        
        $cutAt = max($lastPeriod ?: 0, $lastComma ?: 0);
        if ($cutAt < $limit * 0.6) {
            $cutAt = $lastSpace ?: $limit;
        }
        
        $result = mb_substr($meta, 0, $cutAt);
        if (mb_substr($result, -1) === ',') {
            $result = mb_substr($result, 0, -1);
        }
        
        return rtrim($result, ' ,') . '.';
    }

    /**
     * Mở rộng meta description ngắn
     */
    private function extendMetaDesc(string $meta, string $keyword, string $title): string
    {
        $kwLower = mb_strtolower($keyword);
        $clean = rtrim($meta, '.');
        
        // Thêm call-to-action phù hợp
        $extensions = [
            " Hướng dẫn chi tiết về {$kwLower} cập nhật mới nhất 2026.",
            " Cập nhật mới nhất 2026 về {$kwLower} từ chuyên gia.",
            " Tìm hiểu {$kwLower} chi tiết với hướng dẫn từ A-Z.",
        ];
        
        foreach ($extensions as $ext) {
            $result = $clean . '.' . $ext;
            $len = mb_strlen($result);
            if ($len >= 120 && $len <= 160) {
                return $result;
            }
        }
        
        return $meta; // Không mở rộng được
    }

    /**
     * Tạo câu bổ sung chứa keyword cho tăng density — tự nhiên, đa dạng
     */
    private function generateDensitySentences(string $keyword, string $title, int $count): array
    {
        $kwLower = mb_strtolower($keyword);
        $kwCap = mb_convert_case($keyword, MB_CASE_TITLE, 'UTF-8');
        
        $sentences = [];
        
        // Pool câu tự nhiên — xoay vòng để không lặp lại
        $isProduct = (bool) preg_match('/(mua|license|credit|tool|key)/iu', $title);
        $isTutorial = (bool) preg_match('/(hướng dẫn|cách|how)/iu', $title);
        $isComparison = (bool) preg_match('/(so sánh|vs|versus|đánh giá)/iu', $title);
        
        if ($isProduct) {
            $pool = [
                "Khi sử dụng {$kwLower}, bạn sẽ được hỗ trợ kỹ thuật 24/7 từ đội ngũ chuyên gia của chúng tôi.",
                "Nhiều thợ sửa điện thoại đã tin dùng {$kwLower} trong công việc hàng ngày nhờ tính năng mạnh mẽ và ổn định.",
                "Với {$kwLower}, bạn có thể tiết kiệm chi phí đáng kể so với việc mua license trọn đời.",
                "Thuetaikhoan.net là đại lý chính hãng cung cấp {$kwLower} với giá tốt nhất thị trường Việt Nam.",
            ];
        } elseif ($isTutorial) {
            $pool = [
                "Quy trình thực hiện {$kwLower} không quá phức tạp nếu bạn làm đúng theo hướng dẫn chi tiết dưới đây.",
                "Trước khi tiến hành {$kwLower}, hãy đảm bảo bạn đã sao lưu dữ liệu quan trọng trên thiết bị.",
                "Nhiều người dùng đã thực hiện thành công {$kwLower} chỉ trong vài phút với phương pháp này.",
                "Nếu gặp khó khăn trong quá trình {$kwLower}, hãy liên hệ Thuetaikhoan.net để được hỗ trợ miễn phí.",
            ];
        } elseif ($isComparison) {
            $pool = [
                "Việc hiểu rõ về {$kwLower} sẽ giúp bạn đưa ra quyết định phù hợp nhất cho nhu cầu của mình.",
                "Mỗi công cụ trong {$kwLower} đều có ưu và nhược điểm riêng tùy thuộc vào tình huống sử dụng.",
                "Dựa trên kinh nghiệm thực tế, chúng tôi đã kiểm tra kỹ từng yếu tố trong {$kwLower} để đưa ra đánh giá khách quan.",
            ];
        } else {
            $pool = [
                "Hiểu rõ về {$kwLower} sẽ giúp bạn xử lý các tình huống phổ biến trong nghề sửa chữa điện thoại.",
                "Thông tin về {$kwLower} trong bài viết này được cập nhật mới nhất cho năm 2026.",
                "Nếu bạn cần hỗ trợ thêm về {$kwLower}, đội ngũ Thuetaikhoan.net luôn sẵn sàng tư vấn miễn phí.",
                "Các chuyên gia trong ngành GSM đều khuyên bạn nên nắm vững kiến thức về {$kwLower} trước khi thực hành.",
            ];
        }
        
        // Chọn $count câu từ pool, không lặp
        $selected = array_slice($pool, 0, min($count, count($pool)));
        
        return $selected;
    }

    /**
     * Tìm vị trí chèn đoạn văn (trước heading H2 tiếp theo)
     */
    private function findInsertPosition(string $content, int $skipCount): int|false
    {
        $offset = 0;
        $found = 0;
        
        while (preg_match('/<h2[^>]*>/iu', $content, $match, PREG_OFFSET_CAPTURE, $offset)) {
            if ($found >= $skipCount + 1) { // Skip first H2 (already has content), insert before 2nd, 3rd, etc
                return $match[0][1];
            }
            $found++;
            $offset = $match[0][1] + strlen($match[0][0]);
        }
        
        // Nếu không đủ H2, chèn trước heading cuối
        if ($found > 0) {
            // Tìm H2 cuối cùng
            if (preg_match_all('/<h2[^>]*>/iu', $content, $allH2, PREG_OFFSET_CAPTURE)) {
                $lastH2 = end($allH2[0]);
                return $lastH2[1];
            }
        }
        
        return false;
    }

    /**
     * Tạo alt text tự nhiên cho hình ảnh
     */
    private function generateImageAlt(string $keyword, string $title, string $existingAlt): string
    {
        $kwLower = mb_strtolower($keyword);
        
        // Nếu đã có alt → giữ nguyên + thêm keyword context
        if (!empty($existingAlt)) {
            // Kiểm tra nếu alt đã chứa keyword
            if (mb_stripos($existingAlt, $kwLower) !== false) {
                return $existingAlt;
            }
            
            // Nếu alt liên quan (chứa từ chung với keyword)
            if (mb_strlen($existingAlt) < 80) {
                return $existingAlt . ' - Hướng dẫn ' . $kwLower;
            }
            
            return $existingAlt;
        }
        
        // Tạo alt mới từ title + keyword
        if (preg_match('/test\s*point/iu', $kwLower)) {
            return 'Vị trí ' . $kwLower . ' - Sơ đồ chi tiết';
        }
        
        if (preg_match('/(mua|license)/iu', $title)) {
            return 'Mua ' . $kwLower . ' giá rẻ tại Thuetaikhoan.net';
        }
        
        return 'Hướng dẫn ' . $kwLower . ' chi tiết 2026';
    }

    // ============================================================
    // KEYWORD RANKING TRACKER
    // Theo dõi thứ hạng từ khóa trên Google
    // ============================================================

    /**
     * Lấy tất cả từ khóa đang theo dõi + vị trí mới nhất
     */
    public function keywordRankings(): array
    {
        return DB::table('keyword_rankings')
            ->orderBy('keyword')
            ->get()
            ->map(function ($item) {
                $change = null;
                if ($item->position && $item->previous_position) {
                    $change = $item->previous_position - $item->position; // dương = tăng hạng
                }
                $item->change = $change;
                $item->status_class = $this->rankStatusClass($item->position);
                return $item;
            })
            ->toArray();
    }

    /**
     * Thêm từ khóa mới để theo dõi
     */
    public function addKeywordTracking(string $keyword, string $url): int
    {
        return DB::table('keyword_rankings')->insertGetId([
            'keyword' => trim($keyword),
            'url' => trim($url),
            'search_engine' => 'google',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Xóa từ khóa khỏi danh sách theo dõi
     */
    public function deleteKeywordTracking(int $id): bool
    {
        DB::table('keyword_ranking_history')->where('keyword_ranking_id', $id)->delete();
        return DB::table('keyword_rankings')->where('id', $id)->delete() > 0;
    }

    /**
     * Kiểm tra thứ hạng trên Google cho tất cả từ khóa
     * Dùng scrape Google search results
     */
    public function updateKeywordRankings(): array
    {
        $keywords = DB::table('keyword_rankings')->get();
        $results = [];

        foreach ($keywords as $kw) {
            try {
                $position = $this->checkGooglePosition($kw->keyword, $kw->url);
                
                // Lưu previous position
                $previousPosition = $kw->position;
                
                // Cập nhật vị trí
                DB::table('keyword_rankings')->where('id', $kw->id)->update([
                    'previous_position' => $previousPosition,
                    'position' => $position,
                    'checked_at' => now()->toDateString(),
                    'updated_at' => now(),
                ]);

                // Lưu lịch sử
                DB::table('keyword_ranking_history')->insert([
                    'keyword_ranking_id' => $kw->id,
                    'position' => $position,
                    'checked_at' => now()->toDateString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $results[] = [
                    'keyword' => $kw->keyword,
                    'position' => $position,
                    'previous' => $previousPosition,
                    'status' => 'ok',
                ];

                // Delay để tránh bị Google block
                usleep(rand(2000000, 4000000)); // 2-4 giây

            } catch (\Exception $e) {
                $results[] = [
                    'keyword' => $kw->keyword,
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Kiểm tra vị trí URL trên Google cho 1 từ khóa
     * Trả về position (1-100) hoặc null nếu không tìm thấy
     */
    private function checkGooglePosition(string $keyword, string $targetUrl): ?int
    {
        $encodedQuery = urlencode($keyword);
        $googleUrl = "https://www.google.com/search?q={$encodedQuery}&num=100&hl=vi&gl=vn";

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: vi-VN,vi;q=0.9,en;q=0.8',
                ]),
                'timeout' => 15,
            ],
        ]);

        $html = @file_get_contents($googleUrl, false, $context);

        if ($html === false) {
            throw new \Exception('Không thể kết nối Google Search');
        }

        // Parse domain từ target URL
        $targetDomain = parse_url($targetUrl, PHP_URL_HOST) ?: $targetUrl;
        $targetPath = parse_url($targetUrl, PHP_URL_PATH) ?: '';
        
        // Tìm tất cả URLs trong kết quả tìm kiếm
        $position = 0;
        
        // Pattern: tìm các link trong search results
        // Google sử dụng <a href="/url?q=..." hoặc direct links
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $html, $linkMatches);
        
        $seenUrls = [];
        foreach ($linkMatches[1] as $link) {
            // Decode Google redirect URLs
            if (str_contains($link, '/url?q=')) {
                parse_str(parse_url($link, PHP_URL_QUERY) ?? '', $params);
                $link = $params['q'] ?? $link;
            }

            // Bỏ qua internal Google links
            if (str_contains($link, 'google.com') || str_contains($link, 'googleapis.com')) {
                continue;
            }

            // Chỉ tính URLs hợp lệ
            if (!str_starts_with($link, 'http')) {
                continue;
            }

            // Tránh đếm trùng
            $linkDomain = parse_url($link, PHP_URL_HOST) ?: '';
            $linkKey = $linkDomain . (parse_url($link, PHP_URL_PATH) ?: '/');
            if (isset($seenUrls[$linkKey])) {
                continue;
            }
            $seenUrls[$linkKey] = true;
            $position++;

            // So sánh domain + path
            if (str_contains($link, $targetDomain)) {
                if (empty($targetPath) || $targetPath === '/' || str_contains($link, $targetPath)) {
                    return $position;
                }
            }

            if ($position >= 100) break;
        }

        return null; // Không tìm thấy trong top 100
    }

    /**
     * Lịch sử vị trí 30 ngày cho biểu đồ
     */
    public function keywordRankingHistory(int $keywordId, int $days = 30): array
    {
        return DB::table('keyword_ranking_history')
            ->where('keyword_ranking_id', $keywordId)
            ->where('checked_at', '>=', now()->subDays($days)->toDateString())
            ->orderBy('checked_at')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->checked_at,
                    'position' => $item->position,
                ];
            })
            ->toArray();
    }

    /**
     * CSS class cho vị trí thứ hạng
     */
    private function rankStatusClass(?int $position): string
    {
        if ($position === null) return 'rank-none';
        if ($position <= 3) return 'rank-top3';
        if ($position <= 10) return 'rank-top10';
        if ($position <= 30) return 'rank-mid';
        return 'rank-low';
    }
}


