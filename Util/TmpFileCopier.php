<?php

declare(strict_types=1);

namespace Yireo\NextGenImages\Util;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\Directory\TargetDirectory;
use Magento\RemoteStorage\Model\Config as RemoteStorageConfig;
use Psr\Log\LoggerInterface;

class TmpFileCopier
{
    private WriteInterface $tmpDirectoryWrite;
    private WriteInterface $remoteDirectoryWrite;
    private Filesystem $filesystem;
    private TargetDirectory $targetDirectory;
    private RemoteStorageConfig $remoteStorageConfig;
    private LoggerInterface $logger;

    /**
     * Map of remote path => local tmp absolute path.
     *
     * @var array<string,string>
     */
    private array $tmpFiles = [];

    public function __construct(
        Filesystem $filesystem,
        TargetDirectory $targetDirectory,
        RemoteStorageConfig $remoteStorageConfig,
        LoggerInterface $logger
    ) {
        $this->filesystem = $filesystem;
        $this->targetDirectory = $targetDirectory;
        $this->remoteStorageConfig = $remoteStorageConfig;
        $this->logger = $logger;
        $this->tmpDirectoryWrite = $this->filesystem->getDirectoryWrite(DirectoryList::TMP);
        $this->remoteDirectoryWrite = $this->targetDirectory->getDirectoryWrite(DirectoryList::ROOT);
    }

    /**
     * Removes created tmp files
     */
    public function __destruct()
    {
        try {
            foreach ($this->tmpFiles as $key => $tmpFile) {
                $this->tmpDirectoryWrite->delete($tmpFile);
                unset($this->tmpFiles[$key]);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    public function copy(string $filePath): string
    {
        if ($this->remoteStorageConfig->isEnabled() === false) {
            return $filePath;
        }

        if (isset($this->tmpFiles[$filePath])) {
            return $this->tmpFiles[$filePath];
        }

        $absolutePath = $this->remoteDirectoryWrite->getAbsolutePath($filePath);

        if ($this->remoteDirectoryWrite->isFile($absolutePath)) {
            $tmpPath = $this->tmpDirectoryWrite->getAbsolutePath($filePath);

            $parentDir = $this->tmpDirectoryWrite->getDriver()->getParentDirectory($tmpPath);
            $this->tmpDirectoryWrite->create($parentDir);

            $content = $this->remoteDirectoryWrite->getDriver()->fileGetContents($filePath);

            if ($this->tmpDirectoryWrite->getDriver()->filePutContents($tmpPath, $content) >= 0) {
                $filePath = $tmpPath;
                $this->tmpFiles[$tmpPath] = $tmpPath;
            }
        }

        return $filePath;
    }
}
