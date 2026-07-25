<?php

declare(strict_types=1);

namespace Yireo\NextGenImages\Plugin\Convertor;

use Yireo\NextGenImages\Convertor\ConvertorInterface as BaseConvertorInterface;
use Yireo\NextGenImages\Image\Image;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\TargetDirectory;
use Filesystem\Directory\WriteInterface;
use Yireo\NextGenImages\Image\TargetImageFactory;
use Yireo\NextGenImages\Util\File;
use Magento\RemoteStorage\Model\Config as RemoteStorageConfig;

class ConvertorInterface
{
    private Filesystem\Directory\WriteInterface $tmpDirectoryWrite;
    private Filesystem\Directory\WriteInterface $remoteDirectoryWrite;

    private RemoteStorageConfig $remoteStorageConfig;

    private TargetImageFactory $targetImageFactory;

    private File $fileUtil;

    public function __construct(
        RemoteStorageConfig $remoteStorageConfig,
        Filesystem $filesystem,
        TargetDirectory $targetDirectory,
        TargetImageFactory $targetImageFactory,
        File $fileUtil,
        private readonly \Krombox\YireoNextGenImages\Model\TmpFileCopier $tmpFileCopier,
    ) {
        $this->tmpDirectoryWrite = $filesystem->getDirectoryWrite(DirectoryList::TMP);
        $this->remoteDirectoryWrite = $targetDirectory->getDirectoryWrite(DirectoryList::ROOT);
        $this->remoteStorageConfig = $remoteStorageConfig;
        $this->targetImageFactory = $targetImageFactory;
        $this->fileUtil = $fileUtil;
    }

    public function aroundConvertImage(BaseConvertorInterface $subject, callable $proceed, Image $image): Image
    {
        if ($this->remoteStorageConfig->isEnabled() === false) {
            return $proceed($image);
        }

        /* ATTENTION: Hardcoded format, I would add getFormat method to ConvertorInterface
        and then use $subject->getFormat();*/
        $format = 'webp'; // @TODO: Make this configurable.
        $targetImage = $this->targetImageFactory->create($image, $format);

        if (!$this->fileUtil->needsConversion($image->getPath(), $targetImage->getPath())) {
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
