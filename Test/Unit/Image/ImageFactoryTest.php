<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Image;

use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Yireo\NextGenImages\Image\Image;
use Yireo\NextGenImages\Image\ImageFactory;
use Yireo\NextGenImages\Util\UrlConvertor;

class ImageFactoryTest extends TestCase
{
    public function testCreateFromPath()
    {
        $urlConvertor = $this->getUrlConvertor();
        $objectManager = $this->getObjectManager();
        $objectManager->method('create')->willReturn(new Image('/tmp/pub/foo/bar.jpg', '/foo/bar.jpg'));

        $imageFactory = new ImageFactory($objectManager, $urlConvertor);
        $image = $imageFactory->createFromPath('/tmp/pub/foo/bar.jpg');

        $this->assertInstanceOf(Image::class, $image);
        $this->assertEquals('/tmp/pub/foo/bar.jpg', $image->getPath());
    }

    public function testCreateFromUrl()
    {
        $urlConvertor = $this->getUrlConvertor();
        $urlConvertor->method('getFilenameFromUrl')->willReturn('/tmp/pub/foo/bar.jpg');
        $urlConvertor->method('getUrlFromFilename')->willReturn('/foo/bar.jpg');
        $objectManager = $this->getObjectManager();
        $objectManager->method('create')->willReturn(new Image('/tmp/pub/foo/bar.jpg', '/foo/bar.jpg'));

        $imageFactory = new ImageFactory($objectManager, $urlConvertor);
        $image = $imageFactory->createFromUrl('/foo/bar.jpg');

        $this->assertInstanceOf(Image::class, $image);
        $this->assertEquals('/tmp/pub/foo/bar.jpg', $image->getPath());
        $this->assertEquals('/foo/bar.jpg', $image->getUrl());
    }

    public function testCreateFromUrlWithMultipleUrls()
    {
        $urlConvertor = $this->getUrlConvertor();
        $urlConvertor->method('getFilenameFromUrl')->willReturn('/tmp/pub/foo/bar.jpg');
        $urlConvertor->method('getUrlFromFilename')->willReturn('/foo/bar.jpg');
        $objectManager = $this->getObjectManager();
        $objectManager->method('create')->willReturn(
            new Image('/tmp/pub/foo/bar.jpg', '/foo/bar.jpg', '/foo/bar.jpg,/baz/qux.jpg 500w')
        );

        $imageFactory = new ImageFactory($objectManager, $urlConvertor);
        $image = $imageFactory->createFromUrl(['/foo/bar.jpg', '500w' => '/baz/qux.jpg']);

        $this->assertInstanceOf(Image::class, $image);
        $this->assertEquals('/tmp/pub/foo/bar.jpg', $image->getPath());
        $this->assertEquals('/foo/bar.jpg', $image->getUrl());
        $this->assertEquals('/foo/bar.jpg,/baz/qux.jpg 500w', $image->getSrcSet());
    }

    public function testCreateFromUrlWithStringKeys()
    {
        $urlConvertor = $this->getUrlConvertor();
        $urlConvertor->method('getFilenameFromUrl')->willReturn('/tmp/pub/img1.png');
        $urlConvertor->method('getUrlFromFilename')->willReturn('http://cdn.site.com/img1.png');
        $objectManager = $this->getObjectManager();
        $objectManager->method('create')->willReturn(
            new Image('/tmp/pub/img1.png', 'http://cdn.site.com/img1.png', 'http://cdn.site.com/img1.png 480w,http://cdn.site.com/img2.png 768w')
        );

        $imageFactory = new ImageFactory($objectManager, $urlConvertor);
        $image = $imageFactory->createFromUrl([
            '480w' => 'http://cdn.site.com/img1.png',
            '768w' => 'http://cdn.site.com/img2.png'
        ]);

        $this->assertInstanceOf(Image::class, $image);
        $this->assertEquals('/tmp/pub/img1.png', $image->getPath());
        $this->assertEquals('http://cdn.site.com/img1.png', $image->getUrl());
        $this->assertEquals('http://cdn.site.com/img1.png 480w,http://cdn.site.com/img2.png 768w', $image->getSrcSet());
    }

    /**
     * @dataProvider provideSrcSetUrls
     */
    #[DataProvider('provideSrcSetUrls')]
    public function testSrcSetKeysAreFiltered(array $urls, string $expectedSrcSet)
    {
        $urlConvertor = $this->getUrlConvertor();
        $urlConvertor->method('getFilenameFromUrl')->willReturn('/tmp/pub/a.jpg');
        $objectManager = $this->getObjectManager();
        $objectManager->expects($this->once())
            ->method('create')
            ->with(Image::class, $this->callback(function (array $arguments) use ($expectedSrcSet) {
                $this->assertSame($expectedSrcSet, $arguments['srcSet']);
                return true;
            }))
            ->willReturn(new Image('/tmp/pub/a.jpg', '/a.jpg'));

        (new ImageFactory($objectManager, $urlConvertor))->createFromUrl($urls);
    }

    public static function provideSrcSetUrls(): array
    {
        return [
            'valid keys' => [
                ['/a.jpg', '1.5x' => '/b.jpg', '480w' => '/c.jpg'],
                '/a.jpg,/b.jpg 1.5x,/c.jpg 480w',
            ],
            'invalid keys' => [
                [
                    '1x' => '/a.jpg',
                    '2x"><svg onload=alert(1)>' => '/b.jpg',
                    '3y' => '/c.jpg',
                    'x' => '/d.jpg',
                    '1x 2x' => '/e.jpg',
                    '4x' => '/f.jpg',
                ],
                '/a.jpg 1x,/f.jpg 4x',
            ],
        ];
    }

    private function getObjectManager(): MockObject
    {
        return $this->getMockBuilder(ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getUrlConvertor(): MockObject
    {
        return $this->getMockBuilder(UrlConvertor::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
