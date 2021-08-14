<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Image;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\File\NotFoundException;
use Magento\Store\Model\StoreManagerInterface;

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
     * @throws NoSuchEntityException
     */
    public function isLocal(string $url): bool
    {
        if (!preg_match('/^https?:\/\//', $url)) {
            return true;
        }

        if (strpos($url, $this->getBaseUrl()) !== false) {
            return true;
        }

        if (strpos($url, $this->getMediaUrl()) !== false) {
            return true;
        }

        return false;
    }

    /**
     * @param string $url
     * @return string
     * @throws FileSystemException
     * @throws NoSuchEntityException
     */
    public function getFilenameFromUrl(string $url): string
    {
        $url = preg_replace('/\/static\/version([0-9]+\/)/', '/static/', (string)$url);

        if ($this->isLocal($url) === false) {
            throw new NotFoundException((string)__('URL "' . $url . '" does not appear to be a local file'));
        }

        if (strpos($url, $this->getMediaUrl()) !== false) {
            return str_replace($this->getMediaUrl(), $this->getMediaFolder() . '/', $url);
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
        return $this->urlModel->getBaseUrl();
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    private function getMediaUrl(): string
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
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
        return $this->directoryList->getPath('media');
    }
}
