<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\LayoutInterface;
use Yireo\NextGenImages\Config\Config;
use Yireo\NextGenImages\Util\BlockRenderStack;
use Yireo\NextGenImages\Util\HtmlReplacer;
use Yireo\NextGenImages\Util\ShouldModifyOutput;

/**
 * @todo Add integration test for URL /page_cache/block/esi/blocks/[%22catalog.topnav%22]/handles/WyJkZWZhdWx0Il0=
 */
class ReplaceBlockHtml implements ObserverInterface
{
    private const ALPINE_REQUEST_HEADER = 'X-Alpine-Request';

    /**
     * @var HtmlReplacer
     */
    private $htmlReplacer;

    /**
     * @var ShouldModifyOutput
     */
    private $shouldModifyOutput;

    /**
     * @var LayoutInterface
     */
    private $layout;

    /**
     * @var Config
     */
    private $config;

    private RequestInterface $request;

    private BlockRenderStack $blockRenderStack;

    /**
     * @param HtmlReplacer $htmlReplacer
     * @param ShouldModifyOutput $shouldModifyOutput
     * @param LayoutInterface $layout
     * @param Config $config
     * @param RequestInterface $request
     * @param BlockRenderStack $blockRenderStack
     */
    public function __construct(
        HtmlReplacer $htmlReplacer,
        ShouldModifyOutput $shouldModifyOutput,
        LayoutInterface $layout,
        Config $config,
        RequestInterface $request,
        BlockRenderStack $blockRenderStack
    ) {
        $this->htmlReplacer = $htmlReplacer;
        $this->shouldModifyOutput = $shouldModifyOutput;
        $this->layout = $layout;
        $this->config = $config;
        $this->request = $request;
        $this->blockRenderStack = $blockRenderStack;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $block = $observer->getEvent()->getBlock();
        if (false === $block instanceof BlockInterface) {
            return;
        }

        $this->blockRenderStack->pop($block);

        if (false === $this->shouldModify($block)) {
            return;
        }

        if ($this->shouldModifyOutput->shouldModifyOutput($this->layout) === false) {
            return;
        }

        $transport = $observer->getEvent()->getTransport();
        $html = (string)$transport->getHtml();
        if (false === $this->hasReplaceableContent($html)) {
            return;
        }

        $transport->setHtml($this->htmlReplacer->replace($html));
    }

    private function shouldModify(BlockInterface $block): bool
    {
        if ($this->isFullPageCacheBlock($block)) {
            return true;
        }

        if ($this->isAjax() && $this->blockRenderStack->isEmpty()) {
            return true;
        }

        return false;
    }

    private function hasReplaceableContent(string $html): bool
    {
        if (stripos($html, '<img') !== false) {
            return true;
        }

        return $this->config->convertCssBackgrounds();
    }

    private function isFullPageCacheBlock(BlockInterface $block): bool
    {
        if (false === $block instanceof AbstractBlock) {
            return false;
        }

        if (false === (bool)$block->getData('ttl')) {
            return false;
        }

        if (false === !$this->config->hasFullPageCacheEnabled($this->layout)) {
            return false;
        }

        return true;
    }

    private function isAjax(): bool
    {
        $value = $this->request->getHeader(self::ALPINE_REQUEST_HEADER);
        return in_array($value, ['true', '1'], true);
    }
}
