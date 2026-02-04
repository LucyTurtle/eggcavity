<?php

namespace App\Support;

use DOMDocument;
use DOMNode;
use DOMElement;

class SafeArchiveHtml
{
    private const ALLOWED_TAGS = [
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'p', 'br', 'strong', 'em', 'i', 'b',
        'ul', 'ol', 'li', 'div', 'span', 'a',
    ];

    /**
     * Sanitize content for archive descriptions. If content looks like HTML,
     * allows safe tags and preserves href on links. Otherwise treats as plain text.
     */
    public static function sanitize(?string $content): string
    {
        if ($content === null || $content === '') {
            return '';
        }

        $trimmed = trim($content);
        if ($trimmed === '') {
            return '';
        }

        // Plain text (no HTML): escape and convert newlines to <br>
        if (strpos($trimmed, '<') === false || strpos($trimmed, '>') === false) {
            return nl2br(e($trimmed));
        }

        return self::sanitizeHtml($trimmed);
    }

    private static function sanitizeHtml(string $html): string
    {
        $doc = new DOMDocument();
        $doc->encoding = 'UTF-8';

        $internalErrors = libxml_use_internal_errors(true);
        try {
            // Wrap in a div so we have a single root; loadHTML expects a document
            $doc->loadHTML(
                '<div id="__root">' . $html . '</div>',
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            );
        } catch (\Throwable $e) {
            libxml_use_internal_errors($internalErrors);
            return nl2br(e($html));
        }
        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        $root = $doc->getElementById('__root');
        if (! $root) {
            return nl2br(e($html));
        }

        self::sanitizeNode($root);
        $body = $root->ownerDocument->saveHTML($root);

        // Remove the wrapper <div id="__root"> and </div>
        $body = preg_replace('/^\s*<div id="__root">\s*/s', '', $body);
        $body = preg_replace('/\s*<\/div>\s*$/s', '', $body);

        return trim($body);
    }

    private static function sanitizeNode(DOMNode $node): void
    {
        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return;
        }

        /** @var DOMElement $node */
        $tag = strtolower($node->nodeName);

        if (! in_array($tag, self::ALLOWED_TAGS, true)) {
            // Sanitize children first, then replace this node with its children (unwrap)
            $children = [];
            foreach ($node->childNodes as $child) {
                $children[] = $child;
            }
            foreach ($children as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE) {
                    self::sanitizeNode($child);
                }
            }
            $fragment = $node->ownerDocument->createDocumentFragment();
            while ($node->firstChild) {
                $fragment->appendChild($node->firstChild);
            }
            $node->parentNode->replaceChild($fragment, $node);
            return;
        }

        // Strip disallowed attributes: <a> only href; others allow class and style
        $toRemove = [];
        foreach ($node->attributes as $attr) {
            $name = strtolower($attr->name);
            if ($tag === 'a') {
                if ($name === 'href') {
                    $value = trim($attr->value);
                    if (! self::isAllowedUrl($value)) {
                        $toRemove[] = $attr->name;
                    }
                } else {
                    $toRemove[] = $attr->name;
                }
            } elseif ($name === 'class' || $name === 'style') {
                // Allow class/style on div/span etc. for archive styling
            } else {
                $toRemove[] = $attr->name;
            }
        }
        foreach ($toRemove as $name) {
            $node->removeAttribute($name);
        }

        // Recurse into children (copy into array because we'll modify)
        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }
        foreach ($children as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                self::sanitizeNode($child);
            }
        }
    }

    private static function isAllowedUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }
        // Allow relative URLs
        if (str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return true;
        }
        // Allow http and https
        return preg_match('#^https?://#i', $url) === 1;
    }
}
