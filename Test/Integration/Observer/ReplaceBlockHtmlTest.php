<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Text;
use Magento\Framework\View\Element\Text\ListText;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\Request;
use Yireo\NextGenImages\Observer\ReplaceBlockHtml;
use Yireo\NextGenImages\Test\Integration\AbstractTestCase;
use Yireo\NextGenImages\Test\Integration\Stub\WrappingHtmlReplacer;
use Yireo\NextGenImages\Util\BlockRenderStack;

/**
 * @magentoAppArea frontend
 */
class ReplaceBlockHtmlTest extends AbstractTestCase
{
    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture current_store yireo_nextgenimages/settings/enabled 1
     */
    public function testOnlyOutermostBlockIsProcessedInAlpineRequest(): void
    {
        $htmlReplacer = $this->setupObserver('true');

        $html = $this->renderNestedBlocks();

        $this->assertSame(1, substr_count($html, '<picture>'));
        $this->assertSame(1, $htmlReplacer->calls);
        $this->assertTrue($this->getObjectManager()->get(BlockRenderStack::class)->isEmpty());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture current_store yireo_nextgenimages/settings/enabled 1
     */
    public function testNothingIsProcessedWithInvalidHeader(): void
    {
        $htmlReplacer = $this->setupObserver('TRUE');

        $html = $this->renderNestedBlocks();

        $this->assertSame(0, substr_count($html, '<picture>'));
        $this->assertSame(0, $htmlReplacer->calls);
    }

    private function setupObserver(string $headerValue): WrappingHtmlReplacer
    {
        $objectManager = $this->getObjectManager();

        /** @var Request $request */
        $request = $objectManager->get(RequestInterface::class);
        $request->getHeaders()->addHeaderLine('X-Alpine-Request', $headerValue);

        $htmlReplacer = new WrappingHtmlReplacer();
        $objectManager->removeSharedInstance(ReplaceBlockHtml::class);
        $objectManager->addSharedInstance(
            $objectManager->create(ReplaceBlockHtml::class, ['htmlReplacer' => $htmlReplacer]),
            ReplaceBlockHtml::class
        );

        return $htmlReplacer;
    }

    private function renderNestedBlocks(): string
    {
        $layout = $this->getObjectManager()->get(LayoutInterface::class);
        $layout->getUpdate()->addHandle('default');

        $parentName = 'ngi.parent.' . uniqid();
        $childName = 'ngi.child.' . uniqid();
        $parent = $layout->createBlock(ListText::class, $parentName);
        $child = $layout->createBlock(Text::class, $childName);
        $child->setText('<div><img src="/media/test.jpg" alt="test"/></div>');
        $layout->setChild($parentName, $childName, 'child');

        return (string)$parent->toHtml();
    }

    private function getObjectManager(): ObjectManager
    {
        /** @var ObjectManager $objectManager */
        $objectManager = Bootstrap::getObjectManager();
        return $objectManager;
    }
}
