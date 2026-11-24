<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Store\Model\ScopeInterface;
use Yireo\NextGenImages\Util\AlpineRequest;
use Yireo\NextGenImages\Util\BlockRenderStack;

class TrackBlockRendering implements ObserverInterface
{
    public function __construct(
        private readonly BlockRenderStack $blockRenderStack,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly AlpineRequest $alpineRequest
    ) {
    }

    public function execute(Observer $observer): void
    {
        if (false === $this->alpineRequest->isAlpineRequest()) {
            return;
        }

        $block = $observer->getEvent()->getBlock();
        if (false === $block instanceof BlockInterface) {
            return;
        }

        if ($this->hasOutputDisabled($block)) {
            return;
        }

        $this->blockRenderStack->push($block);
    }

    private function hasOutputDisabled(BlockInterface $block): bool
    {
        if (false === $block instanceof AbstractBlock) {
            return false;
        }

        return (bool)$this->scopeConfig->getValue(
            'advanced/modules_disable_output/' . $block->getModuleName(),
            ScopeInterface::SCOPE_STORE
        );
    }
}
