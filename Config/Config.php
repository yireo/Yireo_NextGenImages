<?php
declare(strict_types=1);

namespace Yireo\NextGenImages\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\PageCache\Model\DepersonalizeChecker;

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
     * Config constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param DepersonalizeChecker $depersonalizeChecker
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        DepersonalizeChecker $depersonalizeChecker
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->depersonalizeChecker = $depersonalizeChecker;
    }

    /**
     * @return bool
     */
    public function enabled(): bool
    {
        return (bool)$this->scopeConfig->getValue('yireo_nextgenimages/settings/enabled');
    }

    /**
     * @return bool
     */
    public function allowImageCreation(): bool
    {
        return (bool)$this->scopeConfig->getValue('yireo_nextgenimages/settings/convert_images');
    }

    /**
     * @return bool
     */
    public function addLazyLoading(): bool
    {
        return (bool)$this->scopeConfig->getValue('yireo_nextgenimages/settings/lazy_loading');
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
        return (bool)$this->scopeConfig->getValue('yireo_nextgenimages/settings/debug');
    }
}
