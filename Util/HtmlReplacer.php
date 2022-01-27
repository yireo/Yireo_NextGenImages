<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Util;

use Yireo\NextGenImages\Block\PictureFactory;
use Yireo\NextGenImages\Image\ImageCollector;

class HtmlReplacer
{
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
     * Constructor.
     *
     * @param UrlConvertor $urlConvertor
     * @param ImageCollector $imageCollector
     * @param PictureFactory $pictureFactory
     */
    public function __construct(
        UrlConvertor $urlConvertor,
        ImageCollector $imageCollector,
        PictureFactory $pictureFactory
    ) {
        $this->urlConvertor = $urlConvertor;
        $this->imageCollector = $imageCollector;
        $this->pictureFactory = $pictureFactory;
    }

    /**
     * @param string $html
     * @return string
     */
    public function replace(string $html): string
    {
        $regex = '/<([^<]+)\ (data\-src|src)=\"([^\"]+)\.(png|jpg|jpeg)([^>]+)>(\s*)<(\/?)([a-z]+)/msi';
        if (preg_match_all($regex, $html, $matches) === false) {
            return $html;
        }

        foreach ($matches[0] as $index => $match) {
            $nextTag = $matches[7][$index] . $matches[8][$index];
            $fullSearchMatch = $matches[0][$index];
            $imageUrl = $matches[3][$index] . '.' . $matches[4][$index];

            if (!$this->isAllowedByNextTag($nextTag)) {
                continue;
            }

            if (!$this->isAllowedByImageUrl($imageUrl)) {
                continue;
            }

            $images = $this->imageCollector->collect($imageUrl);
            if (empty($images)) {
                continue;
            }

            $isDataSrc = $matches[2][$index] === 'data-src';
            $htmlTag = preg_replace('/>(.*)/msi', '>', $fullSearchMatch);
            $pictureBlock = $this->pictureFactory->create($imageUrl, $images, $htmlTag, $isDataSrc);
            $replacement = $pictureBlock->toHtml() . '<' . $nextTag;
            $html = str_replace($fullSearchMatch, $replacement, $html);
        }

        return $html;
    }

    /**
     * @param string $nextTag
     * @return bool
     */
    private function isAllowedByNextTag(string $nextTag): bool
    {
        if ($nextTag === '/picture' || $nextTag === '/source') {
            return false;
        }

        return true;
    }

    /**
     * @param string $imageUrl
     * @return bool
     */
    private function isAllowedByImageUrl(string $imageUrl): bool
    {
        if (!$this->urlConvertor->isLocal($imageUrl)) {
            return false;
        }

        if (strpos($imageUrl, '/media/captcha/') !== false) {
            return false;
        }

        return true;
    }
}
