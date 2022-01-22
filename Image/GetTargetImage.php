<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Image;

use Magento\Framework\App\Filesystem\DirectoryList;
use Yireo\NextGenImages\Config\Config;
use Yireo\NextGenImages\Config\Source\TargetDirectory;

class GetTargetImage
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
     * @param DirectoryList $directoryList
     * @param Config $config
     */
    public function __construct(
        DirectoryList $directoryList,
        Config $config
    ) {
        $this->directoryList = $directoryList;
        $this->config = $config;
    }

    public function get(Image $image, string $suffix): Image
    {
        $targetPath = preg_replace('/\.(jpg|jpeg|png)$/', '.' . $suffix, $image->getPath());

        if ($this->config->getTargetDirectory() === TargetDirectory::CACHE) {
            $mediaDirectory = $this->directoryList->getPath(DirectoryList::MEDIA);
            $cacheDirectory = $mediaDirectory . '/nextgenimages/';
            return new Image($cacheDirectory . '/'  . basename($targetPath));
        }

        return new Image($targetPath);
    }
}
