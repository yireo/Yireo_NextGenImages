<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\Util;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Yireo\NextGenImages\Block\PictureFactory;
use Yireo\NextGenImages\Config\Config;
use Yireo\NextGenImages\Convertor\ConvertorListing;
use Yireo\NextGenImages\Image\Image;
use Yireo\NextGenImages\Image\ImageCollector;
use Yireo\NextGenImages\Image\ImageFactory;
use Yireo\NextGenImages\Test\Integration\AbstractTestCase;
use Yireo\NextGenImages\Util\DomUtils;
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

        $returnMap = [
            [
                '/media/tmp/test1.jpg',
                [
                    new Image('/tmp/pub/media/tmp/test1.jpg', '/media/tmp/test1.jpg'),
                    new Image('/tmp/pub/media/tmp/test1.webp', '/media/tmp/test1.webp'),
                ],
            ],
            [
                '/media/tmp/test2.jpg',
                [
                    new Image('/tmp/pub/media/tmp/test2.jpg', '/media/tmp/test2.jpg'),
                    new Image('/tmp/pub/media/tmp/test2.webp', '/media/tmp/test2.webp'),
                ],
            ],
        ];

        $imageCollector = $this->getMagentoMock(ImageCollector::class);
        $imageCollector
            ->method('collect')
            ->will($this->returnValueMap($returnMap));

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
        ];
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
        @mkdir($tmpFolder);

        foreach ($images as $image) {
            copy($fixtureFolder.'/'.$image, $tmpFolder.'/'.$image);
        }
    }
}
