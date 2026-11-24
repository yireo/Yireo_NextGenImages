<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\Util;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Filesystem\Driver\File;
use Yireo\NextGenImages\Block\PictureFactory;
use Yireo\NextGenImages\Config\Config;
use Yireo\NextGenImages\Convertor\ConvertorListing;
use Yireo\NextGenImages\Image\Image;
use Yireo\NextGenImages\Image\ImageCollector;
use Yireo\NextGenImages\Image\ImageFactory;
use Yireo\NextGenImages\Test\Integration\AbstractTestCase;
use Yireo\NextGenImages\Test\Integration\Stub\Convertor\FakeWebpConvertor;
use Yireo\NextGenImages\Util\DomUtils;
use Yireo\NextGenImages\Util\HtmlReplacer;
use Yireo\NextGenImages\Util\UrlConvertor;

class HtmlReplacerTest extends AbstractTestCase
{
    private const XSS_IMAGE_URL = 'http://localhost/media/nextgenimages-xss-test/test1.jpg';

    public function testReplaceWithNoImages()
    {
        $om = ObjectManager::getInstance();
        $htmlReplacer = new HtmlReplacer(
            $om->create(UrlConvertor::class),
            $om->create(ImageCollector::class),
            $om->create(PictureFactory::class),
            $om->create(ImageFactory::class),
            $om->create(Config::class),
            $om->create(DomUtils::class)
        );

        $html = '<div><img src="/img/test1.jpg"/></div>';
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
        $this->copyImages();

        $imagesByUrl = [
            '/media/tmp/test1.jpg' => [
                new Image('/tmp/pub/media/tmp/test1.jpg', '/media/tmp/test1.jpg'),
                new Image('/tmp/pub/media/tmp/test1.webp', '/media/tmp/test1.webp'),
            ],
            '/media/tmp/test2.jpg' => [
                new Image('/tmp/pub/media/tmp/test2.jpg', '/media/tmp/test2.jpg'),
                new Image('/tmp/pub/media/tmp/test2.webp', '/media/tmp/test2.webp'),
            ],
        ];

        $imageCollector = $this->getMagentoMock(ImageCollector::class);
        $imageCollector
            ->method('collectFromImage')
            ->willReturnCallback(fn (Image $image) => $imagesByUrl[$image->getUrl()] ?? []);

        $htmlReplacer = new HtmlReplacer(
            $om->create(UrlConvertor::class),
            $imageCollector,
            $om->create(PictureFactory::class),
            $om->create(ImageFactory::class),
            $om->create(Config::class),
            $om->create(DomUtils::class)
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
                '<div>'
                .'<img src="/media/tmp/test1.jpg"/>'
                .'</div>',
                '<div>'
                .'<picture>'
                .'<source type="image/jpeg" srcset="/media/tmp/test1.jpg">'
                .'<source type="image/webp" srcset="/media/tmp/test1.webp">'
                .'<img src="/media/tmp/test1.jpg" loading="lazy" />'
                .'</picture>'
                .'</div>',
            ],
            [
                '<div>'
                .'<img src="/media/tmp/test1.jpg"/>'
                .'FOOBAR'
                .'</div>',
                '<div>'
                .'<picture>'
                .'<source type="image/jpeg" srcset="/media/tmp/test1.jpg">'
                .'<source type="image/webp" srcset="/media/tmp/test1.webp">'
                .'<img src="/media/tmp/test1.jpg" loading="lazy" />'
                .'</picture>'
                .'FOOBAR'
                .'</div>',
            ],
            [
                '<div>'
                .'<img src="data:image/gif;base64,foobar"/>'
                .'</div>',
                '<div>'
                .'<img src="data:image/gif;base64,foobar"/>'
                .'</div>',
            ],
            [
                '<div>'
                .'<img data-src="/media/tmp/test1.jpg"/>'
                .'</div>',
                '<div>'
                .'<picture>'
                .'<source type="image/jpeg" data-srcset="/media/tmp/test1.jpg">'
                .'<source type="image/webp" data-srcset="/media/tmp/test1.webp">'
                .'<img data-src="/media/tmp/test1.jpg" loading="lazy" />'
                .'</picture>'
                .'</div>',
            ],
            [
                '<div>'
                .'<img src="/media/tmp/test1.jpg" loading="lazy"/>'
                .'</div>',
                '<div>'
                .'<picture>'
                .'<source type="image/jpeg" srcset="/media/tmp/test1.jpg">'
                .'<source type="image/webp" srcset="/media/tmp/test1.webp">'
                .'<img src="/media/tmp/test1.jpg" loading="lazy"/>'
                .'</picture>'
                .'</div>',
            ],
            [
                '<div>'
                .'<img src="/media/tmp/test1.jpg" loading="eager"/>'
                .'</div>',
                '<div>'
                .'<picture>'
                .'<source type="image/jpeg" srcset="/media/tmp/test1.jpg">'
                .'<source type="image/webp" srcset="/media/tmp/test1.webp">'
                .'<img src="/media/tmp/test1.jpg" loading="eager"/>'
                .'</picture>'
                .'</div>',
            ],
            [
                '<div>'
                .'<img src="/media/tmp/test1.jpg" fetchpriority="high"/>'
                .'</div>',
                '<div>'
                .'<picture>'
                .'<source type="image/jpeg" srcset="/media/tmp/test1.jpg">'
                .'<source type="image/webp" srcset="/media/tmp/test1.webp">'
                .'<img src="/media/tmp/test1.jpg" fetchpriority="high"/>'
                .'</picture>'
                .'</div>',
            ],
            [
                '<div style="background-image: url(/media/tmp/test1.jpg);"></div>',
                '<div style="background-image: url(/media/tmp/test1.jpg);"></div>',
            ],
            [
                '<div>'
                .'<img src="/media/tmp/test1.jpg"/>'
                .'<img src="/media/tmp/test2.jpg"/>'
                .'</div>',
                '<div>'
                .'<picture>'
                .'<source type="image/jpeg" srcset="/media/tmp/test1.jpg">'
                .'<source type="image/webp" srcset="/media/tmp/test1.webp">'
                .'<img src="/media/tmp/test1.jpg" loading="lazy" />'
                .'</picture>'
                .'<picture>'
                .'<source type="image/jpeg" srcset="/media/tmp/test2.jpg">'
                .'<source type="image/webp" srcset="/media/tmp/test2.webp">'
                .'<img src="/media/tmp/test2.jpg" loading="lazy" />'
                .'</picture>'
                .'</div>',
            ],
            [
                '<div>'
                .'<img src="/media/tmp/test1.jpg" @click="$nextTick(() => blah())"/>'
                .'<img src="/media/tmp/test2.jpg" @click="$nextTick(() => blah())"/>'
                .'</div>',
                '<div>'
                .'<picture>'
                .'<source type="image/jpeg" srcset="/media/tmp/test1.jpg">'
                .'<source type="image/webp" srcset="/media/tmp/test1.webp">'
                .'<img src="/media/tmp/test1.jpg" @click="$nextTick(() => blah())" loading="lazy" />'
                .'</picture>'
                .'<picture>'
                .'<source type="image/jpeg" srcset="/media/tmp/test2.jpg">'
                .'<source type="image/webp" srcset="/media/tmp/test2.webp">'
                .'<img src="/media/tmp/test2.jpg" @click="$nextTick(() => blah())" loading="lazy" />'
                .'</picture>'
                .'</div>',
            ],
            [
                '<div>'
                .'<img style="float: right;" src="/media/tmp/test1.jpg"/>'
                .'</div>',
                '<div>'
                .'<picture>'
                .'<source type="image/jpeg" srcset="/media/tmp/test1.jpg">'
                .'<source type="image/webp" srcset="/media/tmp/test1.webp">'
                .'<img style="float: right;" src="/media/tmp/test1.jpg" loading="lazy" />'
                .'</picture>'
                .'</div>',
            ],
            [
                "<script>var imgElement = '<img src=\"/media/tmp/test1.jpg\" />';</script>",
                "<script>var imgElement = '<img src=\"/media/tmp/test1.jpg\" />';</script>",
            ],
            [
                '<div>'
                .'<img :src="/media/tmp/test1.jpg"/>'
                .'</div>',
                '<div>'
                .'<picture>'
                .'<source type="image/jpeg" :srcset="/media/tmp/test1.jpg">'
                .'<source type="image/webp" :srcset="/media/tmp/test1.webp">'
                .'<img :src="/media/tmp/test1.jpg" loading="lazy" />'
                .'</picture>'
                .'</div>',
            ],
            [
                '<div>'
                .'<img :data-src="/media/tmp/test1.jpg"/>'
                .'</div>',
                '<div>'
                .'<picture>'
                .'<source type="image/jpeg" :data-srcset="/media/tmp/test1.jpg">'
                .'<source type="image/webp" :data-srcset="/media/tmp/test1.webp">'
                .'<img :data-src="/media/tmp/test1.jpg" loading="lazy" />'
                .'</picture>'
                .'</div>',
            ],
            [
                '<div>'
                .'<img src="/media/tmp/test1.jpg"/>'
                .'<img src="/media/tmp/test1.jpg"/>'
                .'<img src="/media/tmp/test2.jpg"/>'
                .'<img src="/media/tmp/test1.jpg"/>'
                .'</div>',
                '<div>'
                .str_repeat(self::getExpectedPicture('test1'), 2)
                .self::getExpectedPicture('test2')
                .self::getExpectedPicture('test1')
                .'</div>',
            ],
            [
                '<div>'
                .'<img src="/media/tmp/test1.jpg"/>'
                .'<img src="data:image/gif;base64,foobar"/>'
                .'<img src="https://example.com/test1.jpg"/>'
                .'<img src="/media/tmp/test1.gif"/>'
                .'<img src="/media/tmp/test2.jpg"/>'
                .'</div>',
                '<div>'
                .self::getExpectedPicture('test1')
                .'<img src="data:image/gif;base64,foobar"/>'
                .'<img src="https://example.com/test1.jpg"/>'
                .'<img src="/media/tmp/test1.gif"/>'
                .self::getExpectedPicture('test2')
                .'</div>',
            ],
            [
                '<div>'
                .'<picture><source type="image/webp" srcset="/media/tmp/test1.webp"><img src="/media/tmp/test1.jpg"/></picture>'
                .'<img src="/media/tmp/test2.jpg"/>'
                .'</div>',
                '<div>'
                .'<picture><source type="image/webp" srcset="/media/tmp/test1.webp"><img src="/media/tmp/test1.jpg"/></picture>'
                .self::getExpectedPicture('test2')
                .'</div>',
            ],
        ];
    }

    /**
     * @param string $imageTag
     * @param bool $expectPicture
     * @return void
     * @dataProvider getXssPayloads
     * @magentoAppArea frontend
     * @magentoConfigFixture current_store yireo_nextgenimages/settings/enabled 1
     */
    public function testXssPayloadsAreNotRendered(string $imageTag, bool $expectPicture)
    {
        $om = ObjectManager::getInstance();
        $mediaFolder = $this->copyXssImages();

        $convertorListing = $om->create(ConvertorListing::class, [
            'convertors' => [$om->create(FakeWebpConvertor::class)],
        ]);
        $imageCollector = $om->create(ImageCollector::class, ['convertorListing' => $convertorListing]);
        $htmlReplacer = $om->create(HtmlReplacer::class, ['imageCollector' => $imageCollector]);

        try {
            $html = '<div>'.str_replace('{url}', self::XSS_IMAGE_URL, $imageTag).'</div>';
            $resultingHtml = $htmlReplacer->replace($html);
        } finally {
            $om->get(File::class)->deleteDirectory($mediaFolder);
        }

        $this->assertStringNotContainsString('<svg', $resultingHtml);
        if ($expectPicture) {
            $this->assertStringContainsString('<picture', $resultingHtml);
            $this->assertStringContainsString('<source type="image/webp"', $resultingHtml);
        }
    }

    public function getXssPayloads(): array
    {
        return [
            'sizes' => [
                '<img src="{url}" sizes="100vw&quot;&gt;&lt;svg/onload=alert(1)&gt;">',
                true,
            ],
            'media' => [
                '<img src="{url}" media="all&quot;&gt;&lt;svg/onload=alert(1)&gt;">',
                true,
            ],
            'srcset descriptor' => [
                '<img src="{url}" srcset="{url} 1x&quot;&gt;&lt;svg onload=alert(1)&gt;">',
                false,
            ],
            'srcset with one invalid descriptor' => [
                '<img src="{url}" srcset="{url} 1x, {url} 2x&quot;&gt;&lt;svg onload=alert(1)&gt;">',
                true,
            ],
        ];
    }

    private function copyXssImages(): string
    {
        $om = ObjectManager::getInstance();
        $mediaFolder = dirname($om->get(UrlConvertor::class)->getFilenameFromUrl(self::XSS_IMAGE_URL));

        $fileDriver = $om->get(File::class);
        if ($fileDriver->isDirectory($mediaFolder)) {
            $fileDriver->deleteDirectory($mediaFolder);
        }

        $fileDriver->createDirectory($mediaFolder);
        copy(__DIR__.'/../fixtures/images/test1.jpg', $mediaFolder.'/test1.jpg');

        return $mediaFolder;
    }

    private static function getExpectedPicture(string $name): string
    {
        return '<picture>'
            .'<source type="image/jpeg" srcset="/media/tmp/'.$name.'.jpg">'
            .'<source type="image/webp" srcset="/media/tmp/'.$name.'.webp">'
            .'<img src="/media/tmp/'.$name.'.jpg" loading="lazy" />'
            .'</picture>';
    }

    private function copyImages(): void
    {
        $images = [];
        $images[] = 'test1.jpg';
        $images[] = 'test2.jpg';
        $images[] = 'test1.webp';
        $images[] = 'test2.webp';

        $om = ObjectManager::getInstance();

        $directoryList = $om->get(DirectoryList::class);
        $fixtureFolder = __DIR__.'/../fixtures/images/';

        $tmpFolder = $directoryList->getRoot().'/pub/media/tmp';
        $fileDriver = $om->get(File::class);
        $fileDriver->createDirectory($tmpFolder);

        foreach ($images as $image) {
            copy($fixtureFolder.'/'.$image, $tmpFolder.'/'.$image);
        }
    }
}
