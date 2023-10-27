<?php
declare(strict_types=1);

namespace Yireo\NextGenImages\Util;

use DOMDocument;

class DomUtils
{
    /**
     * @param string $html
     * @return DOMDocument
     */
    public function htmlToDOMDocument(string $html): DOMDocument
    {
        $document = new DOMDocument();
        if (empty($html)) {
            return $document;
        }
        libxml_use_internal_errors(true);

        $convmap = [0x80, 0x10FFFF, 0, 0x1FFFFF];
        $encodedHtml = mb_encode_numericentity(
            $html,
            $convmap,
            'UTF-8'
        );

        $document->loadHTML(
            $encodedHtml,
            LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED
        );

        libxml_clear_errors();
        libxml_use_internal_errors(false);
        $document->encoding = 'utf-8';
        return $document;
    }
}
