<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\Config;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Yireo\IntegrationTestHelper\Test\Integration\AbstractTestCase;
use Yireo\NextGenImages\Config\Config;
use Yireo\NextGenImages\Util\UrlConvertor;

class ConfigTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $mediaDirectory = $this->objectManager->get(Filesystem::class)->getDirectoryWrite(DirectoryList::MEDIA);
        $mediaDirectory->create('nextgenimages');
        $mediaDirectory->create('nextgenimages-custom');
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture current_store web/unsecure/base_url https://example.com/
     * @magentoConfigFixture current_store web/secure/base_url https://example.com/
     */
    public function testDefaultCacheDirectoryPathIsInsideMediaFolder()
    {
        $this->assertCacheDirectoryMapsToMediaUrl('nextgenimages/');
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture current_store web/unsecure/base_url https://example.com/
     * @magentoConfigFixture current_store web/secure/base_url https://example.com/
     * @magentoConfigFixture current_store yireo_nextgenimages/settings/cache_directory media/nextgenimages-custom
     */
    public function testCustomCacheDirectoryPathIsInsideMediaFolder()
    {
        $this->assertCacheDirectoryMapsToMediaUrl('nextgenimages-custom/');
    }

    private function assertCacheDirectoryMapsToMediaUrl(string $expectedMediaSubPath): void
    {
        $directoryList = $this->objectManager->get(DirectoryList::class);
        $mediaPath = rtrim($directoryList->getPath(DirectoryList::MEDIA), '/');

        $config = $this->objectManager->create(Config::class);
        $cacheDirectoryPath = $config->getCacheDirectoryPath();
        $this->assertSame($mediaPath.'/'.$expectedMediaSubPath, $cacheDirectoryPath);

        $urlConvertor = $this->objectManager->create(UrlConvertor::class);
        $url = $urlConvertor->getUrlFromFilename($cacheDirectoryPath.'example.webp');
        $this->assertSame('https://example.com/media/'.$expectedMediaSubPath.'example.webp', $url);
    }
}
