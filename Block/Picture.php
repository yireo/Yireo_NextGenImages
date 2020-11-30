<?php
declare(strict_types=1);

namespace Yireo\NextGenImages\Block;

use Magento\Framework\View\Element\Template;
use Yireo\NextGenImages\Image\SourceImage;

class Picture extends Template
{
    /**
     * @var string
     */
    protected $_template = 'picture.phtml';

    /**
     * @var SourceImage[]
     */
    private $sourceImages = [];

    /**
     * @var string
     */
    private $originalImage = '';

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
     * @return SourceImage[]
     */
    public function getSourceImages(): array
    {
        return $this->sourceImages;
    }

    /**
     * @param array $sourceImages
     * @return Picture
     */
    public function setSourceImages(array $sourceImages): Picture
    {
        $this->sourceImages = $sourceImages;
        return $this;
    }

    /**
     * @param SourceImage $alternateImage
     *
     * @return Picture
     */
    public function addSourceImage(SourceImage $sourceImage)
    {
        $this->sourceImages[] = $sourceImage;
        return $this;
    }

    /**
     * @return string
     */
    public function getOriginalImage(): string
    {
        return $this->originalImage;
    }

    /**
     * @param string $originalImage
     *
     * @return Picture
     */
    public function setOriginalImage(string $originalImage): Picture
    {
        $this->originalImage = $originalImage;
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
    public function getOriginalImageType(): string
    {
        if (preg_match('/\.(jpg|jpeg)$/i', $this->getOriginalImage())) {
            return 'image/jpg';
        }

        if (preg_match('/\.(png)$/i', $this->getOriginalImage())) {
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
    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
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
}
