<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Util;

use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;
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
    private array $collectedUrls = [];

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
                'foobar',
                'foobar',
            ],
            [
                '<p>foobar</p><p>foobar</p>',
                '<p>foobar</p><p>foobar</p>',
            ],
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
            [
                '<img src="test1.jpg">test<span>test</span><img src="test2.jpg">',
                '<img src="test1.jpg">test<span>test</span><img src="test2.jpg">',
            ],
        ];
    }

    /**
     * @dataProvider getTestReplaceMultipleImagesArguments
     */
    public function testReplaceMultipleImages(string $originalHtml, string $expectedHtml)
    {
        $this->assertEquals($expectedHtml, $this->getHtmlReplacerWithRealDom()->replace($originalHtml));
    }

    public function getTestReplaceMultipleImagesArguments(): array
    {
        return [
            'different images' => [
                '<div><img src="/a.jpg"><img src="/b.png"/><img src="/c.jpeg" alt="c"></div>',
                '<div><picture><img src="/a.jpg"></picture><picture><img src="/b.png"/></picture>'
                .'<picture><img src="/c.jpeg" alt="c"></picture></div>',
            ],
            'identical images' => [
                '<p><img src="/a.jpg"></p><p><img src="/a.jpg"></p><p><img src="/a.jpg"></p>',
                '<p><picture><img src="/a.jpg"></picture></p><p><picture><img src="/a.jpg"></picture></p>'
                .'<p><picture><img src="/a.jpg"></picture></p>',
            ],
            'ineligible images in between' => [
                '<img src="/a.jpg"><img src="data:image/png;base64,foo"><img src="http://remote/b.jpg">'
                .'<img src="/c.gif"><img src="/media/captcha/d.png"><img src="/skip.jpg"><img src="/e.jpg">',
                '<picture><img src="/a.jpg"></picture><img src="data:image/png;base64,foo">'
                .'<img src="http://remote/b.jpg"><img src="/c.gif"><img src="/media/captcha/d.png">'
                .'<img src="/skip.jpg"><picture><img src="/e.jpg"></picture>',
            ],
            'existing picture is left alone' => [
                '<picture><source srcset="/a.webp"><img src="/a.jpg"></picture><img src="/a.jpg">',
                '<picture><source srcset="/a.webp"><img src="/a.jpg"></picture><picture><img src="/a.jpg"></picture>',
            ],
            'srcset and src images' => [
                '<img srcset="/a.jpg 1x, /a2.jpg 2x" src="/a.jpg"><img src="/b.jpg">',
                '<picture><img srcset="/a.jpg 1x, /a2.jpg 2x" src="/a.jpg"></picture><picture><img src="/b.jpg"></picture>',
            ],
            'quoted greater-than characters' => [
                '<img src="/a.jpg" @click="() => a > b"><img src="/b.jpg" x-on:load=\'() => c\'>',
                '<picture><img src="/a.jpg" @click="() => a > b"></picture>'
                .'<picture><img src="/b.jpg" x-on:load=\'() => c\'></picture>',
            ],
            'image html inside script is left alone' => [
                "<img src=\"/a.jpg\"><script>var a = '<img src=\"/a.jpg\">';</script>",
                "<picture><img src=\"/a.jpg\"></picture><script>var a = '<img src=\"/a.jpg\">';</script>",
            ],
        ];
    }

    public function testReplaceManyImagesInLargeHtml()
    {
        $imageCount = 500;
        $filler = str_repeat('<span class="filler">Lorem ipsum dolor sit amet</span>', 20);
        $originalHtml = '';
        $expectedHtml = '';
        for ($i = 0; $i < $imageCount; $i++) {
            $imageTag = '<img src="/media/catalog/product/'.($i % 50).'.jpg" alt="'.$i.'">';
            $originalHtml .= '<div>'.$imageTag.$filler.'</div>';
            $expectedHtml .= '<div><picture>'.$imageTag.'</picture>'.$filler.'</div>';
        }

        $start = microtime(true);
        $actualHtml = $this->getHtmlReplacerWithRealDom()->replace($originalHtml);
        $duration = microtime(true) - $start;

        $this->assertSame($imageCount, substr_count($actualHtml, '<picture>'));
        $this->assertStringNotContainsString('data-yireo-ngi-marker', $actualHtml);
        $this->assertStringNotContainsString('yireo_nextgenimages', $actualHtml);
        $this->assertEquals($expectedHtml, $actualHtml);
        $this->assertLessThan(5, $duration, 'Replacing many images in a large HTML document is too slow');
    }

    public function testSrcSetWithInjectedDescriptorIsSkipped()
    {
        $html = '<div><img srcset="/media/a.jpg 1x&quot;&gt;&lt;svg onload=alert(1)&gt;" alt="a"></div>';

        $actualHtml = $this->getHtmlReplacerWithRealDom()->replace($html);

        $this->assertSame($html, $actualHtml);
        $this->assertSame([], $this->collectedUrls);
    }

    /**
     * @dataProvider provideSrcSets
     */
    #[DataProvider('provideSrcSets')]
    public function testSrcSetDescriptors(string $srcSet, array $expectedUrls)
    {
        $html = '<img srcset="'.$srcSet.'" alt="a">';

        $actualHtml = $this->getHtmlReplacerWithRealDom()->replace($html);

        $this->assertSame('<picture>'.$html.'</picture>', $actualHtml);
        $this->assertSame([$expectedUrls], $this->collectedUrls);
    }

    public static function provideSrcSets(): array
    {
        return [
            'valid descriptors' => [
                '/a.jpg 1x, /b.jpg 1.5x, /c.jpg 480w',
                ['1x' => '/a.jpg', '1.5x' => '/b.jpg', '480w' => '/c.jpg'],
            ],
            'without descriptor' => [
                '/a.jpg, /b.jpg 2x',
                [0 => '/a.jpg', '2x' => '/b.jpg'],
            ],
            'other whitespace' => [
                "  /a.jpg\t1x ,\n/b.jpg   2x  ",
                ['1x' => '/a.jpg', '2x' => '/b.jpg'],
            ],
            'invalid descriptors are dropped' => [
                '/a.jpg 1x, /b.jpg 2x&quot;&gt;&lt;svg onload=alert(1)&gt;, /c.jpg 3y, /d.jpg x, /e.jpg 4x',
                ['1x' => '/a.jpg', '4x' => '/e.jpg'],
            ],
        ];
    }

    public function testUppercaseImageTagIsReplaced()
    {
        $html = '<div><IMG SRC="/a.jpg" ALT="a"><Img src="/b.jpg"></div>';

        $this->assertSame(
            '<div><picture><IMG SRC="/a.jpg" ALT="a"></picture><picture><Img src="/b.jpg"></picture></div>',
            $this->getHtmlReplacerWithRealDom()->replace($html)
        );
    }

    public function testExistingMarkerAttributesArePreserved()
    {
        $html = '<img data-marker="1" src="/a.jpg"><img src="/b.jpg" data-yireo-ngi-marker="foo-1">';

        $this->assertSame(
            '<picture><img data-marker="1" src="/a.jpg"></picture>'
            .'<picture><img src="/b.jpg" data-yireo-ngi-marker="foo-1"></picture>',
            $this->getHtmlReplacerWithRealDom()->replace($html)
        );
    }

    public function testMarkerWithRegexCharacters()
    {
        $htmlReplacer = $this->getHtmlReplacerWithRealDom();
        $markerPrefix = new ReflectionProperty(HtmlReplacer::class, 'markerPrefix');
        $markerPrefix->setValue($htmlReplacer, 'a.b+(c)/[d]');

        $html = '<img src="/a.jpg"><img src="/b.jpg">';

        $this->assertSame(
            '<picture><img src="/a.jpg"></picture><picture><img src="/b.jpg"></picture>',
            $htmlReplacer->replace($html)
        );
    }

    public function testMarkerPrefixIsRandom()
    {
        $markerPrefix = new ReflectionProperty(HtmlReplacer::class, 'markerPrefix');

        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}$/', $markerPrefix->getValue($this->getHtmlReplacerWithRealDom()));
        $this->assertNotSame(
            $markerPrefix->getValue($this->getHtmlReplacerWithRealDom()),
            $markerPrefix->getValue($this->getHtmlReplacerWithRealDom())
        );
    }

    public function testOriginalHtmlIsReturnedOnRegexFailure()
    {
        $backtrackLimit = ini_get('pcre.backtrack_limit');
        $jit = ini_get('pcre.jit');
        ini_set('pcre.jit', '0');
        ini_set('pcre.backtrack_limit', '1');

        try {
            $html = '<div><img src="/a.jpg" alt="'.str_repeat('a', 1000).'"></div>';
            $actualHtml = $this->getHtmlReplacerWithRealDom()->replace($html);
        } finally {
            ini_set('pcre.backtrack_limit', (string)$backtrackLimit);
            ini_set('pcre.jit', (string)$jit);
        }

        $this->assertSame($html, $actualHtml);
    }

    private function getHtmlReplacerWithRealDom(): HtmlReplacer
    {
        $urlConvertor = $this->getMagentoMock(UrlConvertor::class);
        $urlConvertor->method('isLocal')->willReturnCallback(function (string $url) {
            return !str_starts_with($url, 'http://remote');
        });

        $imageCollector = $this->getMagentoMock(ImageCollector::class);
        $imageCollector->method('collect')->willReturnCallback(function ($urls) {
            $this->collectedUrls[] = $urls;
            $urls = is_array($urls) ? $urls : [$urls];
            foreach ($urls as $url) {
                if (str_contains($url, 'skip')) {
                    return [];
                }
            }

            return [new Image('/tmp/pub/test.webp', '/test.webp')];
        });

        $pictureFactory = $this->getMagentoMock(PictureFactory::class);
        $pictureFactory->method('create')->willReturnCallback(
            function ($image, $images, string $imageHtml) {
                $block = $this->getMagentoMock(Picture::class);
                $block->method('toHtml')->willReturn('<picture>'.$imageHtml.'</picture>');

                return $block;
            }
        );

        $config = $this->getMagentoMock(Config::class);
        $config->method('convertCssBackgrounds')->willReturn(false);

        return new HtmlReplacer(
            $urlConvertor,
            $imageCollector,
            $pictureFactory,
            $this->getMagentoMock(ImageFactory::class),
            $config,
            new DomUtils()
        );
    }
}
