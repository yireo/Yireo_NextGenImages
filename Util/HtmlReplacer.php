<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Util;

use DOMDocument;
use DOMElement;
use DOMNode;
use Yireo\NextGenImages\Block\PictureFactory;
use Yireo\NextGenImages\Image\ImageCollector;
use Yireo\NextGenImages\Image\ImageFactory;

class HtmlReplacer
{
    private const MARKER_CODE = 'data-marker';
    
    /**
     * @var UrlConvertor
     */
    private $urlConvertor;
    
    /**
     * @var ImageCollector
     */
    private $imageCollector;
    
    /**
     * @var PictureFactory
     */
    private $pictureFactory;
    
    /**
     * @var ImageFactory
     */
    private $imageFactory;
    
    /**
     * Constructor.
     *
     * @param UrlConvertor $urlConvertor
     * @param ImageCollector $imageCollector
     * @param PictureFactory $pictureFactory
     * @param ImageFactory $imageFactory
     */
    public function __construct(
        UrlConvertor $urlConvertor,
        ImageCollector $imageCollector,
        PictureFactory $pictureFactory,
        ImageFactory $imageFactory
    ) {
        $this->urlConvertor = $urlConvertor;
        $this->imageCollector = $imageCollector;
        $this->pictureFactory = $pictureFactory;
        $this->imageFactory = $imageFactory;
    }
    
    /**
     * @param string $html
     * @return string
     */
    public function replace(string $html): string
    {
        $html = $this->addImageMarkersToHtml($html);
        $document = $this->htmlToDOMDocument($html);
        
        $images = $document->getElementsByTagName('img');
        foreach ($images as $image) {
            $imageMarker = $image->getAttribute(self::MARKER_CODE);
            if (empty($imageMarker)) {
                continue;
            }
            
            if (!$this->isAllowedByParentNode($image)) {
                continue;
            }
            
            $imageUrl = $image->getAttribute('src');
            if (!$this->isAllowedByImageUrl($imageUrl)) {
                continue;
            }
            
            $images = $this->imageCollector->collect($imageUrl);
            if (!count($images) > 0) {
                continue;
            }
            
            if (!preg_match(
                '/<img ' . self::MARKER_CODE . '="' . $imageMarker . '"([^\>]+)>/',
                $html,
                $imageHtmlMatch
            )) {
                continue;
            }
            
            $imageHtml = $imageHtmlMatch[0];
            $pictureBlock = $this->pictureFactory->create(
                $this->imageFactory->createFromUrl($imageUrl),
                $images,
                $imageHtml,
                (bool)$image->getAttribute('data-src')
            );
            
            $html = str_replace($imageHtml, $pictureBlock->toHtml(), $html);
        }
        
        $html = $this->removeImageMarker($html);
        return $html;
    }
    
    /**
     * @param string $html
     * @return string
     */
    private function addImageMarkersToHtml(string $html): string
    {
        if (preg_match_all('/<img([^\>]+)>/mi', $html, $imgMatches)) {
            $i = 1;
            foreach ($imgMatches[0] as $imgMatch) {
                $newTag = str_replace('<img ', '<img ' . self::MARKER_CODE . '="' . $i . '" ', $imgMatch);
                $html = str_replace($imgMatch, $newTag, $html);
                $i++;
            }
        }
        
        return $html;
    }
    
    private function removeImageMarker(string $html): string
    {
        return preg_replace('/ ' . self::MARKER_CODE . '="([^\"]+)"/', '', $html);
    }
    
    private function htmlToDOMDocument(string $html): DOMDocument
    {
        $document = new DOMDocument();
        libxml_use_internal_errors(true);
        $document->loadHTML(
            mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'),
            LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED
        );
        
        libxml_clear_errors();
        libxml_use_internal_errors(false);
        $document->encoding = 'utf-8';
        return $document;
    }
    
    /**
     * @param DOMElement $node
     * @return bool
     */
    private function isAllowedByParentNode(DOMElement $node): bool
    {
        $parentNode = $node->parentNode;
        /** @phpstan-ignore-next-line */
        return !in_array($parentNode->tagName, ['picture', 'source']);
    }
    
    /**
     * @param string $imageUrl
     * @return bool
     */
    private function isAllowedByImageUrl(string $imageUrl): bool
    {
        if (preg_match('/^data:/', $imageUrl)) {
            return false;
        }
        
        if (!$this->urlConvertor->isLocal($imageUrl)) {
            return false;
        }
        
        if (strpos($imageUrl, '/media/captcha/') !== false) {
            return false;
        }
        
        return true;
    }
}
