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
        $targetPath = preg_replace('/\.(jpg|jpeg|png)$/', '.' . $suffix, $image->getPath());

        if ($this->config->getTargetDirectory() === TargetDirectory::CACHE) {
            $mediaDirectory = $this->directoryList->getPath(DirectoryList::MEDIA);
            $cacheDirectory = $mediaDirectory . '/nextgenimages/';

            $targetPath = $cacheDirectory . basename($targetPath);
            return $this->imageFactory->createFromPath($targetPath);
        }

        return $this->imageFactory->createFromPath($targetPath);
    }
}
