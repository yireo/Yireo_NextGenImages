<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\PageCache\Model\DepersonalizeChecker;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Yireo\NextGenImages\Config\Source\TargetDirectory;

class Config implements ArgumentInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var DepersonalizeChecker
     */
    private $depersonalizeChecker;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    private DirectoryList $directoryList;

    /**
     * Config constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param DepersonalizeChecker $depersonalizeChecker
     * @param StoreManagerInterface $storeManager
     * @param DirectoryList $directoryList
     */
    public function __construct(
        ScopeConfigInterface  $scopeConfig,
        DepersonalizeChecker  $depersonalizeChecker,
        StoreManagerInterface $storeManager,
        DirectoryList         $directoryList
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->depersonalizeChecker = $depersonalizeChecker;
        $this->storeManager = $storeManager;
        $this->directoryList = $directoryList;
    }

    /**
     * @return bool
     */
    public function enabled(): bool
    {
        return (bool)$this->getValue('yireo_nextgenimages/settings/enabled');
    }

    /**
     * @return bool
     */
    public function allowImageCreation(): bool
    {
        return (bool)$this->getValue('yireo_nextgenimages/settings/convert_images');
    }

    /**
     * @return bool
     */
    public function convertImagesOnSave(): bool
    {
        return (bool)$this->getValue('yireo_nextgenimages/settings/convert_on_save');
    }

    /**
     * @return string
     */
    public function getTargetDirectory(): string
    {
        $value = $this->getValue('yireo_nextgenimages/settings/target_directory');
        if ($value === TargetDirectory::CACHE) {
            return $value;
        }

        return TargetDirectory::SAME_AS_SOURCE;
    }

    public function getCacheDirectoryPath(): string
    {
        $pubPath = $this->directoryList->getRoot() . '/pub/';

        $value = trim($this->getValue('yireo_nextgenimages/settings/cache_directory'));
        if (empty($value)) {
            return $pubPath . '/media/nextgenimages/';
        }

        if (!is_dir($value)) {
            return $pubPath . '/media/nextgenimages/';
        }

        return $pubPath . $value;
    }

    /**
     * @return bool
     */
    public function addHash(): bool
    {
        return (bool)$this->getValue('yireo_nextgenimages/settings/hash');
    }

    /**
     * @return bool
     */
    public function addLazyLoading(): bool
    {
        return (bool)$this->getValue('yireo_nextgenimages/settings/lazy_loading');
    }

    /**
     * @param LayoutInterface $layout
     * @return bool
     */
    public function hasFullPageCacheEnabled(LayoutInterface $layout): bool
    {
        if ($this->depersonalizeChecker->checkIfDepersonalize($layout)) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isDebugging(): bool
    {
        return (bool)$this->getValue('yireo_nextgenimages/settings/debug');
    }

    /**
     * @return bool
     */
    public function isLogging(): bool
    {
        return (bool)$this->getValue('yireo_nextgenimages/settings/log');
    }

    /**
     * @param string $path
     * @return mixed
     */
    private function getValue(string $path)
    {
        try {
            $value = $this->scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_STORE,
                $this->storeManager->getStore()
            );
        } catch (NoSuchEntityException $e) {
            $value = null;
        }

        return $value;
    }
}
