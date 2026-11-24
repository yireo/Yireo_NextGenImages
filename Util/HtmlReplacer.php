<?php
declare(strict_types=1);

namespace Yireo\NextGenImages\Util;

use DOMElement;
use Yireo\NextGenImages\Block\PictureFactory;
use Yireo\NextGenImages\Config\Config;
use Yireo\NextGenImages\Image\ImageCollector;
use Yireo\NextGenImages\Image\ImageFactory;

class HtmlReplacer
{
    private const MARKER_CODE = 'data-yireo-ngi-marker';

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
     * @var DomUtils
     */
    private $domUtils;

    /**
     * @var string
     */
    private $markerPrefix;

    /**
     * Constructor.
     *
     * @param UrlConvertor $urlConvertor
     * @param ImageCollector $imageCollector
     * @param PictureFactory $pictureFactory
     * @param ImageFactory $imageFactory
     * @param Config $config
     * @param DomUtils $domUtils
     */
    public function __construct(
        UrlConvertor $urlConvertor,
        ImageCollector $imageCollector,
        PictureFactory $pictureFactory,
        ImageFactory $imageFactory,
        Config $config,
        DomUtils $domUtils
    ) {
        $this->urlConvertor = $urlConvertor;
        $this->imageCollector = $imageCollector;
        $this->pictureFactory = $pictureFactory;
        $this->imageFactory = $imageFactory;
        $this->config = $config;
        $this->domUtils = $domUtils;
        $this->markerPrefix = bin2hex(random_bytes(4));
    }

    /**
     * @param string $html
     * @return string
     */
    public function replace(string $html): string
    {
        $originalHtml = $html;
        $html = $this->replaceInlineCssBackgroundImages($html);
        $html = $this->addImageMarkersToHtml($html);
        if ($html === null) {
            return $originalHtml;
        }

        $html = $this->replaceImageTags($html);

        return $this->removeImageMarker($html) ?? $originalHtml;
    }

    /**
     * @param string $html
     * @return string
     */
    private function replaceImageTags(string $html): string
    {
        if (empty($html)) {
            return $html;
        }

        $html = '<yireo_nextgenimages>'.$html.'</yireo_nextgenimages>';

        $document = $this->domUtils->htmlToDOMDocument($html);
        $images = $document->getElementsByTagName('img');

        $replacements = [];
        foreach ($images as $image) {
            $imageHtml = $this->getImageHtmlFromImage($image, $html);
            if (empty($imageHtml)) {
                continue;
            }

            $pictureHtml = $this->getPictureHtmlFromImage($image, $html);
            if (!empty($pictureHtml)) {
                $replacements[$imageHtml] = $pictureHtml;
            }
        }
        $html = strtr($html, $replacements);

        $html = str_replace('<yireo_nextgenimages>', '', $html);
        $html = str_replace('</yireo_nextgenimages>', '', $html);

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

        // Quoted attribute values may contain a ">", like in Alpine.js attributes `@click="() => foo()"`
        $regex = '/<img '.self::MARKER_CODE.'="'.preg_quote($imageMarker, '/')
            .'"((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>/i';
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
        
        $imageSrcSet = $image->getAttribute('srcset');
        if ($imageSrcSet) {
            $imageUrls = $this->getImageUrlsFromSrcSet($imageSrcSet);
            if (empty($imageUrls)) {
                return '';
            }

            $imageUrl = (string)($imageUrls[0] ?? reset($imageUrls));
            $images = $this->imageCollector->collect($imageUrls);
            if (!count($images) > 0) {
                return '';
            }
        } else {
            $imageUrl = $this->getImageUrlFromElement($image);
            if (!$this->isAllowedByImageUrl($imageUrl)) {
                return '';
            }

            $images = $this->imageCollector->collect($imageUrl);
            if (!count($images) > 0) {
                return '';
            }
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
     * @param string $srcSet
     * @return string[]
     */
    private function getImageUrlsFromSrcSet(string $srcSet): array
    {
        $imageUrls = [];
        foreach (explode(',', $srcSet) as $srcSetImage) {
            $srcSetImage = trim($srcSetImage, " \f\n\r\t\v\x00");
            if ($srcSetImage === '') {
                continue;
            }

            $pieces = preg_split('/\s+/', $srcSetImage);
            if ($pieces === false || count($pieces) > 2) {
                continue;
            }

            if (!isset($pieces[1])) {
                $imageUrls[0] = $pieces[0];
                continue;
            }

            if (!preg_match(ImageFactory::SRCSET_DESCRIPTOR_REGEX, $pieces[1])) {
                continue;
            }

            $imageUrls[$pieces[1]] = $pieces[0];
        }

        return $imageUrls;
    }

    /**
     * @param string $html
     * @return string|null
     */
    private function addImageMarkersToHtml(string $html): ?string
    {
        $i = 0;

        return preg_replace_callback(
            "/<img((?:[^>\"']|\"[^\"]*\"|'[^']*')+)>/mi",
            function ($matches) use (&$i) {
                $i++;
                $marker = ' '.self::MARKER_CODE.'="'.$this->markerPrefix.'-'.$i.'"';

                return preg_replace('/^<img/i', '$0'.$marker, $matches[0], 1) ?? $matches[0];
            },
            $html
        );
    }

    /**
     * @param string $html
     * @return string|null
     */
    private function removeImageMarker(string $html): ?string
    {
        return preg_replace('/ '.self::MARKER_CODE.'="'.preg_quote($this->markerPrefix, '/').'-\d+"/', '', $html);
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

        if (!preg_match('/\.(jpg|jpeg|png)/i', $imageUrl)) {
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

        $imageUrl = '';
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
            ':data-src',
        ];
    }
}
