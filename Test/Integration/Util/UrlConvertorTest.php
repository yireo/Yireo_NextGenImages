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
    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture current_store web/unsecure/base_url https://example.com/
     * @magentoConfigFixture current_store web/secure/base_url https://example.com/
     * @return void
     */
    public function testGetUrlFromFilename()
    {
        $directoryList = $this->objectManager->get(DirectoryList::class);
        $root = $directoryList->getRoot();

        $urlConvertor = $this->objectManager->create(UrlConvertor::class);

        $filename = $urlConvertor->getFilenameFromUrl('https://example.com/static/foobar.jpg');
        $this->assertSame($root . '/pub/static/foobar.jpg', $filename);

        $filename = $urlConvertor->getFilenameFromUrl('https://example.com/static/version1234/foobar.jpg');
        $this->assertSame($root . '/pub/static/foobar.jpg', $filename);
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture current_store web/unsecure/base_url https://example.com/
     * @magentoConfigFixture current_store web/secure/base_url https://example.com/
     * @return void
     */
    public function testGetFilenameFromUrl()
    {
        $directoryList = $this->objectManager->get(DirectoryList::class);
        $root = $directoryList->getRoot();

        $urlConvertor = $this->objectManager->create(UrlConvertor::class);
        $filename = $urlConvertor->getUrlFromFilename($root . '/pub/static/foobar.jpg');

        $this->assertSame('https://example.com/static/foobar.jpg', $filename);
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
