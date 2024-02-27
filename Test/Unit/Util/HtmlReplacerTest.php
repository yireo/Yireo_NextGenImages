<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Util;

use Yireo\NextGenImages\Block\Picture;
use Yireo\NextGenImages\Block\PictureFactory;
use Yireo\NextGenImages\Config\Config;
use Yireo\NextGenImages\Convertor\ConvertorListing;
use Yireo\NextGenImages\Image\Image;
use Yireo\NextGenImages\Image\ImageCollector;
use Yireo\NextGenImages\Image\ImageFactory;
use Yireo\NextGenImages\Test\Unit\AbstractTestCase;
use Yireo\NextGenImages\Util\DomUtils;
use Yireo\NextGenImages\Util\HtmlReplacer;
use Yireo\NextGenImages\Util\UrlConvertor;

class HtmlReplacerTest extends AbstractTestCase
{
    public function testReplaceWithNoImages()
    {
        $htmlReplacer = new HtmlReplacer(
            $this->getMagentoMock(UrlConvertor::class),
            $this->getMagentoMock(ImageCollector::class),
            $this->getMagentoMock(PictureFactory::class),
            $this->getMagentoMock(ImageFactory::class),
            $this->getMagentoMock(Config::class),
            $this->getMagentoMock(DomUtils::class)
        );

        $html = '<div><img src="/img/test.png"/></div>';
        $result = $htmlReplacer->replace($html);
        $this->assertEquals($html, $result);
    }

    /**
     * @param string $originalHtml
     * @param string $expectedHtml
     * @return void
     * @dataProvider getTestReplaceWithTestImageArguments
     */
    public function testReplaceWithTestImage(string $originalHtml, string $expectedHtml)
    {
        $urlConvertor = $this->getMagentoMock(UrlConvertor::class);
        $urlConvertor->method('isLocal')->willReturn(true);

        $imageCollector = $this->getMagentoMock(ImageCollector::class);
        $images = [
            new Image('/tmp/pub/test.png', '/test.png'),
            new Image('/tmp/pub/test.webp', '/test.webp'),
        ];
        $imageCollector->method('collect')->willReturn($images);

        $block = $this->getMagentoMock(Picture::class);
        $block->method('toHtml')->willReturn('<DUMMYPICTURE/>');

        $pictureFactory = $this->getMagentoMock(PictureFactory::class);
        $pictureFactory->method('create')->willReturn($block);

        $imageFactory = $this->getMagentoMock(ImageFactory::class);

        $config = $this->getMagentoMock(Config::class);
        $config->method('convertCssBackgrounds')->willReturn(true);

        $htmlReplacer = new HtmlReplacer(
            $urlConvertor,
            $imageCollector,
            $pictureFactory,
            $imageFactory,
            $config,
            $this->getMagentoMock(DomUtils::class)
        );

        $actualHtml = $htmlReplacer->replace($originalHtml);
        $this->assertEquals($expectedHtml, $actualHtml);
    }

    public function getTestReplaceWithTestImageArguments(): array
    {
        return [
            [
                '<div><img src="/img/test.png"/></div>',
                '<div><img src="/img/test.png"/></div>'
            ],
            [
                '<div><img src="/img/test.png"/>FOOBAR</div>',
                '<div><img src="/img/test.png"/>FOOBAR</div>'
            ],
            [
                '<div><img :src="/img/test.png"/>FOOBAR</div>',
                '<div><img :src="/img/test.png"/>FOOBAR</div>'
            ],
            [
                '<div><img :data-src="/img/test.png"/>FOOBAR</div>',
                '<div><img :data-src="/img/test.png"/>FOOBAR</div>'
            ],
            [
                '<div><img src="data:image/gif;base64,foobar"/></div>',
                '<div><img src="data:image/gif;base64,foobar"/></div>'
            ],
            [
                '<div style="background-image: url(http://localhost/img/test.png);"></div>',
                '<div style="background-image: url(/test.png);"></div>'
            ],
            [
                '<div><img src="/img/test.png"/><img src="/img/test.png"/><img src="/img/test.png"/></div>',
                '<div><img src="/img/test.png"/><img src="/img/test.png"/><img src="/img/test.png"/></div>'
            ],
            [
                "<div><script>var imgElement = '<img src=\"...\" />';</script></div>",
                "<div><script>var imgElement = '<img src=\"...\" />';</script></div>",
            ],
            [
                '<div><div @click="fullscreen = false; $nextTick(() => calcPageSize())"><img src="/img/test.png"/></div></div>',
                '<div><div @click="fullscreen = false; $nextTick(() => calcPageSize())"><img src="/img/test.png"/></div></div>'
            ],
        ];
    }
}
