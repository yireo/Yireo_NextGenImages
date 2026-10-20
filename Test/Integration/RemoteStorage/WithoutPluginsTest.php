<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\RemoteStorage;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Yireo\NextGenImages\Exception\ConvertorException;
use Yireo\NextGenImages\Image\Image;
use Yireo\NextGenImages\Image\ImageFactory;
use Yireo\NextGenImages\Image\TargetImageFactory;
use Yireo\NextGenImages\Logger\Debugger;
use Yireo\NextGenImages\Test\Integration\Stub\Convertor\FakeWebpConvertor;
use Yireo\NextGenImages\Util\File;
use Yireo\NextGenImages\Util\UrlConvertor;

/**
 * Illustrates why remote storage support is needed: without the remote storage plugins (so, the original classes
 * only), an image that only exists on remote storage is never converted.
 *
 * @magentoAppArea frontend
 */
class WithoutPluginsTest extends AbstractRemoteStorageTestCase
{
    public function testImageOnRemoteStorageIsNotFound(): void
    {
        $this->addRemoteImage('test1.jpg');
        $file = $this->createOriginalFile();
        $localPath = $this->getLocalMediaPath('test1.jpg');

        $this->assertFalse($file->fileExists($localPath));
        $this->assertFalse(
            $file->needsConversion($localPath, $this->getLocalMediaPath('test1.webp')),
            'Without remote storage support, the conversion is silently skipped'
        );
    }

    public function testImageOnRemoteStorageIsNotConverted(): void
    {
        $relativePath = $this->addRemoteImage('test1.jpg');
        $image = new Image($this->getLocalMediaPath('test1.jpg'), 'http://localhost/'.$relativePath);
        $convertor = new FakeWebpConvertor($this->objectManager->get(TargetImageFactory::class));

        try {
            $convertor->convertImage($image);
            $this->fail('Conversion should not be possible without a local source image');
        } catch (ConvertorException $e) {
        }

        $this->assertSame([false], FakeWebpConvertor::$sourceExistedLocally);
        $this->assertFileDoesNotExist($this->getRemotePath('media/'.self::TEST_FOLDER.'/test1.webp'));
    }

    private function createOriginalFile(): File
    {
        return new File(
            $this->objectManager->get(Filesystem::class),
            $this->objectManager->get(Debugger::class),
            $this->objectManager->get(UrlConvertor::class),
            $this->objectManager->get(TargetImageFactory::class),
            $this->objectManager->get(ImageFactory::class)
        );
    }

    private function getLocalMediaPath(string $filename): string
    {
        $mediaPath = $this->objectManager->get(DirectoryList::class)->getPath(DirectoryList::MEDIA);
        return $mediaPath.'/'.self::TEST_FOLDER.'/'.$filename;
    }
}
