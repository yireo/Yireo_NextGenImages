<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\Util;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\File\NotFoundException;
use Yireo\IntegrationTestHelper\Test\Integration\AbstractTestCase;
use Yireo\NextGenImages\Util\UrlConvertor;

class UrlConvertorTest extends AbstractTestCase
{
    public function testGetUrlFromFilename()
    {
        $directoryList = $this->objectManager->get(DirectoryList::class);
        $root = $directoryList->getRoot();

        $urlConvertor = $this->objectManager->create(UrlConvertor::class);

        $filename = $urlConvertor->getFilenameFromUrl('/static/foobar.jpg');
        $this->assertSame($root . '/pub/static/foobar.jpg', $filename);

        $filename = $urlConvertor->getFilenameFromUrl('/static/version1234/foobar.jpg');
        $this->assertSame($root . '/pub/static/foobar.jpg', $filename);
    }

    /**
     * @return void
     */
    public function testGetFilenameFromUrl()
    {
        $directoryList = $this->objectManager->get(DirectoryList::class);
        $root = $directoryList->getRoot();

        $urlConvertor = $this->objectManager->create(UrlConvertor::class);
        $filename = $urlConvertor->getUrlFromFilename($root . '/pub/static/foobar.jpg');

        $expectedUrl = 'http://localhost/static/foobar.jpg';

        $this->assertSame($expectedUrl, $filename);
    }

    public function testGetWrongFilenameFromUrl()
    {
        $directoryList = $this->objectManager->get(DirectoryList::class);
        $root = $directoryList->getRoot();
        $urlConvertor = $this->objectManager->create(UrlConvertor::class);

        $this->expectException(NotFoundException::class);
        $urlConvertor->getUrlFromFilename($root . '/../foobar.jpg');
    }
}