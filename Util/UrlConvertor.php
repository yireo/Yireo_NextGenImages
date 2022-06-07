<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Util;

use Magento\Framework\App\Filesystem\DirectoryList as FilesystemDirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\File\NotFoundException;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Throwable;

class UrlConvertor
{
    /**
     * @var UrlInterface
     */
    private $urlModel;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param UrlInterface $urlModel
     * @param StoreManagerInterface $storeManager
     * @param DirectoryList $directoryList
     */
    public function __construct(
        UrlInterface $urlModel,
        StoreManagerInterface $storeManager,
        DirectoryList $directoryList
    ) {
        $this->urlModel = $urlModel;
        $this->storeManager = $storeManager;
        $this->directoryList = $directoryList;
    }

    /**
     * @param string $url
     * @return bool
     */
    public function isLocal(string $url): bool
    {
        if (!preg_match('/^https?:\/\//', $url)) {
            return true;
        }

        $baseUrl = $this->getBaseUrl();
        if (strpos($url, $baseUrl) !== false) {
            return true;
        }

        try {
            $mediaUrl = $this->getMediaUrl();
            if (strpos($url, $mediaUrl) !== false) {
                return true;
            }
        } catch (NoSuchEntityException $e) {
            return false;
        }

        return false;
    }

    /**
     * @param string $filename
     * @return string
     * @throws NotFoundException
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
     * @throws NotFoundException
     */
    public function getFilenameFromUrl(string $url): string
    {
        $url = html_entity_decode($url);
        $url = preg_replace('/\/static\/version(\d+\/)/', '/static/', $url);

        if ($this->isLocal($url) === false) {
            throw new NotFoundException((string)__('URL "' . $url . '" does not appear to be a local file'));
        }

        try {
            if (strpos($url, $this->getMediaUrl()) !== false) {
                return str_replace($this->getMediaUrl(), $this->getMediaFolder() . '/', $url);
            }
        } catch (Throwable $e) {
            throw new NotFoundException((string)__('Media folder does not exist'));
        }

        try {
            if (strpos($url, $this->getStaticUrl()) !== false) {
                return str_replace($this->getStaticUrl(), $this->getStaticFolder() . '/', $url);
            }
        } catch (Throwable $e) {
            throw new NotFoundException((string)__('Static folder does not exist'));
        }

        if (strpos($url, $this->getBaseUrl()) !== false) {
            return str_replace($this->getBaseUrl(), $this->getBaseFolder() . '/', $url);
        }

        if (preg_match('/^\//', $url)) {
            return $this->getBaseFolder() . $url;
        }

        throw new NotFoundException((string)__('URL "' . $url . '" is not matched with a local file'));
    }

    /**
     * @return string
     */
    private function getBaseUrl(): string
    {
        return str_replace('/index.php/', '/', $this->urlModel->getBaseUrl());
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    private function getMediaUrl(): string
    {
        /** @var Store $store */
        $store = $this->storeManager->getStore();
        return $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    private function getStaticUrl(): string
    {
        /** @var Store $store */
        $store = $this->storeManager->getStore();
        return $store->getBaseUrl(UrlInterface::URL_TYPE_STATIC);
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
        return $this->directoryList->getPath(FilesystemDirectoryList::MEDIA);
    }

    /**
     * @return string
     * @throws FileSystemException
     */
    private function getStaticFolder(): string
    {
        return $this->directoryList->getPath(FilesystemDirectoryList::STATIC_VIEW);
    }
}
