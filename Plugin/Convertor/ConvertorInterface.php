<?php

declare(strict_types=1);

namespace Yireo\NextGenImages\Plugin\Convertor;

use Yireo\NextGenImages\Convertor\ConvertorInterface as BaseConvertorInterface;
use Yireo\NextGenImages\Image\Image;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\TargetDirectory;
use Yireo\NextGenImages\Image\TargetImageFactory;
use Yireo\NextGenImages\Util\File;
use Magento\RemoteStorage\Model\Config as RemoteStorageConfig;
use Yireo\NextGenImages\Util\TmpFileCopier;

class ConvertorInterface
{
    private Filesystem\Directory\WriteInterface $tmpDirectoryWrite;
    private Filesystem\Directory\WriteInterface $remoteDirectoryWrite;

    private RemoteStorageConfig $remoteStorageConfig;

    private TargetImageFactory $targetImageFactory;

    private File $fileUtil;

    private TmpFileCopier $tmpFileCopier;

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
        array $formats = []
    ) {
        $this->tmpDirectoryWrite = $filesystem->getDirectoryWrite(DirectoryList::TMP);
        $this->remoteDirectoryWrite = $targetDirectory->getDirectoryWrite(DirectoryList::ROOT);
        $this->remoteStorageConfig = $remoteStorageConfig;
        $this->targetImageFactory = $targetImageFactory;
        $this->fileUtil = $fileUtil;
        $this->tmpFileCopier = $tmpFileCopier;
        $this->formats = $formats;
    }

    public function aroundConvertImage(BaseConvertorInterface $subject, callable $proceed, Image $image): Image
    {
        if ($this->remoteStorageConfig->isEnabled() === false) {
            return $proceed($image);
        }

        $targetImage = $this->getUpToDateTargetImage($subject, $image);
        if ($targetImage instanceof Image) {
            return $targetImage;
        }

        $relativePath = $this->tmpDirectoryWrite->getRelativePath($image->getPath());
        $this->tmpFileCopier->copy($relativePath);

        $resultImage = $proceed($image);

        $tmpAbsolutePath = $resultImage->getPath();

        $tmpRelativePath = $this->tmpDirectoryWrite->getRelativePath($tmpAbsolutePath);

        $remoteAbsolutePath = $this->remoteDirectoryWrite->getAbsolutePath($tmpRelativePath);

        $this->moveToRemote($tmpAbsolutePath, $remoteAbsolutePath);

        return $resultImage;
    }

    /**
     * Skip downloading the source image when the target image on remote storage is up to date
     */
    private function getUpToDateTargetImage(BaseConvertorInterface $subject, Image $image): ?Image
    {
        $format = $this->getFormat($subject);
        if ($format === '') {
            return null;
        }

        if (!$this->fileUtil->fileExists($image->getPath())) {
            return null;
        }

        $targetImage = $this->targetImageFactory->create($image, $format);
        if ($this->fileUtil->needsConversion($image->getPath(), $targetImage->getPath())) {
            return null;
        }

        return $targetImage;
    }

    private function getFormat(BaseConvertorInterface $subject): string
    {
        if (method_exists($subject, 'getFormat')) {
            return (string)$subject->getFormat();
        }

        foreach ($this->formats as $className => $format) {
            if (is_a($subject, $className)) {
                return (string)$format;
            }
        }

        return '';
    }

    private function moveToRemote(string $localFile, string $remotePath): void
    {
        if ($this->tmpDirectoryWrite->getDriver()->isExists($localFile) === false) {
            return;
        }

        $this->tmpDirectoryWrite->getDriver()->rename(
            $localFile,
            $remotePath,
            $this->remoteDirectoryWrite->getDriver()
        );
    }
}
