<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\RemoteStorage;

use Yireo\NextGenImages\Image\Image;
use Yireo\NextGenImages\Image\ImageFactory;
use Yireo\NextGenImages\Test\Integration\Stub\Convertor\FakeWebpConvertor;
use Yireo\NextGenImages\Test\Integration\Stub\Convertor\Webp2LikeFakeConvertor;
use Yireo\NextGenImages\Util\UrlConvertor;

/**
 * @magentoAppArea frontend
 */
class RemoteStorageSimulationTest extends AbstractRemoteStorageTestCase
{
    private const SOURCE = 'media/'.self::TEST_FOLDER.'/test1.jpg';
    private const TARGET = 'media/'.self::TEST_FOLDER.'/test1.webp';

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableRemoteStorage();
        $this->addRemoteImage('test1.jpg');
    }

    public function testImageOnRemoteStorageIsNotAvailableLocally(): void
    {
        $this->assertFileExists($this->getRemotePath(self::SOURCE));
        $this->assertFileDoesNotExist($this->getTmpPath(self::SOURCE));
    }

    public function testUrlOfTmpImageIsMediaUrl(): void
    {
        $urlConvertor = $this->objectManager->get(UrlConvertor::class);
        $this->assertSame(
            'http://localhost/'.self::SOURCE,
            $urlConvertor->getUrlFromFilename($this->getTmpPath(self::SOURCE))
        );
    }

    public function testConvertsImageThatOnlyExistsOnRemoteStorage(): void
    {
        $targetImage = $this->convert();

        $this->assertSame(1, FakeWebpConvertor::$calls);
        $this->assertSame([true], FakeWebpConvertor::$sourceExistedLocally, 'Source should be copied locally');
        $this->assertSame($this->getTmpPath(self::TARGET), $targetImage->getPath());
        $this->assertSame('http://localhost/'.self::TARGET, $targetImage->getUrl());
        $this->assertFileExists($this->getRemotePath(self::TARGET), 'Target should be moved to remote storage');
        $this->assertFileDoesNotExist($this->getTmpPath(self::TARGET), 'Target should not stay in var/tmp');
    }

    public function testRemovesTmpCopyOfSourceImageAfterwards(): void
    {
        $this->convert();
        $this->assertFileExists($this->getTmpPath(self::SOURCE));

        $this->tmpFileCopier->__destruct();
        $this->assertFileDoesNotExist($this->getTmpPath(self::SOURCE));
        $this->assertFileExists($this->getRemotePath(self::SOURCE), 'Remote source should never be removed');
    }

    public function testSkipsConversionWhenTargetOnRemoteStorageIsUpToDate(): void
    {
        $firstImage = $this->convert();
        $secondImage = $this->convert();

        $this->assertSame(1, FakeWebpConvertor::$calls);
        $this->assertSame($firstImage->getPath(), $secondImage->getPath());
    }

    public function testConvertsAgainWhenSourceOnRemoteStorageIsNewer(): void
    {
        $this->convert();
        touch($this->getRemotePath(self::TARGET), time() - 200);

        $this->convert();
        $this->assertSame(2, FakeWebpConvertor::$calls);
    }

    /**
     * Yireo_Webp2 checks whether the target image exists right after conversion. At that moment the target
     * only exists in var/tmp, while the File plugin only looks at remote storage.
     */
    public function testWorksWithConvertorThatVerifiesTargetAfterConversion(): void
    {
        $targetImage = $this->convert(Webp2LikeFakeConvertor::class);

        $this->assertSame($this->getTmpPath(self::TARGET), $targetImage->getPath());
        $this->assertFileExists($this->getRemotePath(self::TARGET));
    }

    private function convert(string $convertorClass = FakeWebpConvertor::class): Image
    {
        $imageFactory = $this->objectManager->get(ImageFactory::class);
        $image = $imageFactory->createFromPath($this->getTmpPath(self::SOURCE));

        $convertor = $this->objectManager->create($convertorClass);
        return $convertor->convertImage($image);
    }
}
