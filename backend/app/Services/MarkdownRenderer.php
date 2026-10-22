<?php
namespace App\Services;

class MarkdownRenderer {

    /**
     * Convert Markdown into clean, responsive, SEO/AEO-optimized semantic HTML.
     */
    public static function render(string $markdown): string {
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $markdown));
        $output = [];
        $inTable = false;
        $tableRows = [];
        $inList = false;
        $listType = 'ul';

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // 1. Table Detection
            if (str_starts_with($trimmed, '|') && str_ends_with($trimmed, '|')) {
                // If in a list, close list first
                if ($inList) {
                    $output[] = "</{$listType}>";
                    $inList = false;
                }
                $inTable = true;
                $tableRows[] = $trimmed;
                continue;
            } elseif ($inTable) {
                // Flush table
                $output[] = self::renderTable($tableRows);
                $tableRows = [];
                $inTable = false;
            }

            // 2. Unordered / Ordered Lists
            if (preg_match('/^[-*]\s+(.*)$/', $trimmed, $m)) {
                if (!$inList || $listType !== 'ul') {
                    if ($inList) $output[] = "</{$listType}>";
                    $output[] = '<ul class="article-list" style="margin: 1.25rem 0 1.5rem 1.5rem; line-height: 1.8; color: var(--text-secondary);">';
                    $inList = true;
                    $listType = 'ul';
                }
                $output[] = '<li style="margin-bottom: 0.5rem;">' . self::inlineFormat($m[1]) . '</li>';
                continue;
            } elseif (preg_match('/^(\d+)\.\s+(.*)$/', $trimmed, $m)) {
                if (!$inList || $listType !== 'ol') {
                    if ($inList) $output[] = "</{$listType}>";
                    $output[] = '<ol class="article-ordered-list" style="margin: 1.25rem 0 1.5rem 1.5rem; line-height: 1.8; color: var(--text-secondary);">';
                    $inList = true;
                    $listType = 'ol';
                }
                $output[] = '<li style="margin-bottom: 0.5rem;">' . self::inlineFormat($m[2]) . '</li>';
                continue;
            } elseif ($inList && !empty($trimmed)) {
                // Continuation of list or closing
            } elseif ($inList && empty($trimmed)) {
                $output[] = "</{$listType}>";
                $inList = false;
                continue;
            }

            // 3. Horizontal Rule
            if ($trimmed === '---' || $trimmed === '***' || $trimmed === '___') {
                $output[] = '<hr style="border: 0; height: 1px; background: var(--border-subtle, #e2e8f0); margin: 2rem 0;">';
                continue;
            }

            // 4. Blockquotes
            if (str_starts_with($trimmed, '>')) {
                $quoteText = trim(substr($trimmed, 1));
                $output[] = '<blockquote style="border-left: 4px solid var(--primary-red, #dc2626); background: var(--bg-subtle, #f8fafc); padding: 0.85rem 1.25rem; margin: 1.25rem 0; border-radius: 0 8px 8px 0; color: var(--text-secondary, #475569); font-size: 0.95rem; line-height: 1.6;">' . self::inlineFormat($quoteText) . '</blockquote>';
                continue;
            }

            // 5. Headings with Anchors for SEO
            if (preg_match('/^(#{1,6})\s+(.*)$/', $trimmed, $hm)) {
                $level = strlen($hm[1]);
                $headingText = trim($hm[2]);
                $cleanText = strtolower(strip_tags(self::inlineFormat($headingText)));
                $slugId = trim(preg_replace('/[^a-z0-9]+/i', '-', $cleanText), '-');

                if ($level === 1) {
                    $output[] = "<h1 id=\"{$slugId}\" style=\"font-family: var(--font-heading); font-size: 2.1rem; font-weight: 800; color: var(--text-primary); margin: 1.8rem 0 1rem; letter-spacing: -0.02em; line-height: 1.25;\">{$headingText}</h1>";
                } elseif ($level === 2) {
                    $output[] = "<h2 id=\"{$slugId}\" style=\"font-family: var(--font-heading); font-size: 1.55rem; font-weight: 700; color: var(--primary-red-dark, #991b1b); margin: 2.2rem 0 0.85rem; letter-spacing: -0.01em; border-bottom: 1px solid var(--border-subtle, #f1f5f9); padding-bottom: 0.4rem;\">{$headingText}</h2>";
                } elseif ($level === 3) {
                    $output[] = "<h3 id=\"{$slugId}\" style=\"font-family: var(--font-heading); font-size: 1.25rem; font-weight: 600; color: var(--text-primary); margin: 1.5rem 0 0.6rem;\">{$headingText}</h3>";
                } else {
                    $output[] = "<h4 id=\"{$slugId}\" style=\"font-family: var(--font-heading); font-size: 1.1rem; font-weight: 600; color: var(--text-secondary); margin: 1.25rem 0 0.5rem;\">{$headingText}</h4>";
                }
                continue;
            }

            // 6. Regular Paragraphs
            if (!empty($trimmed)) {
                $output[] = '<p style="color: var(--text-secondary, #334155); font-size: 1.025rem; line-height: 1.8; margin-bottom: 1.25rem;">' . self::inlineFormat($trimmed) . '</p>';
            }
        }

        // Close any dangling structures
        if ($inTable) {
            $output[] = self::renderTable($tableRows);
        }
        if ($inList) {
            $output[] = "</{$listType}>";
        }

        return implode("\n", $output);
    }

    /**
     * Render Markdown Pipe Table into clean responsive HTML table
     */
    private static function renderTable(array $rows): string {
        if (empty($rows)) return '';

        $html = '<div class="table-scroll-wrapper" style="overflow-x: auto; margin: 1.5rem 0; border: 1px solid var(--border-subtle, #e2e8f0); border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">';
        $html .= '<table class="data-table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.95rem;">';

        $isHeader = true;
        $hasTbody = false;

        foreach ($rows as $r) {
            $cells = array_map('trim', explode('|', trim($r, '|')));
            // Check if separator line
            $isSeparator = true;
            foreach ($cells as $c) {
                if (!preg_match('/^:?-+:?$/', $c)) {
                    $isSeparator = false;
                    break;
                }
            }
            if ($isSeparator) {
                $isHeader = false;
                continue;
            }

            if ($isHeader) {
                $html .= '<thead style="background: var(--bg-surface-elevated, #f8fafc); border-bottom: 2px solid var(--border-subtle, #cbd5e1);">';
                $html .= '<tr>';
                foreach ($cells as $c) {
                    $html .= '<th style="padding: 0.85rem 1.1rem; font-weight: 700; color: var(--text-primary, #0f172a);">' . self::inlineFormat($c) . '</th>';
                }
                $html .= '</tr></thead>';
                $html .= '<tbody>';
                $hasTbody = true;
                $isHeader = false;
            } else {
                if (!$hasTbody) {
                    $html .= '<tbody>';
                    $hasTbody = true;
                }
                $html .= '<tr style="border-bottom: 1px solid var(--border-subtle, #f1f5f9);">';
                foreach ($cells as $c) {
                    $html .= '<td style="padding: 0.85rem 1.1rem; color: var(--text-secondary, #334155);">' . self::inlineFormat($c) . '</td>';
                }
                $html .= '</tr>';
            }
        }

        if ($hasTbody) {
            $html .= '</tbody>';
        }
        $html .= '</table></div>';
        return $html;
    }

    /**
     * Inline formatting: Bold, Italic, Code, Links
     */
    private static function inlineFormat(string $text): string {
        // Code blocks `code`
        $text = preg_replace('/`([^`]+)`/', '<code style="background: var(--bg-subtle, #f1f5f9); color: var(--primary-red, #dc2626); padding: 0.15rem 0.4rem; border-radius: 4px; font-size: 0.9em;">$1</code>', $text);
        // Bold **text**
        $text = preg_replace('/\*\*(.*?)\*\*/', '<strong style="color: var(--text-primary, #0f172a);">$1</strong>', $text);
        // Italic *text* or _text_
        $text = preg_replace('/(?<!\*)\*(?!\*)(.*?)(?<!\*)\*(?!\*)/', '<em>$1</em>', $text);
        $text = preg_replace('/(?<!_)_(?!_)(.*?)(?<!_)_(?!_)/', '<em>$1</em>', $text);
        // Markdown Links [anchor](url)
        $text = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2" target="_blank" rel="noopener" style="color: var(--primary-red, #dc2626); font-weight: 600; text-decoration: underline;">$1</a>', $text);

        return $text;
    }
}
