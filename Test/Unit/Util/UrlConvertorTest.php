<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Util;

use Magento\Framework\Escaper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Asset\File\NotFoundException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yireo\NextGenImages\Util\UrlConvertor;

class UrlConvertorTest extends TestCase
{
    /**
     * @test \Yireo\NextGenImages\Image\UrlConvertor::isLocal()
     * @throws NoSuchEntityException
     */
    public function testIsLocal()
    {
        $urlConvertor = new UrlConvertor(
            $this->getStoreManagerMock(),
            $this->getDirectoryListMock(),
            $this->getEscaperMock(),
            $this->createMock(File::class)
        );

        $this->assertTrue($urlConvertor->isLocal('/media/test.png'));
        $this->assertTrue($urlConvertor->isLocal('http://localhost/media/test.png'));
        $this->assertTrue($urlConvertor->isLocal('http://cdn/test.png'));
        $this->assertFalse($urlConvertor->isLocal('http://foobar/media/test.png'));
        $this->assertFalse($urlConvertor->isLocal('http://foobar/?http://localhost/media/test.png'));
    }

    /**
     * @dataProvider provideUrlsOutsidePublicFolder
     */
    #[DataProvider('provideUrlsOutsidePublicFolder')]
    public function testGetFilenameFromUrlRejectsPathsOutsidePublicFolder(string $url)
    {
        $urlConvertor = new UrlConvertor(
            $this->getStoreManagerMock(),
            $this->getDirectoryListMock(),
            $this->getEscaperMock(),
            $this->createMock(File::class)
        );

        $this->expectException(NotFoundException::class);
        $urlConvertor->getFilenameFromUrl($url);
    }

    public static function provideUrlsOutsidePublicFolder(): array
    {
        return [
            'relative traversal' => ['/../../app/etc/secret.jpg'],
            'traversal in middle' => ['/media/../../app/etc/secret.jpg'],
            'base URL traversal' => ['http://localhost/../app/etc/secret.jpg'],
            'media URL traversal' => ['http://cdn/../../../etc/secret.png'],
            'backslash traversal' => ['/media/..\\..\\app/secret.jpg'],
            'foreign host with local URL in query' => ['http://foobar/?http://localhost/media/test.png'],
            'alpine expression with traversal' => ['../../app/image.img.jpg'],
        ];
    }

    public function testGetFilenameFromUrlAllowsTraversalInsidePublicFolder()
    {
        $urlConvertor = new UrlConvertor(
            $this->getStoreManagerMock(),
            $this->getDirectoryListMock(),
            $this->getEscaperMock(),
            $this->createMock(File::class)
        );

        $this->assertSame(
            '/var/www/html/pub/media/catalog/../sample.png',
            $urlConvertor->getFilenameFromUrl('http://localhost/media/catalog/../sample.png')
        );
    }

    /**
     * \Yireo\NextGenImages\Image\UrlConvertor::getFilenameFromUrl()
     */
    public function testGetFilenameFromUrl()
    {
        $urlConvertor = new UrlConvertor(
            $this->getStoreManagerMock(),
            $this->getDirectoryListMock(),
            $this->getEscaperMock(),
            $this->createMock(File::class)
        );

        $this->assertSame(
            '/var/www/html/pub/media/sample.png',
            $urlConvertor->getFilenameFromUrl('http://cdn/sample.png')
        );

        $this->assertSame(
            '/var/www/html/pub/sample.webp',
            $urlConvertor->getFilenameFromUrl('http://localhost/sample.webp')
        );

        $this->assertSame(
            '/var/www/html/pub/static/sample.jpg',
            $urlConvertor->getFilenameFromUrl('http://localhost/static/version432423423/sample.jpg')
        );
    }

    /**
     * @param string $baseUrl
     * @return UrlInterface
     */
    private function getUrlMock(string $baseUrl): UrlInterface
    {
        $urlMock = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $urlMock->method('getBaseUrl')->willReturn($baseUrl);
        return $urlMock;
    }

    /**
     * @return StoreManagerInterface
     */
    private function getStoreManagerMock(): StoreManagerInterface
    {
        $storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock->method('getBaseUrl')
            ->will(
                $this->returnValueMap([
                    [UrlInterface::URL_TYPE_WEB, null, 'http://localhost/'],
                    [UrlInterface::URL_TYPE_MEDIA, null, 'http://cdn/'],
                    [UrlInterface::URL_TYPE_STATIC, null, 'http://static/']
                ]));

        $storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $storeManagerMock->method('getStores')->willReturn([$storeMock]);
        $storeManagerMock->method('getStore')->willReturn($storeMock);
        return $storeManagerMock;
    }

    /**
     * @return DirectoryList
     */
    private function getDirectoryListMock(): DirectoryList
    {
        $directoryListMock = $this->getMockBuilder(DirectoryList::class)
            ->disableOriginalConstructor()
            ->getMock();

        $directoryListMock->method('getRoot')->willReturn('/var/www/html');
        $directoryListMock->method('getPath')->willReturn('/var/www/html/pub/media');

        return $directoryListMock;
    }

    /**
     * @return Escaper
     */
    private function getEscaperMock(): Escaper
    {
        $escaperMock = $this->getMockBuilder(Escaper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $escaperMock->expects($this->any())
            ->method('escapeHtml')
            ->will($this->returnArgument(0));

        return $escaperMock;
    }
}
