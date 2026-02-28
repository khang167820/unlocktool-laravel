<?php

namespace App\Services;

class SEOSchemaGenerator
{
    /**
     * Extract FAQ from HTML content
     */
    public function extractFAQFromContent(string $content): ?array
    {
        $faqs = [];
        $dom = new \DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new \DOMXPath($dom);
        
        // Pattern 1: .faq-item > .faq-question + .faq-answer
        $faqItems = $xpath->query('//*[contains(@class, "faq-item")]');
        foreach ($faqItems as $item) {
            $questionNode = $xpath->query('.//*[contains(@class, "faq-question")]', $item)->item(0);
            $answerNode = $xpath->query('.//*[contains(@class, "faq-answer")]', $item)->item(0);
            if ($questionNode && $answerNode) {
                $q = trim($questionNode->textContent);
                $a = trim($answerNode->textContent);
                if (mb_strlen($a) > 20) {
                    $faqs[] = ['question' => $q, 'answer' => $a];
                }
            }
        }
        
        // Pattern 2: Headings with question markers
        if (empty($faqs)) {
            $questionMarkers = ['?', 'How', 'What', 'Why', 'When', 'Can', 'Is', 'Do', 'Làm sao', 'Tại sao', 'Có nên', 'Khi nào', 'Bao lâu'];
            $headings = $xpath->query('//h2 | //h3');
            foreach ($headings as $heading) {
                $questionText = trim($heading->textContent);
                $isQuestion = false;
                foreach ($questionMarkers as $marker) {
                    if (stripos($questionText, $marker) !== false || str_ends_with($questionText, '?')) {
                        $isQuestion = true;
                        break;
                    }
                }
                if (!$isQuestion) continue;
                $nextElement = $heading->nextSibling;
                while ($nextElement && $nextElement->nodeType !== XML_ELEMENT_NODE) {
                    $nextElement = $nextElement->nextSibling;
                }
                if ($nextElement && in_array($nextElement->nodeName, ['p', 'div', 'ul'])) {
                    $answerText = trim($nextElement->textContent);
                    if (mb_strlen($answerText) > 20) {
                        $faqs[] = ['question' => $questionText, 'answer' => $answerText];
                    }
                }
            }
        }
        
        return count($faqs) >= 2 ? $faqs : null;
    }
    
    /**
     * Generate FAQ Schema JSON-LD
     */
    public function generateFAQSchema(array $faqs): string
    {
        $schema = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => []];
        foreach ($faqs as $faq) {
            $schema['mainEntity'][] = [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['answer']]
            ];
        }
        return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
    
    /**
     * Extract HowTo steps from content
     */
    public function extractHowToFromContent(string $content): ?array
    {
        $steps = [];
        $dom = new \DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new \DOMXPath($dom);
        $orderedLists = $xpath->query('//ol/li');
        if ($orderedLists->length >= 3) {
            foreach ($orderedLists as $index => $li) {
                $stepText = trim($li->textContent);
                if (strlen($stepText) > 10) {
                    $steps[] = ['position' => $index + 1, 'name' => $stepText, 'text' => $stepText];
                }
            }
        }
        return count($steps) >= 3 ? $steps : null;
    }
    
    /**
     * Generate HowTo Schema JSON-LD
     */
    public function generateHowToSchema(string $title, array $steps, ?string $description = null): string
    {
        $schema = ['@context' => 'https://schema.org', '@type' => 'HowTo', 'name' => $title, 'step' => []];
        if ($description) $schema['description'] = $description;
        foreach ($steps as $step) {
            $schema['step'][] = ['@type' => 'HowToStep', 'position' => $step['position'], 'name' => $step['name'], 'text' => $step['text']];
        }
        return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
    
    /**
     * Check if content is a tutorial/how-to guide
     */
    public function isHowToContent(string $title, string $content): bool
    {
        $markers = ['hướng dẫn', 'cách', 'how to', 'tutorial', 'guide', 'step by step', 'từng bước'];
        $titleLower = mb_strtolower($title);
        foreach ($markers as $marker) {
            if (stripos($titleLower, $marker) !== false) return true;
        }
        return false;
    }
}
