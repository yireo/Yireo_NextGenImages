<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Util;

use Magento\Framework\App\ObjectManager;
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
        $om = ObjectManager::getInstance();
        $htmlReplacer = new HtmlReplacer(
            $om->create(UrlConvertor::class),
            $om->create(ImageCollector::class),
            $om->create(PictureFactory::class),
            $om->create(ImageFactory::class)
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
     * @magentoAppArea frontend
     */
    public function testReplaceWithTestImage(string $originalHtml, string $expectedHtml)
    {
        $om = ObjectManager::getInstance();
    
        $imageCollector = $this->getMagentoMock(ImageCollector::class);
        $images = [
            new Image('/tmp/pub/test.png', '/test.png'),
            new Image('/tmp/pub/test.webp', '/test.webp'),
        ];
        $imageCollector->method('collect')->willReturn($images);

        $htmlReplacer = new HtmlReplacer(
            $om->create(UrlConvertor::class),
            $imageCollector,
            $om->create(PictureFactory::class),
            $om->create(ImageFactory::class)
        );

        $resultingHtml = $htmlReplacer->replace($originalHtml);
        $resultingHtml = preg_replace("/^\ +/m", '', $resultingHtml);
        $resultingHtml = str_replace("\n", '', $resultingHtml);
        $this->assertEquals($expectedHtml, $resultingHtml);
    }

    public function getTestReplaceWithTestImageArguments(): array
    {
        return [
            [
                '<div><img src="/test.png"/></div>',
                '<div><picture><source type="image/png" srcset="/test.png"><source type="image/webp" srcset="/test.webp"><img src="/test.png" loading="lazy" /></picture></div>'
            ],
            [
                '<div><img src="/test.png"/>FOOBAR</div>',
                '<div><picture><source type="image/png" srcset="/test.png"><source type="image/webp" srcset="/test.webp"><img src="/test.png" loading="lazy" /></picture>FOOBAR</div>'
            ],
            [
                '<div><img src="data:image/gif;base64,foobar"/></div>',
                '<div><img src="data:image/gif;base64,foobar"/></div>'
            ],
            [
                '<div style="background-image: url(/test.png);"></div>',
                '<div style="background-image: url(/test.png);"></div>'
            ],
            [
                '<div><img src="/test.png"/><img src="/test.png"/><img src="/test.png"/></div>',
                '<div><picture><source type="image/png" srcset="/test.png"><source type="image/webp" srcset="/test.webp"><img src="/test.png" loading="lazy" /></picture><picture><source type="image/png" srcset="/test.png"><source type="image/webp" srcset="/test.webp"><img src="/test.png" loading="lazy" /></picture><picture><source type="image/png" srcset="/test.png"><source type="image/webp" srcset="/test.webp"><img src="/test.png" loading="lazy" /></picture></div>'
            ],
            [
                "<script>var imgElement = '<img src=\"/test.png\" />';</script>",
                "<script>var imgElement = '<img src=\"/test.png\" />';</script>",
            ]
        ];
    }
}
