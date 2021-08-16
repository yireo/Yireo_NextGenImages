<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Image;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Yireo\NextGenImages\Image\UrlConvertor;

class UrlConvertorTest extends TestCase
{
    /**
     * @test \Yireo\NextGenImages\Image\UrlConvertor::isLocal()
     * @throws NoSuchEntityException
     */
    public function testIsLocal()
    {
        $urlConvertor = new UrlConvertor(
            $this->getUrlMock('http://localhost/'),
            $this->getStoreManagerMock('http://cdn/'),
            $this->getDirectoryListMock()
        );

        $this->assertTrue($urlConvertor->isLocal('/media/test.png'));
        $this->assertTrue($urlConvertor->isLocal('http://localhost/media/test.png'));
        $this->assertTrue($urlConvertor->isLocal('http://cdn/test.png'));
        $this->assertFalse($urlConvertor->isLocal('http://foobar/media/test.png'));
    }

    /**
     * \Yireo\NextGenImages\Image\UrlConvertor::getFilenameFromUrl()
     */
    public function testGetFilenameFromUrl()
    {
        $urlConvertor = new UrlConvertor(
            $this->getUrlMock('http://localhost/'),
            $this->getStoreManagerMock('http://cdn/'),
            $this->getDirectoryListMock()
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
     * @param string $mediaBaseUrl
     * @return StoreManagerInterface
     */
    private function getStoreManagerMock(string $mediaBaseUrl): StoreManagerInterface
    {
        $storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock->method('getBaseUrl')->willReturn($mediaBaseUrl);

        $storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

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
}
