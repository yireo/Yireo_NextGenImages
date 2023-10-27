<?php
declare(strict_types=1);

namespace Yireo\NextGenImages\Util;

use DOMElement;
use Yireo\NextGenImages\Block\PictureFactory;
use Yireo\NextGenImages\Config\Config;
use Yireo\NextGenImages\Convertor\ConvertorListing;
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
     * @var Config
     */
    private $config;

    /**
     * @var ConvertorListing
     */
    private $convertorListing;

    /**
     * @var DomUtils
     */
    private $domUtils;

    /**
     * Constructor.
     *
     * @param UrlConvertor $urlConvertor
     * @param ImageCollector $imageCollector
     * @param PictureFactory $pictureFactory
     * @param ImageFactory $imageFactory
     * @param Config $config
     * @param ConvertorListing $convertorListing
     * @param DomUtils $domUtils
     */
    public function __construct(
        UrlConvertor   $urlConvertor,
        ImageCollector $imageCollector,
        PictureFactory $pictureFactory,
        ImageFactory   $imageFactory,
        Config $config,
        ConvertorListing $convertorListing,
        DomUtils $domUtils
    ) {
        $this->urlConvertor = $urlConvertor;
        $this->imageCollector = $imageCollector;
        $this->pictureFactory = $pictureFactory;
        $this->imageFactory = $imageFactory;
        $this->config = $config;
        $this->convertorListing = $convertorListing;
        $this->domUtils = $domUtils;
    }

    /**
     * @param string $html
     * @return string
     */
    public function replace(string $html): string
    {
        $html = $this->replaceInlineCssBackgroundImages($html);
        $html = $this->addImageMarkersToHtml($html);
        $html = $this->replaceImageTags($html);
        return $this->removeImageMarker($html);
    }

    /**
     * @param string $html
     * @return string
     */
    private function replaceImageTags(string $html): string
    {
        $document = $this->domUtils->htmlToDOMDocument($html);
        $images = $document->getElementsByTagName('img');
        foreach ($images as $image) {
            $imageHtml = $this->getImageHtmlFromImage($image, $html);
            $pictureHtml = $this->getPictureHtmlFromImage($image, $html);
            if (!empty($pictureHtml)) {
                $html = str_replace($imageHtml, $pictureHtml, $html);
            }
        }

        return $html;
    }

    /**
     * @param DOMElement $image
     * @param string $html
     * @return string
     */
    private function getImageHtmlFromImage(DOMElement $image, string $html): string
    {
        $imageMarker = $image->getAttribute(self::MARKER_CODE);
        if (empty($imageMarker)) {
            return '';
        }

        $regex = '/<img ' . self::MARKER_CODE . '="' . $imageMarker . '"([^\>]+)>/';
        if (!preg_match($regex, $html, $imageHtmlMatch)) {
            return '';
        }

        return $imageHtmlMatch[0];
    }

    /**
     * @param DOMElement $image
     * @param string $html
     * @return string
     */
    private function getPictureHtmlFromImage(DOMElement $image, string $html): string
    {
        $imageMarker = $image->getAttribute(self::MARKER_CODE);
        if (empty($imageMarker)) {
            return '';
        }

        if (!$this->isAllowedByParentNode($image)) {
            return '';
        }

        $imageUrl = $this->getImageUrlFromElement($image);
        if (!$this->isAllowedByImageUrl($imageUrl)) {
            return '';
        }

        $images = $this->imageCollector->collect($imageUrl);
        if (!count($images) > 0) {
            return '';
        }

        $imageHtml = $this->getImageHtmlFromImage($image, $html);
        $pictureBlock = $this->pictureFactory->create(
            $this->imageFactory->createFromUrl($imageUrl),
            $images,
            $imageHtml,
            $this->getSrcAttributeFromElement($image)
        );

        return $pictureBlock->toHtml();
    }

    /**
     * @param string $html
     * @return string
     */
    private function addImageMarkersToHtml(string $html): string
    {
        $i = 0;
        $html = preg_replace_callback(
            "/<img([^\>]+)>/mi",
            function ($matches) use (&$i) {
                $i += 1;
                return str_replace('<img', '<img ' . self::MARKER_CODE . '="' . $i . '"', $matches[0]);
            },
            $html);

        return $html;
    }

    /**
     * @param string $html
     * @return string
     */
    private function removeImageMarker(string $html): string
    {
        return preg_replace('/ ' . self::MARKER_CODE . '="([^\"]+)"/', '', $html);
    }

    /**
     * @param DOMElement $node
     * @return bool
     */
    private function isAllowedByParentNode(DOMElement $node): bool
    {
        $parentNode = $node->parentNode;
        if (empty($parentNode) || empty($parentNode->tagName)) {
            return false;
        }

        /** @phpstan-ignore-next-line */
        return !in_array($parentNode->tagName, ['picture', 'source']);
    }

    /**
     * @param string $imageUrl
     * @return bool
     */
    private function isAllowedByImageUrl(string $imageUrl): bool
    {
        if (empty($imageUrl)) {
            return false;
        }

        if (!preg_match('/\.(jpg|jpeg|png)$/', $imageUrl)) {
            return false;
        }

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

    /**
     * @param string $html
     * @return string
     */
    private function replaceInlineCssBackgroundImages(string $html): string
    {
        if (false === $this->config->convertCssBackgrounds()) {
            return $html;
        }

        //$regex = '/{[^}{]*background(-image)?:\s*url\(\s*[\'"]?(https?:\/\/[^")]+\.(png|jpg|jpeg))[\'"]?\s*\)[^}{]}/msi';
        $regex = '/background(-image)?:\s*url\(\s*[\'"]?(https?:\/\/[^")]+\.(png|jpg|jpeg))[\'"]?\s*\)/msi';
        if (preg_match_all($regex, $html, $matches) === false) {
            return $html;
        }

        foreach ($matches[2] as $imageUrl) {
            if (!$this->isAllowedByImageUrl($imageUrl)) {
                continue;
            }

            $sourceImages = $this->imageCollector->collect($imageUrl);

            if (empty($sourceImages)) {
                continue;
            }

            if (isset($sourceImages[0])) {
                $html = str_replace($imageUrl, $sourceImages[0]->getUrl(), $html);
            }
        }

        return $html;
    }

    /**
     * @param DOMElement $image
     * @return string
     */
    private function getImageUrlFromElement(DOMElement $image): string
    {
        $attributes = $this->getAllowedSrcAttributes();
        foreach ($attributes as $attribute) {
            $imageUrl = $image->getAttribute($attribute);
            if (!empty($imageUrl)) {
                break;
            }
        }

        if (!$this->isAllowedByImageUrl($imageUrl)) {
            return '';
        }

        return $imageUrl;
    }

    /**
     * @param DOMElement $image
     * @return string
     */
    private function getSrcAttributeFromElement(DOMElement $image): string
    {
        $attributes = $this->getAllowedSrcAttributes();
        foreach ($attributes as $attribute) {
            $imageUrl = $image->getAttribute($attribute);
            if (!empty($imageUrl)) {
                return $attribute;
            }
        }

        return 'src';
    }

    /**
     * @return string[]
     */
    private function getAllowedSrcAttributes(): array
    {
        return [
            'src',
            'data-src',
            ':src',
            ':data-src'
        ];
    }
}
