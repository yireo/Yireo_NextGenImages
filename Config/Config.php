<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\PageCache\Model\DepersonalizeChecker;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

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

    /**
     * Config constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param DepersonalizeChecker $depersonalizeChecker
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        DepersonalizeChecker $depersonalizeChecker,
        StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->depersonalizeChecker = $depersonalizeChecker;
        $this->storeManager = $storeManager;
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
     * @return bool
     */
    public function addLazyLoading(): bool
    {
        return (bool)$this->getValue('yireo_nextgenimages/settings/lazy_loading');
    }

    /**
     * @param LayoutInterface $block
     * @return bool
     */
    public function hasFullPageCacheEnabled(LayoutInterface $block): bool
    {
        if ($this->depersonalizeChecker->checkIfDepersonalize($block)) {
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
