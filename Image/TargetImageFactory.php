<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Image;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\DriverInterface;
use Yireo\NextGenImages\Config\Config;
use Yireo\NextGenImages\Config\Source\TargetDirectory;
use Yireo\NextGenImages\Util\PathNormalizer;

class TargetImageFactory
{
    private $directoryList;
    private $config;
    private $imageFactory;
    private $fileDriver;

    /**
     * @param DirectoryList $directoryList
     * @param Config $config
     * @param ImageFactory $imageFactory
     */
    public function __construct(
        DirectoryList $directoryList,
        Config $config,
        ImageFactory $imageFactory,
        Filesystem $filesystem
    ) {
        $this->directoryList = $directoryList;
        $this->config = $config;
        $this->imageFactory = $imageFactory;
        $this->fileDriver = $filesystem->getDirectoryWrite(DirectoryList::PUB)->getDriver();
    }

    /**
     * @param Image $image
     * @param string $suffix
     * @return Image
     * @throws FileSystemException
     */
    public function create(Image $image, string $suffix): Image
    {
        $targetPath = $this->getTargetPathFromImage($image).'/'.$this->getTargetFilename($image, $suffix);
        if ($this->config->getTargetDirectory() === TargetDirectory::CACHE) {
            $targetPath = $this->assertInsideCacheDirectory($targetPath);
        }

        return $this->imageFactory->createFromPath($targetPath);
    }

    /**
     * @param string $targetPath
     * @return string
     * @throws FileSystemException
     */
    private function assertInsideCacheDirectory(string $targetPath): string
    {
        $normalizedTargetPath = PathNormalizer::normalize($targetPath);
        $cacheDirectory = rtrim(PathNormalizer::normalize($this->config->getCacheDirectoryPath()), '/');
        if (!str_starts_with($normalizedTargetPath, $cacheDirectory.'/')) {
            throw new FileSystemException(
                __('Target path "%1" is outside of the cache directory', $targetPath)
            );
        }

        return $normalizedTargetPath;
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

        return $path.$this->getTargetHash($image).'.'.$suffix;
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

        return '-'.hash('crc32', $image->getPath());
    }

    /**
     * @param Image $image
     * @return string
     * @throws FileSystemException
     */
    private function getTargetPathFromImage(Image $image): string
    {
        if ($this->config->getTargetDirectory() === TargetDirectory::CACHE) {
            $pathRoot = $this->fileDriver->getParentDirectory($image->getPath());
            return $this->config->getCacheDirectoryPath().$this->getRelativePath($pathRoot);
        }

        // phpcs:ignore
        return dirname($image->getPath());
    }

    /**
     * @param string $path
     * @return string
     */
    private function getRelativePath(string $path): string
    {
        $root = rtrim($this->directoryList->getRoot(), '/');
        if ($root !== '' && ($path === $root || str_starts_with($path, $root.'/'))) {
            $path = substr($path, strlen($root));
        }

        $path = ltrim($path, '/');
        if ($path === 'pub' || str_starts_with($path, 'pub/')) {
            $path = substr($path, 4);
        }

        return $path;
    }
}
