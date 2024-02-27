<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Block;

use DOMDocument;
use DOMNode;
use Magento\Framework\View\Element\Template;
use Yireo\NextGenImages\Image\Image;

class Picture extends Template
{
    /**
     * @var string
     */
    protected $_template = 'picture.phtml';

    /**
     * @var Image[]
     */
    private $images = [];

    /**
     * @var Image
     */
    private $originalImage;

    /**
     * @var string
     */
    private $title = '';

    /**
     * @var string
     */
    private $altText = '';

    /**
     * @var string
     */
    private $width = '';

    /**
     * @var string
     */
    private $height = '';

    /**
     * @var string
     */
    private $style = '';

    /**
     * @var string
     */
    private $originalTag = '';

    /**
     * @var bool
     */
    private $debug = false;

    /**
     * @var string
     */
    private $class = '';

    /**
     * @var bool
     */
    private $lazyLoading = true;

    /**
     * @var string
     */
    private $srcAttribute = 'src';

    /**
     * @return Image[]
     */
    public function getImages(): array
    {
        return $this->images;
    }

    /**
     * @param Image[] $images
     * @return Picture
     */
    public function setImages(array $images): Picture
    {
        $this->images = $images;

        return $this;
    }

    /**
     * @param Image $image
     * @return Picture
     */
    public function addImage(Image $image): Picture
    {
        $this->images[] = $image;

        return $this;
    }

    /**
     * @return Image
     */
    public function getOriginalImage(): Image
    {
        return $this->originalImage;
    }

    /**
     * @param Image $originalImage
     *
     * @return Picture
     */
    public function setOriginalImage(Image $originalImage): Picture
    {
        $this->originalImage = $originalImage;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return Picture
     */
    public function setTitle(string $title): Picture
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getAltText(): string
    {
        return $this->altText;
    }

    /**
     * @param string $altText
     *
     * @return Picture
     */
    public function setAltText(string $altText): Picture
    {
        $this->altText = $altText;

        return $this;
    }

    /**
     * @return string
     */
    public function getWidth(): string
    {
        return $this->width;
    }

    /**
     * @param string $width
     * @return Picture
     */
    public function setWidth(string $width): Picture
    {
        $this->width = $width;

        return $this;
    }

    /**
     * @return string
     */
    public function getHeight(): string
    {
        return $this->height;
    }

    /**
     * @param string $height
     * @return Picture
     */
    public function setHeight(string $height): Picture
    {
        $this->height = $height;

        return $this;
    }

    /**
     * @return string
     */
    public function getStyle(): string
    {
        return $this->style;
    }

    /**
     * @param string $style
     * @return Picture
     */
    public function setStyle(string $style): Picture
    {
        $this->style = $style;

        return $this;
    }

    /**
     * @return string
     */
    public function getOriginalTag(): string
    {
        return $this->originalTag;
    }

    /**
     * @param string $originalTag
     *
     * @return Picture
     */
    public function setOriginalTag(string $originalTag): Picture
    {
        $this->originalTag = $originalTag;

        return $this;
    }

    /**
     * @return string
     */
    public function getOriginalAttributesAsString(): string
    {
        return implode(' ', $this->getOriginalAttributes());
    }

    /**
     * @return string[]
     */
    public function getOriginalAttributes(): array
    {
        $attributes = [];
        $originalNode = $this->getDomElementFromHtmlTag($this->getOriginalTag());
        if (!$originalNode instanceof DOMNode) {
            return $attributes;
        }

        foreach ($originalNode->attributes as $attribute) {
            $name = $attribute->nodeName;
            if (in_array($name, ['img', 'data-src', 'src', ':data-src', ':src', 'data-srcset', 'srcset', ':srcset', ':data-srcset', 'class'])) {
                continue;
            }

            $value = $attribute->nodeValue;
            if (empty($value)) {
                continue;
            }

            $attributes[] = $name.'="'.$value.'"';
        }

        if (preg_match_all('/@([^=]+)=\"([^\"]+)\"/', $this->getOriginalTag(), $matches)) {
            foreach ($matches[0] as $match) {
                $attributes[] = $match;
            }
        }

        return $attributes;
    }

    /**
     * @return string
     */
    public function getOriginalImageType(): string
    {
        if (preg_match('/\.(jpg|jpeg)$/i', $this->getOriginalImage()->getUrl())) {
            return 'image/jpg';
        }

        if (preg_match('/\.(png)$/i', $this->getOriginalImage()->getUrl())) {
            return 'image/png';
        }

        return '';
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * @param bool $debug
     */
    public function setDebug(bool $debug): Picture
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @param string $class
     *
     * @return Picture
     */
    public function setClass(string $class): Picture
    {
        $this->class = $class;

        return $this;
    }

    /**
     * @return bool
     */
    public function getLazyLoading(): bool
    {
        if (stristr($this->getOriginalTag(), 'fetchpriority="high"')) {
            return false;
        }

        return $this->lazyLoading;
    }

    /**
     * @param bool $lazyLoading
     * @return $this
     */
    public function setLazyLoading(bool $lazyLoading): Picture
    {
        $this->lazyLoading = $lazyLoading;

        return $this;
    }

    /**
     * @return string
     */
    public function getSrcAttribute(): string
    {
        return $this->srcAttribute;
    }

    /**
     * @param string $srcAttribute
     * @return $this
     */
    public function setSrcAttribute(string $srcAttribute): Picture
    {
        $this->srcAttribute = $srcAttribute;

        return $this;
    }

    /**
     * @return string
     */
    public function toHtml()
    {
        return $this->stripWhitespaces((string)parent::toHtml());
    }

    /**
     * @param string $html
     * @return DomNode|null
     * @todo Migrate this to DomUtils
     */
    private function getDomElementFromHtmlTag(string $html): ?DomNode
    {
        $document = new DOMDocument();
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

        return $document->getElementsByTagName('*')->item(0);
    }

    private function stripWhitespaces(string $html): string
    {
        $html = preg_replace('/(\s+)</m', '<', $html);
        $html = preg_replace('/(\s+)>/m', '>', $html);
        $html = preg_replace('/(\s+)/m', ' ', $html);
        $html = str_replace("\n", "", $html);
        $html = trim($html);
        return $html;
    }
}
