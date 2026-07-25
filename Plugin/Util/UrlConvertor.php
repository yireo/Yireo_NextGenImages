<?php

declare(strict_types=1);

namespace Yireo\NextGenImages\Plugin\Util;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Exception\NotFoundException;
use Magento\RemoteStorage\Model\Config as RemoteStorageConfig;
use Yireo\NextGenImages\Util\UrlConvertor as BaseUrlConvertor;

class UrlConvertor
{
    /**
     * @var RemoteStorageConfig
     */
    private $remoteStorageConfig;

    /**
     * @var File
     */
    private $fileDriver;

    /**
     * @var string
     */
    private $basePath;

    /**
     * @var string
     */
    private $mediaPath;

    /**
     * @var string
     */
    private $staticPath;

    /**
     * @var string
     */
    private $tmpBasePath;

    /**
     * @var string
     */
    private $tmpMediaPath;

    /**
     * @var string
     */
    private $tmpStaticPath;

    public function __construct(
        RemoteStorageConfig $remoteStorageConfig,
        DirectoryList $directoryList,
        File $fileDriver
    ) {
        $this->remoteStorageConfig = $remoteStorageConfig;
        $this->fileDriver = $fileDriver;

        $this->basePath = rtrim($directoryList->getRoot(), '/');
        $this->mediaPath = rtrim($directoryList->getPath(DirectoryList::MEDIA), '/');
        $this->staticPath = rtrim($directoryList->getPath(DirectoryList::STATIC_VIEW), '/');

        $this->tmpBasePath = rtrim($directoryList->getPath(DirectoryList::TMP), '/');
        $this->tmpMediaPath = $this->tmpBasePath . '/' . DirectoryList::MEDIA;
        $this->tmpStaticPath = $this->tmpBasePath . '/' . DirectoryList::STATIC_VIEW;
    }

    /**
     * Replaces Magento folders with their var/tmp equivalents.
     */
    public function afterGetFilenameFromUrl(BaseUrlConvertor $subject, string $result): string
    {
        if ($this->remoteStorageConfig->isEnabled() === false) {
            return $result;
        }

        try {
            $realMediaPath = $this->fileDriver->getRealPath($this->mediaPath);
            if (strpos($result, $realMediaPath) !== false) {
                return str_replace($this->mediaPath, $this->tmpMediaPath, $result);
            }
        } catch (FileSystemException|NoSuchEntityException $e) {
            throw new NotFoundException(__('Media folder does not exist'));
        }

        try {
            $realStaticPath = $this->fileDriver->getRealPath($this->staticPath);
            if (strpos($result, $realStaticPath) !== false) {
                return str_replace($this->staticPath, $this->tmpStaticPath, $result);
            }
        } catch (FileSystemException|NoSuchEntityException $e) {
            throw new NotFoundException(__('Static folder does not exist'));
        }

        try {
            $realBasePath = $this->fileDriver->getRealPath($this->basePath);
            if (strpos($result, $realBasePath) !== false) {
                return str_replace($this->basePath, $this->tmpBasePath, $result);
            }
        } catch (FileSystemException $e) {
            throw new NotFoundException(__('Base folder does not exist'));
        }

        return $result;
    }

    /**
     * Replaces var/tmp folders with Magento folders.
     */
    public function beforeGetUrlFromFilename(BaseUrlConvertor $subject, string $filename): array
    {
        if ($this->remoteStorageConfig->isEnabled() === false) {
            return [$filename];
        }

        if (strpos($filename, $this->tmpMediaPath) !== false) {
            $filename = str_replace($this->tmpMediaPath, $this->mediaPath, $filename);
        }

        if (strpos($filename, $this->tmpStaticPath) !== false) {
            $filename = str_replace($this->tmpStaticPath, $this->staticPath, $filename);
        }

        if (strpos($filename, $this->tmpBasePath) !== false) {
            $filename = str_replace($this->tmpBasePath, $this->basePath, $filename);
        }

        return [$filename];
    }
}
