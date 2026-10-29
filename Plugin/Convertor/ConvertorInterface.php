<?php

declare(strict_types=1);

namespace Yireo\NextGenImages\Plugin\Convertor;

use Yireo\NextGenImages\Convertor\ConvertorInterface as BaseConvertorInterface;
use Yireo\NextGenImages\Image\Image;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\TargetDirectory;
use Yireo\NextGenImages\Image\TargetImageFactory;
use Yireo\NextGenImages\Logger\Debugger;
use Yireo\NextGenImages\Util\File;
use Magento\RemoteStorage\Model\Config as RemoteStorageConfig;
use Yireo\NextGenImages\Util\TmpFileCopier;
use Yireo\NextGenImages\Util\UrlConvertor;
use Yireo\NextGenImages\Image\ImageFactory;
use Magento\Framework\View\Asset\File\NotFoundException;

class ConvertorInterface
{
    private Filesystem\Directory\WriteInterface $tmpDirectoryWrite;
    private Filesystem\Directory\WriteInterface $remoteDirectoryWrite;

    private RemoteStorageConfig $remoteStorageConfig;

    private TargetImageFactory $targetImageFactory;

    private File $fileUtil;

    private TmpFileCopier $tmpFileCopier;

    private UrlConvertor $urlConvertor;

    private ImageFactory $imageFactory;

    private Debugger $debugger;

    /**
     * Map of convertor class => target format (like "webp"), for convertors without a getFormat() method
     *
     * @var array<string,string>
     */
    private array $formats;

    public function __construct(
        RemoteStorageConfig $remoteStorageConfig,
        Filesystem $filesystem,
        TargetDirectory $targetDirectory,
        TargetImageFactory $targetImageFactory,
        File $fileUtil,
        TmpFileCopier $tmpFileCopier,
        UrlConvertor $urlConvertor,
        ImageFactory $imageFactory,
        Debugger $debugger,
        array $formats = []
    ) {
        $this->tmpDirectoryWrite = $filesystem->getDirectoryWrite(DirectoryList::TMP);
        $this->remoteDirectoryWrite = $targetDirectory->getDirectoryWrite(DirectoryList::ROOT);
        $this->remoteStorageConfig = $remoteStorageConfig;
        $this->targetImageFactory = $targetImageFactory;
        $this->fileUtil = $fileUtil;
        $this->tmpFileCopier = $tmpFileCopier;
        $this->urlConvertor = $urlConvertor;
        $this->imageFactory = $imageFactory;
        $this->debugger = $debugger;
        $this->formats = $formats;
    }

    public function aroundConvertImage(BaseConvertorInterface $subject, callable $proceed, Image $image): Image
    {
        if ($this->remoteStorageConfig->isEnabled() === false) {
            return $proceed($image);
        }

        $format = $this->getFormat($subject);
        if ($format === '') {
            // Without a target format it is impossible to know whether conversion is needed
            return $proceed($image);
        }

        $targetImage = $this->targetImageFactory->create($image, $format);
        $hasSrcSet = $image->getSrcSet() !== null;
        $imagesNeedingConversion = $this->getImagesNeedingConversion($image, $targetImage, $format);

        if (empty($imagesNeedingConversion)) {
            // Target image is up to date. If srcSet is present, call proceed() so the original convertor
            // can assign converted srcSet URLs to the target image
            return $hasSrcSet ? $proceed($image) : $targetImage;
        }

        // Copy only images that need conversion
        $this->copyImagesToTmp($imagesNeedingConversion);

        $resultImage = $proceed($image);

        $this->moveConvertedImagesToRemote($resultImage);

        return $resultImage;
    }

    private function getFormat(BaseConvertorInterface $subject): string
    {
        $format = '';

        if (method_exists($subject, 'getFormat')) {
            $format = (string)$subject->getFormat();
        }

        if ($format === '') {
            foreach ($this->formats as $className => $configuredFormat) {
                if (is_a($subject, $className)) {
                    $format = (string)$configuredFormat;
                    break;
                }
            }
        }

        return strtolower(ltrim(trim($format), '.'));
    }

    /**
     * Collect the paths of all images (main image plus srcset entries) that require conversion
     *
     * @param Image $image
     * @param Image $targetImage
     * @param string $format
     * @return array<int, string>
     */
    private function getImagesNeedingConversion(Image $image, Image $targetImage, string $format): array
    {
        $paths = [];

        if ($this->fileUtil->needsConversion($image->getPath(), $targetImage->getPath())) {
            $paths[] = $image->getPath();
        }

        $paths = array_merge(
            $paths,
            $this->getSrcSetImagesNeedingConversion($image, $format)
        );

        return array_values(array_unique($paths));
    }

    private function getSrcSetImagesNeedingConversion(Image $image, string $format): array
    {
        $imagesNeedingConversion = [];

        foreach ($this->mapSrcSetToPaths($image->getSrcSet()) as $filePath) {
            try {
                $srcSetImageObj = $this->imageFactory->createFromPath($filePath);
                $srcSetTargetImage = $this->targetImageFactory->create($srcSetImageObj, $format);
            } catch (NotFoundException|FileSystemException|ValidatorException $exception) {
                $this->debugger->debug($exception->getMessage(), ['path' => $filePath]);
                continue;
            }

            if ($this->fileUtil->needsConversion($filePath, $srcSetTargetImage->getPath())) {
                $imagesNeedingConversion[] = $filePath;
            }
        }

        return $imagesNeedingConversion;
    }
    
    private function mapSrcSetToPaths(?string $srcSet): array
    {
        if ($srcSet === null || $srcSet === '') {
            return [];
        }

        $paths = [];
        foreach ($this->parseSrcSet($srcSet) as $srcSetImage) {
            try {
                $paths[] = $this->urlConvertor->getFilenameFromUrl($srcSetImage['url']);
            } catch (NotFoundException|FileSystemException $exception) {
                $this->debugger->debug($exception->getMessage(), ['url' => $srcSetImage['url']]);
                continue;
            }
        }

        return $paths;
    }

    private function parseSrcSet(string $srcSet): array
    {
        $images = [];
        $parts = explode(',', $srcSet);

        foreach ($parts as $part) {
            $part = trim($part);
            $pieces = preg_split('/\s+/', $part, 2);
            $url = $pieces[0] ?? '';
            $descriptor = $pieces[1] ?? '';

            if ($url) {
                $images[] = [
                    'url' => $url,
                    'descriptor' => $descriptor
                ];
            }
        }

        return $images;
    }

    private function copyImagesToTmp(array $pathsNeedingConversion): void
    {
        foreach ($pathsNeedingConversion as $path) {
            try {
                $relativePath = $this->tmpDirectoryWrite->getRelativePath($path);
            } catch (FileSystemException|ValidatorException $exception) {
                $this->debugger->debug($exception->getMessage(), ['path' => $path]);
                continue;
            }

            $this->tmpFileCopier->copy($relativePath);
        }
    }

    private function moveConvertedImagesToRemote(Image $resultImage): void
    {
        $pathsToMove = [$resultImage->getPath()];

        // The converted variants already exist, so their srcset URLs point to the files to move as-is
        foreach ($this->mapSrcSetToPaths($resultImage->getSrcSet()) as $filePath) {
            $pathsToMove[] = $filePath;
        }

        foreach (array_unique($pathsToMove) as $tmpAbsolutePath) {
            $remoteAbsolutePath = $this->prepareRemotePath($tmpAbsolutePath);

            if ($remoteAbsolutePath !== null) {
                $this->moveToRemote($tmpAbsolutePath, $remoteAbsolutePath);
            }
        }
    }

    private function prepareRemotePath(string $tmpAbsolutePath): ?string
    {
        try {
            $tmpRelativePath = $this->tmpDirectoryWrite->getRelativePath($tmpAbsolutePath);
            return $this->remoteDirectoryWrite->getAbsolutePath($tmpRelativePath);
        } catch (FileSystemException|ValidatorException $exception) {
            // This throws when the file exists in the local Magento root instead of in var/tmp
            $this->debugger->debug($exception->getMessage(), ['file' => $tmpAbsolutePath]);
            return null;
        }
    }

    private function moveToRemote(string $localFile, string $remotePath): void
    {
        try {
            if ($this->tmpDirectoryWrite->getDriver()->isExists($localFile) === false) {
                return;
            }

            $this->tmpDirectoryWrite->getDriver()->rename(
                $localFile,
                $remotePath,
                $this->remoteDirectoryWrite->getDriver()
            );
        } catch (FileSystemException $fileSystemException) {
            $this->debugger->debug($fileSystemException->getMessage(), ['file' => $localFile]);
        }
    }
}
