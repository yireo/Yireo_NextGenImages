<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\PageCache\Model\DepersonalizeChecker;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Yireo\NextGenImages\Config\Config;
use Yireo\NextGenImages\Test\Unit\AbstractTestCase;

class ConfigTest extends AbstractTestCase
{
    private string $root = '';
    private string $originalCwd = '';

    protected function setUp(): void
    {
        $this->originalCwd = (string)getcwd();
        $this->root = sys_get_temp_dir().'/nextgenimages-config-'.uniqid();
        mkdir($this->root.'/pub/media/custom', 0777, true);
        mkdir($this->root.'/pub/media/nextgenimages', 0777, true);
    }

    protected function tearDown(): void
    {
        chdir($this->originalCwd);
        exec('rm -rf '.escapeshellarg($this->root));
    }

    /**
     * @dataProvider getCacheDirectoryValues
     */
    public function testGetCacheDirectoryPath(?string $value, string $cwd, string $expectedRelativePath)
    {
        chdir($this->root.$cwd);

        $result = $this->getConfig($value)->getCacheDirectoryPath();

        $this->assertSame($this->root.'/pub/'.$expectedRelativePath, $result);
        $this->assertStringNotContainsString('//', $result);
        $this->assertStringEndsWith('/', $result);
    }

    public static function getCacheDirectoryValues(): array
    {
        return [
            'null value' => [null, '', 'media/nextgenimages/'],
            'empty value' => ['', '', 'media/nextgenimages/'],
            'default value' => ['media/nextgenimages/', '', 'media/nextgenimages/'],
            'custom directory, cwd is root (CLI)' => ['media/custom/', '', 'media/custom/'],
            'custom directory, cwd is pub (web)' => ['media/custom/', '/pub', 'media/custom/'],
            'custom directory without trailing slash' => ['media/custom', '', 'media/custom/'],
            'custom directory with leading slash' => ['/media/custom/', '', 'media/custom/'],
            'custom directory surrounded by whitespace' => [" media/custom/ \n", '', 'media/custom/'],
            'custom directory that does not exist yet' => ['media/notyet/', '', 'media/notyet/'],
            'directory traversal' => ['../var/', '', 'media/nextgenimages/'],
            'only slashes' => ['///', '', 'media/nextgenimages/'],
        ];
    }

    public function testGetCacheDirectoryPathWithAbsolutePathInsidePub()
    {
        chdir($this->root);
        $result = $this->getConfig($this->root.'/pub/media/custom')->getCacheDirectoryPath();
        $this->assertSame($this->root.'/pub/media/custom/', $result);
    }

    public function testGetCacheDirectoryPathDoesNotDependOnWorkingDirectory()
    {
        chdir($this->root);
        $fromCli = $this->getConfig('media/custom/')->getCacheDirectoryPath();

        chdir($this->root.'/pub');
        $fromWeb = $this->getConfig('media/custom/')->getCacheDirectoryPath();

        $this->assertSame($fromWeb, $fromCli);
    }

    private function getConfig(?string $value): Config
    {
        $scopeConfig = $this->getMagentoMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(
            fn (string $path) => $path === 'yireo_nextgenimages/settings/cache_directory' ? $value : null
        );

        $storeManager = $this->getMagentoMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($this->getMagentoMock(StoreInterface::class));

        $directoryList = $this->getMagentoMock(DirectoryList::class);
        $directoryList->method('getRoot')->willReturn($this->root);
        $directoryList->method('getPath')->willReturnMap([
            [DirectoryList::PUB, $this->root.'/pub'],
        ]);

        return new Config(
            $scopeConfig,
            $this->getMagentoMock(DepersonalizeChecker::class),
            $storeManager,
            $directoryList
        );
    }
}
