<?php

declare(strict_types=1);

namespace Yireo\NextGenImages\Plugin\Util;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\TargetDirectory;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\RemoteStorage\Model\Config as RemoteStorageConfig;
use Yireo\NextGenImages\Util\File as BaseFile;

class File
{
    private ReadInterface $tmpDirectoryRead;

    private ReadInterface $remoteDirectoryRead;

    private RemoteStorageConfig $remoteStorageConfig;

    public function __construct(
        RemoteStorageConfig $remoteStorageConfig,
        TargetDirectory $targetDirectory,
        Filesystem $filesystem
    ) {
        $this->remoteStorageConfig = $remoteStorageConfig;
        $this->tmpDirectoryRead = $filesystem->getDirectoryRead(DirectoryList::TMP);
        $this->remoteDirectoryRead = $targetDirectory->getDirectoryRead(DirectoryList::ROOT);
    }

    public function aroundFileExists(
        BaseFile $subject,
        callable $proceed,
        string $filePath
    ): bool {
        if (!$this->remoteStorageConfig->isEnabled()) {
            return $proceed($filePath);
        }

        try {
            $relativePath = $this->tmpDirectoryRead->getRelativePath($filePath);
            if ($this->remoteDirectoryRead->isExist($relativePath)) {
                return true;
            }
        } catch (FileSystemException $fileSystemException) {
        }

        // A freshly converted image only exists locally until it is moved to the remote storage
        return $proceed($filePath);
    }

    public function aroundGetModificationTime(BaseFile $subject, callable $proceed, string $filePath): int
    {
        if (!$this->remoteStorageConfig->isEnabled()) {
            return $proceed($filePath);
        }

        try {
            $tmpRelativePath = $this->tmpDirectoryRead->getRelativePath($filePath);
            $stat = $this->remoteDirectoryRead->stat($tmpRelativePath);
        } catch (FileSystemException $fileSystemException) {
            return 0;
        }

        if (!empty($stat['mtime'])) {
            return (int)$stat['mtime'];
        }

        return (int)($stat['ctime'] ?? 0);
    }
}
