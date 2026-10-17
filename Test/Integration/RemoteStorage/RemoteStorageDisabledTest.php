<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\RemoteStorage;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\RemoteStorage\Model\Config as RemoteStorageConfig;
use Yireo\IntegrationTestHelper\Test\Integration\AbstractTestCase;
use Yireo\NextGenImages\Image\ImageFactory;
use Yireo\NextGenImages\Test\Integration\Stub\Convertor\Webp2LikeFakeConvertor;
use Yireo\NextGenImages\Test\Integration\Stub\Convertor\FakeWebpConvertor;
use Yireo\NextGenImages\Util\UrlConvertor;

/**
 * Regression test: without remote storage, the remote storage plugins should not change anything
 *
 * @magentoAppArea frontend
 */
class RemoteStorageDisabledTest extends AbstractTestCase
{
    private const TEST_FOLDER = 'nextgenimages-local-storage-test';

    private string $mediaFolder;
    private string $tmpFolder;

    protected function setUp(): void
    {
        parent::setUp();
        FakeWebpConvertor::reset();

        $directoryList = $this->objectManager->get(DirectoryList::class);
        $this->mediaFolder = $directoryList->getPath(DirectoryList::MEDIA).'/'.self::TEST_FOLDER;
        $this->tmpFolder = $directoryList->getPath(DirectoryList::TMP).'/media/'.self::TEST_FOLDER;

        $this->removeTestFiles();
        mkdir($this->mediaFolder, 0777, true);
        copy(__DIR__.'/../fixtures/images/test1.jpg', $this->mediaFolder.'/test1.jpg');
        touch($this->mediaFolder.'/test1.jpg', time() - 100);
    }

    protected function tearDown(): void
    {
        $this->removeTestFiles();
        FakeWebpConvertor::reset();
        parent::tearDown();
    }

    public function testRemoteStorageIsDisabled(): void
    {
        $this->assertFalse($this->objectManager->get(RemoteStorageConfig::class)->isEnabled());
    }

    public function testUrlConversionIsUnchanged(): void
    {
        $urlConvertor = $this->objectManager->get(UrlConvertor::class);
        $this->assertSame(
            'http://localhost/media/'.self::TEST_FOLDER.'/test1.jpg',
            $urlConvertor->getUrlFromFilename($this->mediaFolder.'/test1.jpg')
        );
    }

    /**
     * @dataProvider getConvertorClasses
     */
    public function testConvertsImageNextToSource(string $convertorClass): void
    {
        $imageFactory = $this->objectManager->get(ImageFactory::class);
        $image = $imageFactory->createFromPath($this->mediaFolder.'/test1.jpg');

        $convertor = $this->objectManager->create($convertorClass);
        $targetImage = $convertor->convertImage($image);

        $this->assertSame($this->mediaFolder.'/test1.webp', $targetImage->getPath());
        $this->assertSame('http://localhost/media/'.self::TEST_FOLDER.'/test1.webp', $targetImage->getUrl());
        $this->assertFileExists($this->mediaFolder.'/test1.webp');
        $this->assertDirectoryDoesNotExist($this->tmpFolder);
        $this->assertSame(1, FakeWebpConvertor::$calls);
    }

    public static function getConvertorClasses(): array
    {
        return [
            'simple convertor' => [FakeWebpConvertor::class],
            'convertor that verifies target' => [Webp2LikeFakeConvertor::class],
        ];
    }

    private function removeTestFiles(): void
    {
        $fileDriver = new FileDriver();
        foreach ([$this->mediaFolder, $this->tmpFolder] as $folder) {
            if (is_dir($folder)) {
                $fileDriver->deleteDirectory($folder);
            }
        }
    }
}
