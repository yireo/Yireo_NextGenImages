<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Util;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\View\Asset\File\NotFoundException;
use Yireo\NextGenImages\Exception\ConvertorException;
use Yireo\NextGenImages\Image\ImageFactory;
use Yireo\NextGenImages\Image\TargetImageFactory;
use Yireo\NextGenImages\Logger\Debugger;

class File
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var DriverInterface
     */
    private $fileDriver;

    /**
     * @var Debugger
     */
    private $debugger;

    /**
     * @var UrlConvertor
     */
    private $urlConvertor;

    /**
     * @var TargetImageFactory
     */
    private $targetImageFactory;

    /**
     * @var ImageFactory
     */
    private $imageFactory;

    /**
     * File constructor.
     *
     * @param DirectoryList $directoryList
     * @param Filesystem $filesystem
     * @param Debugger $debugger
     * @param UrlConvertor $urlConvertor
     */
    public function __construct(
        DirectoryList $directoryList,
        Filesystem $filesystem,
        Debugger $debugger,
        UrlConvertor $urlConvertor,
        TargetImageFactory $targetImageFactory,
        ImageFactory $imageFactory
    ) {
        $this->directoryList = $directoryList;
        $this->fileDriver = $filesystem->getDirectoryWrite(DirectoryList::PUB)->getDriver();
        $this->debugger = $debugger;
        $this->urlConvertor = $urlConvertor;
        $this->targetImageFactory = $targetImageFactory;
        $this->imageFactory = $imageFactory;
    }

    /**
     * @param string $uri
     *
     * @return string
     * @throws ConvertorException
     */
    public function resolve(string $uri): string
    {
        if ($this->fileExists($uri)) {
            return $uri;
        }

        try {
            return $this->urlConvertor->getFilenameFromUrl($uri);
        } catch (NotFoundException $e) {
            throw new ConvertorException($e->getMessage());
        }
    }

    /**
     * @param string $uri
     * @return bool
     * @throws ConvertorException
     */
    public function uriExists(string $uri): bool
    {
        $filePath = $this->resolve($uri);
        if ($this->fileExists($filePath)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $filePath
     * @return bool
     */
    public function fileExists(string $filePath): bool
    {
        try {
            return $this->fileDriver->isExists($filePath);
        } catch (FileSystemException $fileSystemException) {
            return false;
        }
    }

    /**
     * @param string $filePath
     * @return bool
     * @throws FileSystemException
     */
    public function isWritable(string $filePath): bool
    {
        if ($this->fileExists($filePath)) {
            return $this->fileDriver->isWritable($filePath);
        }

        return $this->fileDriver->isWritable($this->fileDriver->getParentDirectory($filePath));
    }

    /**
     * @param string $sourceFilename
     * @param string $destinationSuffix
     * @return string
     * @throws FileSystemException
     * @deprecated Use TargetImageFactory::get() directly
     */
    public function convertSuffix(string $sourceFilename, string $destinationSuffix): string
    {
        $image = $this->imageFactory->createFromPath($sourceFilename);
        return $this->targetImageFactory->get($image, $destinationSuffix)->getPath();
    }

    /**
     * @param string $filePath
     *
     * @return int
     */
    public function getModificationTime(string $filePath): int
    {
        try {
            $stat = $this->fileDriver->stat($filePath);
            if (!empty($stat['mtime'])) {
                return (int)$stat['mtime'];
            }

            if (!empty($stat['ctime'])) {
                return (int)$stat['ctime'];
            }

            return 0;
        } catch (FileSystemException $e) {
            $this->debugger->debug($e->getMessage(), ['filePath' => $filePath]);
            return 0;
        }
    }

    /**
     * @param string $targetFile
     * @param string $comparisonFile
     *
     * @return bool
     */
    public function isNewerThan(string $targetFile, string $comparisonFile): bool
    {
        if (!$this->fileExists($targetFile)) {
            return false;
        }

        $targetFileModificationTime = $this->getModificationTime($targetFile);
        if ($targetFileModificationTime === 0) {
            return false;
        }

        $comparisonFileModificationTime = $this->getModificationTime($comparisonFile);
        if ($comparisonFileModificationTime === 0) {
            return true;
        }

        if ($targetFileModificationTime > $comparisonFileModificationTime) {
            return true;
        }

        return false;
    }

    /**
     * @param string $sourceImageFilename
     * @param string $destinationImageFilename
     * @return bool
     * @throws NotFoundException
     */
    public function needsConversion(string $sourceImageFilename, string $destinationImageFilename): bool
    {
        if ($this->fileExists($sourceImageFilename) === false) {
            return false;
        }

        if ($this->fileExists($destinationImageFilename)
            && $this->isNewerThan($destinationImageFilename, $sourceImageFilename)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $uri
     * @return bool
     * @throws ConvertorException
     * @deprecated Use uriExists($uri) instead
     */
    public function urlExists(string $uri): bool
    {
        return $this->uriExists($uri);
    }
}
