<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\RemoteStorage;

use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Yireo\NextGenImages\Image\Image;
use Yireo\NextGenImages\Image\ImageFactory;
use Yireo\NextGenImages\Test\Integration\Stub\Convertor\SrcSetFakeWebpConvertor;
use Yireo\NextGenImages\Util\UrlConvertor;

/**
 * @magentoAppArea frontend
 */
class RemoteStorageSrcSetTest extends AbstractRemoteStorageTestCase
{
    private const SOURCE_URL = 'http://localhost/media/'.self::TEST_FOLDER.'/test1.jpg';
    private const SRCSET_SOURCE_URL = 'http://localhost/media/'.self::TEST_FOLDER.'/test2.jpg';
    private const TARGET_URL = 'http://localhost/media/'.self::TEST_FOLDER.'/test1.webp';
    private const SRCSET_TARGET_URL = 'http://localhost/media/'.self::TEST_FOLDER.'/test2.webp';

    private string $source;
    private string $srcSetSource;

    protected function setUp(): void
    {
        parent::setUp();
        SrcSetFakeWebpConvertor::reset();
        $this->enableRemoteStorage();
        $this->source = $this->addRemoteImageForUrl(self::SOURCE_URL, 'test1.jpg');
        $this->srcSetSource = $this->addRemoteImageForUrl(self::SRCSET_SOURCE_URL, 'test2.jpg');
    }

    protected function tearDown(): void
    {
        $folder = dirname($this->getTmpPath($this->source));
        if (is_dir($folder)) {
            (new FileDriver())->deleteDirectory($folder);
        }

        SrcSetFakeWebpConvertor::reset();
        parent::tearDown();
    }

    public function testMovesConvertedSrcSetImagesToRemoteStorage(): void
    {
        $targetImage = $this->convert();

        $this->assertSame(
            [$this->getTmpPath($this->source), $this->getTmpPath($this->srcSetSource)],
            array_values(array_unique(SrcSetFakeWebpConvertor::$convertedPaths))
        );
        $this->assertSame(self::TARGET_URL.', '.self::SRCSET_TARGET_URL.' 2x', $targetImage->getSrcSet());

        foreach ([$this->source, $this->srcSetSource] as $source) {
            $target = preg_replace('/\.jpg$/', '.webp', $source);
            $this->assertFileExists($this->getRemotePath($target), 'Target should be moved to remote storage');
            $this->assertFileDoesNotExist($this->getTmpPath($target), 'Target should not stay in var/tmp');
        }

        $this->tmpFileCopier->__destruct();
        foreach ([$this->source, $this->srcSetSource] as $source) {
            $this->assertFileDoesNotExist($this->getTmpPath($source), 'Source should not stay in var/tmp');
            $this->assertFileExists($this->getRemotePath($source), 'Remote source should never be removed');
        }
    }

    public function testDoesNotConvertSrcSetImagesThatAreUpToDate(): void
    {
        $this->convert();
        SrcSetFakeWebpConvertor::reset();

        $targetImage = $this->convert();

        $this->assertSame([], SrcSetFakeWebpConvertor::$convertedPaths);
        $this->assertSame(self::TARGET_URL.', '.self::SRCSET_TARGET_URL.' 2x', $targetImage->getSrcSet());
    }

    /**
     * The integration framework sandboxes pub/media, so the path of a media URL is resolved via the UrlConvertor
     */
    private function addRemoteImageForUrl(string $url, string $filename): string
    {
        $tmpPath = $this->objectManager->get(UrlConvertor::class)->getFilenameFromUrl($url);
        $relativePath = substr($tmpPath, strlen($this->tmpRoot) + 1);

        $remotePath = $this->getRemotePath($relativePath);
        if (!is_dir(dirname($remotePath))) {
            mkdir(dirname($remotePath), 0777, true);
        }

        copy(__DIR__.'/../fixtures/images/'.$filename, $remotePath);
        touch($remotePath, time() - 100);

        return $relativePath;
    }

    private function convert(): Image
    {
        $imageFactory = $this->objectManager->get(ImageFactory::class);
        $image = $imageFactory->createFromUrl([self::SOURCE_URL, '2x' => self::SRCSET_SOURCE_URL]);

        $convertor = $this->objectManager->create(SrcSetFakeWebpConvertor::class);
        return $convertor->convertImage($image);
    }
}
