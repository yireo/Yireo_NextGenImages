<?php

declare(strict_types=1);

namespace Yireo\NextGenImages\Util;

use Magento\Framework\App\Filesystem\DirectoryList as FilesystemDirectoryList;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\File\NotFoundException;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class UrlConvertor
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @param StoreManagerInterface $storeManager
     * @param DirectoryList $directoryList
     * @param Escaper $escaper
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        DirectoryList         $directoryList,
        Escaper               $escaper
    ) {
        $this->storeManager = $storeManager;
        $this->directoryList = $directoryList;
        $this->escaper = $escaper;
    }

    /**
     * @param string $url
     * @return bool
     */
    public function isLocal(string $url): bool
    {
        $url = $this->normalizeUrl($url);
        if (!preg_match('#^http(s?)://#', $url)) {
            return true;
        }

        foreach ($this->storeManager->getStores() as $store) {
            $storeBaseUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_WEB);
            if (strpos($url, $storeBaseUrl) !== false) {
                return true;
            }

            $storeMediaUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            if (strpos($url, $storeMediaUrl) !== false) {
                return true;
            }

            $storeStaticUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_STATIC);
            if (strpos($url, $storeStaticUrl) !== false) {
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
            if (strpos($filename, $this->getMediaFolder()) !== false) {
                return str_replace($this->getMediaFolder() . '/', $this->getMediaUrl(), $filename);
            }
        } catch (FileSystemException|NoSuchEntityException $e) {
            throw new NotFoundException((string)__('Media folder does not exist'));
        }

        try {
            if (strpos($filename, $this->getStaticFolder()) !== false) {
                return str_replace($this->getStaticFolder() . '/', $this->getStaticUrl(), $filename);
            }
        } catch (FileSystemException|NoSuchEntityException $e) {
            throw new NotFoundException((string)__('Static folder does not exist'));
        }

        if (strpos($filename, $this->getBaseFolder()) !== false) {
            return str_replace($this->getBaseFolder() . '/', $this->getBaseUrl(), $filename);
        }

        if (!preg_match('/^\//', $filename)) {
            return $this->getBaseUrl() . $filename;
        }

        throw new NotFoundException((string)__('Filename "' . $filename . '" is not matched with an URL'));
    }

    /**
     * @param string $url
     * @return string
     * @throws FileSystemException
     */
    public function getFilenameFromUrl(string $url): string
    {
        $url = (string)$this->escaper->escapeHtml($url);
        $url = preg_replace('/\/static\/version(\d+\/)/', '/static/', $url);
        $url = $this->normalizeUrl($url);

        if ($this->isLocal($url) === false) {
            throw new NotFoundException((string)__('URL "' . $url . '" does not appear to be a local file'));
        }

        foreach ($this->storeManager->getStores() as $store) {
            $storeBaseUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_WEB);
            if (strpos($url, $storeBaseUrl) !== false) {
                return str_replace($storeBaseUrl, $this->getBaseFolder() . '/', $url);
            }

            $storeMediaUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            if (strpos($url, $storeMediaUrl) !== false) {
                return str_replace($storeMediaUrl, $this->getMediaFolder() . '/', $url);
            }

            $staticUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_STATIC);
            if (strpos($url, $staticUrl) !== false) {
                return str_replace($staticUrl, $this->getStaticFolder() . '/', $url);
            }
        }

        if (preg_match('/^\//', $url)) {
            return $this->getBaseFolder() . $url;
        }

        throw new NotFoundException((string)__('URL "' . $url . '" is not matched with a local file'));
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
        return $this->directoryList->getRoot() . '/pub';
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
     * @param string $url
     * @return string
     */
    private function normalizeUrl(string $url): string
    {
        $url = str_replace('/index.php/', '/', $url);
        return preg_replace('#^//#', 'http://', $url);
    }
}
