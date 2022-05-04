<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Util;

use Yireo\NextGenImages\Block\Picture;
use Yireo\NextGenImages\Block\PictureFactory;
use Yireo\NextGenImages\Image\Image;
use Yireo\NextGenImages\Image\ImageCollector;
use Yireo\NextGenImages\Image\ImageFactory;
use Yireo\NextGenImages\Test\Unit\AbstractTestCase;
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
            $this->getMagentoMock(ImageFactory::class)
        );

        $html = '<div><img src="/img/test.png"/></div>';
        $result = $htmlReplacer->replace($html);
        $this->assertEquals($html, $result);
    }

    /**
     * @param string $originalHtml
     * @param string $finalHtml
     * @return void
     * @dataProvider getArguments
     */
    public function testReplaceWithTestImage(string $originalHtml, string $finalHtml)
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
        $block->method('toHtml')->willReturn('<picture><img src="/img/test.png"/></picture>');
        $pictureFactory = $this->getMagentoMock(PictureFactory::class);
        $pictureFactory->method('create')->willReturn($block);

        $imageFactory = $this->getMagentoMock(ImageFactory::class);

        $htmlReplacer = new HtmlReplacer(
            $urlConvertor,
            $imageCollector,
            $pictureFactory,
            $imageFactory
        );

        $result = $htmlReplacer->replace($originalHtml);
        $this->assertEquals($finalHtml, $result);
    }

    public function getArguments(): array
    {
        return [
            [
                '<div><img src="/img/test.png"/></div>',
                '<div><picture><img src="/img/test.png"/></picture></div>'
            ],
            [
                '<div><img src="/img/test.png"/>FOOBAR</div>',
                '<div><picture><img src="/img/test.png"/></picture>FOOBAR</div>'
            ],
            [
                '<div><img src="data:image/gif;base64,foobar"/></div>',
                '<div><img src="data:image/gif;base64,foobar"/></div>'
            ],
            [
                '<div style="background-image: url(foobar.png);"></div>',
                '<div style="background-image: url(foobar.png);"></div>'
            ]
        ];
    }
}
