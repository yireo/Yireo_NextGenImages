<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Yireo\NextGenImages\Config\Config;
use Yireo\NextGenImages\Observer\ReplaceBlockHtml;
use Yireo\NextGenImages\Observer\TrackBlockRendering;
use Yireo\NextGenImages\Test\Unit\AbstractTestCase;
use Yireo\NextGenImages\Util\BlockRenderStack;
use Yireo\NextGenImages\Util\HtmlReplacer;
use Yireo\NextGenImages\Util\ShouldModifyOutput;

class ReplaceBlockHtmlTest extends AbstractTestCase
{
    private const HTML = '<div><img src="/media/test.jpg"/></div>';

    /**
     * @dataProvider getHeaderValues
     */
    #[DataProvider('getHeaderValues')]
    public function testHeaderStrictness(mixed $headerValue, bool $expectReplace): void
    {
        $htmlReplacer = $this->getHtmlReplacer($expectReplace ? 1 : 0);
        $observer = $this->getObserver($htmlReplacer, $headerValue, new BlockRenderStack());

        $transport = new DataObject(['html' => self::HTML]);
        $observer->execute($this->getEventObserver($this->getMagentoMock(Template::class), $transport));
        $this->assertSame($expectReplace ? 'replaced' : self::HTML, $transport->getData('html'));
    }

    public static function getHeaderValues(): array
    {
        return [
            ['true', true],
            ['1', true],
            [false, false],
            ['', false],
            ['TRUE', false],
            ['yes', false],
            ['1abc', false],
            ['0', false],
            ['true ', false],
        ];
    }

    public function testOnlyOutermostBlockIsProcessed(): void
    {
        $stack = new BlockRenderStack();
        $htmlReplacer = $this->getHtmlReplacer(1);
        $observer = $this->getObserver($htmlReplacer, 'true', $stack);
        $tracker = new TrackBlockRendering($stack, $this->getMagentoMock(ScopeConfigInterface::class));

        $parent = $this->getMagentoMock(Template::class);
        $child = $this->getMagentoMock(Template::class);

        $tracker->execute($this->getEventObserver($parent));
        $tracker->execute($this->getEventObserver($child));

        $childTransport = new DataObject(['html' => self::HTML]);
        $observer->execute($this->getEventObserver($child, $childTransport));
        $this->assertSame(self::HTML, $childTransport->getData('html'));

        $parentTransport = new DataObject(['html' => self::HTML]);
        $observer->execute($this->getEventObserver($parent, $parentTransport));
        $this->assertSame('replaced', $parentTransport->getData('html'));
        $this->assertTrue($stack->isEmpty());
    }

    public function testSkipWithoutImagesAndWithoutCssBackgrounds(): void
    {
        $htmlReplacer = $this->getHtmlReplacer(0);
        $observer = $this->getObserver($htmlReplacer, 'true', new BlockRenderStack(), false);

        $transport = new DataObject(['html' => '<div style="background: url(x.jpg)">foo</div>']);
        $observer->execute($this->getEventObserver($this->getMagentoMock(Template::class), $transport));
        $this->assertSame('<div style="background: url(x.jpg)">foo</div>', $transport->getData('html'));
    }

    public function testNoSkipWithoutImagesButWithCssBackgrounds(): void
    {
        $htmlReplacer = $this->getHtmlReplacer(1);
        $observer = $this->getObserver($htmlReplacer, 'true', new BlockRenderStack(), true);

        $transport = new DataObject(['html' => '<div>foo</div>']);
        $observer->execute($this->getEventObserver($this->getMagentoMock(Template::class), $transport));
        $this->assertSame('replaced', $transport->getData('html'));
    }

    public function testUppercaseImgTagIsNotSkipped(): void
    {
        $htmlReplacer = $this->getHtmlReplacer(1);
        $observer = $this->getObserver($htmlReplacer, 'true', new BlockRenderStack(), false);

        $transport = new DataObject(['html' => '<IMG src="/media/test.jpg">']);
        $observer->execute($this->getEventObserver($this->getMagentoMock(Template::class), $transport));
        $this->assertSame('replaced', $transport->getData('html'));
    }

    private function getHtmlReplacer(int $expectedCalls): HtmlReplacer
    {
        $htmlReplacer = $this->getMagentoMock(HtmlReplacer::class);
        $htmlReplacer->expects($this->exactly($expectedCalls))->method('replace')->willReturn('replaced');
        return $htmlReplacer;
    }

    private function getObserver(
        HtmlReplacer $htmlReplacer,
        mixed $headerValue,
        BlockRenderStack $stack,
        bool $convertCssBackgrounds = false
    ): ReplaceBlockHtml {
        $shouldModifyOutput = $this->getMagentoMock(ShouldModifyOutput::class);
        $shouldModifyOutput->method('shouldModifyOutput')->willReturn(true);

        $config = $this->getMagentoMock(Config::class);
        $config->method('convertCssBackgrounds')->willReturn($convertCssBackgrounds);

        $request = $this->getMagentoMock(Http::class);
        $request->method('getHeader')->with('X-Alpine-Request')->willReturn($headerValue);

        return new ReplaceBlockHtml(
            $htmlReplacer,
            $shouldModifyOutput,
            $this->getMagentoMock(LayoutInterface::class),
            $config,
            $request,
            $stack
        );
    }

    private function getEventObserver(BlockInterface $block, ?DataObject $transport = null): Observer
    {
        $data = ['block' => $block];
        if ($transport !== null) {
            $data['transport'] = $transport;
        }

        return new Observer(['event' => new Event($data)]);
    }
}
