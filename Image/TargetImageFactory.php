<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Image;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Yireo\NextGenImages\Config\Config;
use Yireo\NextGenImages\Config\Source\TargetDirectory;

class TargetImageFactory
{
    /**
     * @var DirectoryList
     */
    private $directoryList;
    
    /**
     * @var Config
     */
    private $config;
    
    /**
     * @var ImageFactory
     */
    private $imageFactory;
    
    /**
     * @param DirectoryList $directoryList
     * @param Config $config
     * @param ImageFactory $imageFactory
     */
    public function __construct(
        DirectoryList $directoryList,
        Config $config,
        ImageFactory $imageFactory
    ) {
        $this->directoryList = $directoryList;
        $this->config = $config;
        $this->imageFactory = $imageFactory;
    }
    
    /**
     * @param Image $image
     * @param string $suffix
     * @return Image
     * @throws FileSystemException
     */
    public function create(Image $image, string $suffix): Image
    {
        $folder = $this->getTargetPathFromImage($image);
        $filename = $this->getTargetFilename($image, $suffix);
        return $this->imageFactory->createFromPath($folder . '/' . $filename);
    }
    
    /**
     * @param Image $image
     * @param string $suffix
     * @return string
     */
    private function getTargetFilename(Image $image, string $suffix): string
    {
        // phpcs:ignore
        $filename = basename($image->getPath());
        $path = preg_replace('/\.(jpg|jpeg|png)$/', '', $filename);
        return $path . $this->getTargetHash($image) . '.' . $suffix;
    }
    
    /**
     * @param Image $image
     * @return string
     */
    private function getTargetHash(Image $image): string
    {
        if ($this->config->addHash() === false) {
            return '';
        }
        
        return '-' . hash('crc32', $image->getPath());
    }
    
    /**
     * @param Image $image
     * @return string
     * @throws FileSystemException
     */
    private function getTargetPathFromImage(Image $image): string
    {
        if ($this->config->getTargetDirectory() === TargetDirectory::CACHE) {
            $mediaDirectory = $this->directoryList->getPath(DirectoryList::MEDIA);
            return $mediaDirectory . '/nextgenimages/';
        }
        
        // phpcs:ignore
        return dirname($image->getPath());
    }
}
