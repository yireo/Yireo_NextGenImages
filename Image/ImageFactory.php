<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Image;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Yireo\NextGenImages\Util\UrlConvertor;

class ImageFactory
{
    private ObjectManagerInterface $objectManager;

    private UrlConvertor $urlConvertor;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param UrlConvertor $urlConvertor
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        UrlConvertor $urlConvertor
    ) {
        $this->objectManager = $objectManager;
        $this->urlConvertor = $urlConvertor;
    }

    /**
     * @param string $path
     * @return Image
     * @throws NoSuchEntityException
     */
    public function createFromPath(string $path): Image
    {
        $url = $this->urlConvertor->getUrlFromFilename($path);
        return $this->objectManager->create(Image::class, ['path' => $path, 'url' => $url]);
    }

    /**
     * @param array|string $url
     * @return Image
     * @throws FileSystemException
     */
    public function createFromUrl($url): Image
    {
        $urls = is_array($url) ? $url : [$url];
        $baseUrl = $this->cleanUrl($urls[0] ?? '');
        $srcSet = $this->getSrcSet($urls);
        $path = $this->urlConvertor->getFilenameFromUrl($baseUrl);
        return $this->objectManager->create(Image::class, ['path' => $path, 'url' => $baseUrl, 'srcSet' => $srcSet]);
    }
    
    private function cleanUrl(string $url): string
    {
        if (strpos($url, 'http') !== false) {
            return explode('?', $url)[0];
        }
        
        return $url;
    }
    
    private function getSrcSet(array $urls): string
    {
        $srcSetPieces = [];
        foreach ($urls as $key => $url) {
            $srcSetPieces[] = $this->cleanUrl($url) . ($key !== 0 ? (' ' . $key) : '');
        }
        
        return implode(',', $srcSetPieces);
    }
}
