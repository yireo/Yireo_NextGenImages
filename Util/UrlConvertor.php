<?php

declare(strict_types=1);

namespace Yireo\NextGenImages\Util;

use Magento\Framework\App\Filesystem\DirectoryList as FilesystemDirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\File\NotFoundException;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class UrlConvertor
{
    private const PROTECTED_MEDIA_FOLDERS = ['customer', 'downloadable', 'custom_options', 'import', 'captcha'];

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    private FileDriver $fileDriver;

    /**
     * @param StoreManagerInterface $storeManager
     * @param DirectoryList $directoryList
     * @param FileDriver $fileDriver
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        DirectoryList $directoryList,
        FileDriver $fileDriver
    ) {
        $this->storeManager = $storeManager;
        $this->directoryList = $directoryList;
        $this->fileDriver = $fileDriver;
    }

    /**
     * @param string $url
     * @return bool
     */
    public function isLocal(string $url): bool
    {
        $url = $this->normalizeUrl($url);
        if (!preg_match('#^https?://#i', $url)) {
            return !preg_match('/^[a-z][a-z0-9+.\-]*:/i', $url);
        }

        foreach ($this->storeManager->getStores() as $store) {
            /** @var Store $store */
            $storeBaseUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_WEB);
            if (str_starts_with($url, $storeBaseUrl)) {
                return true;
            }

            $storeMediaUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            if (str_starts_with($url, $storeMediaUrl)) {
                return true;
            }

            $storeStaticUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_STATIC);
            if (str_starts_with($url, $storeStaticUrl)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $filename
     * @return string
     * @throws NoSuchEntityException
     */
    public function getUrlFromFilename(string $filename): string
    {
        try {
            $mediaFolder = $this->getMediaFolder();
            $realMediaFolder = $this->fileDriver->getRealPath($mediaFolder);
            if (strpos($filename, $realMediaFolder) !== false) {
                return str_replace($mediaFolder.'/', $this->getMediaUrl(), $filename);
            }
        } catch (FileSystemException|NoSuchEntityException $e) {
            throw new NotFoundException((string)__('Media folder does not exist'));
        }

        try {
            $staticFolder = $this->getStaticFolder();
            $realStaticFolder = $this->fileDriver->getRealPath($staticFolder);
            if (strpos($filename, $realStaticFolder) !== false) {
                return str_replace($this->getStaticFolder().'/', $this->getStaticUrl(), $filename);
            }
        } catch (FileSystemException|NoSuchEntityException $e) {
            throw new NotFoundException((string)__('Static folder does not exist'));
        }

        $baseFolder = $this->getBaseFolder();
        $realBaseFolder = $this->fileDriver->getRealPath($baseFolder);
        if (strpos($filename, $realBaseFolder) !== false) {
            return str_replace($this->getBaseFolder().'/', $this->getBaseUrl(), $filename);
        }

        if (!preg_match('/^\//', $filename)) {
            return $this->getBaseUrl().$filename;
        }

        throw new NotFoundException((string)__('Filename "'.$filename.'" is not matched with an URL'));
    }

    /**
     * @param string $url
     * @return string
     * @throws FileSystemException
     */
    public function getFilenameFromUrl(string $url): string
    {
        $url = preg_replace('/\/static\/version(\d+\/)/', '/static/', $url);
        $url = str_replace('/pub/', '/', $url);
        $url = $this->normalizeUrl($url);

        if ($this->isLocal($url) === false) {
            throw new NotFoundException((string)__('URL "'.$url.'" does not appear to be a local file'));
        }

        foreach ($this->storeManager->getStores() as $store) {
            /** @var Store $store */
            $storeBaseUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_WEB);
            if (str_starts_with($url, $storeBaseUrl)) {
                return $this->assertAllowedPath(
                    $this->getBaseFolder().'/'.substr($url, strlen($storeBaseUrl))
                );
            }

            $storeMediaUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            if (str_starts_with($url, $storeMediaUrl)) {
                return $this->assertAllowedPath(
                    $this->getMediaFolder().'/'.substr($url, strlen($storeMediaUrl))
                );
            }

            $staticUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_STATIC);
            if (str_starts_with($url, $staticUrl)) {
                return $this->assertAllowedPath(
                    $this->getStaticFolder().'/'.substr($url, strlen($staticUrl))
                );
            }
        }

        if (preg_match('/^\//', $url)) {
            return $this->assertAllowedPath($this->getBaseFolder().$url);
        }

        throw new NotFoundException((string)__('URL "'.$url.'" is not matched with a local file'));
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    private function getBaseUrl(): string
    {
        $store = $this->storeManager->getStore();
        $baseUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_WEB);

        return $this->normalizeUrl($baseUrl);
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    private function getMediaUrl(): string
    {
        /** @var Store $store */
        $store = $this->storeManager->getStore();
        $mediaUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

        return $this->normalizeUrl($mediaUrl);
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    private function getStaticUrl(): string
    {
        /** @var Store $store */
        $store = $this->storeManager->getStore();
        $staticUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_STATIC);

        return $this->normalizeUrl($staticUrl);
    }

    /**
     * @return string
     */
    private function getBaseFolder(): string
    {
        return $this->directoryList->getRoot().'/pub';
    }

    /**
     * @return string
     * @throws FileSystemException
     */
    private function getMediaFolder(): string
    {
        return rtrim($this->directoryList->getPath(FilesystemDirectoryList::MEDIA), '/');
    }

    /**
     * @return string
     * @throws FileSystemException
     */
    private function getStaticFolder(): string
    {
        return rtrim($this->directoryList->getPath(FilesystemDirectoryList::STATIC_VIEW), '/');
    }

    /**
     * @param string $path
     * @return string
     * @throws FileSystemException
     */
    private function assertAllowedPath(string $path): string
    {
        $normalizedPath = PathNormalizer::normalize($path);
        if ($this->isInProtectedMediaFolder($normalizedPath)) {
            throw new NotFoundException((string)__('Path "'.$path.'" is inside a protected media folder'));
        }

        $allowedFolders = [$this->getBaseFolder(), $this->getMediaFolder(), $this->getStaticFolder()];
        foreach ($allowedFolders as $allowedFolder) {
            if (str_starts_with($normalizedPath, PathNormalizer::normalize($allowedFolder).'/')) {
                return $normalizedPath;
            }
        }

        throw new NotFoundException((string)__('Path "'.$path.'" is outside of the public folder'));
    }

    /**
     * @param string $normalizedPath
     * @return bool
     * @throws FileSystemException
     */
    private function isInProtectedMediaFolder(string $normalizedPath): bool
    {
        $normalizedPath = strtolower($normalizedPath).'/';
        $mediaFolders = [$this->getMediaFolder(), $this->getBaseFolder().'/media'];
        foreach ($mediaFolders as $mediaFolder) {
            $mediaFolder = strtolower(PathNormalizer::normalize($mediaFolder));
            foreach (self::PROTECTED_MEDIA_FOLDERS as $protectedFolder) {
                if (str_starts_with($normalizedPath, $mediaFolder.'/'.$protectedFolder.'/')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param string $url
     * @return string
     */
    private function normalizeUrl(string $url): string
    {
        $url = str_replace('/index.php/', '/', $url);

        return preg_replace('#^//#', 'http://', $url);
    }
}
