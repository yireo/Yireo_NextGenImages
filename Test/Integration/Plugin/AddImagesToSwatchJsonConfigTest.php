<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\Plugin;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Swatches\Block\Product\Renderer\Configurable;
use Yireo\IntegrationTestHelper\Test\Integration\AbstractTestCase;
use Yireo\NextGenImages\Convertor\ConvertorListing;
use Yireo\NextGenImages\Image\ImageCollector;
use Yireo\NextGenImages\Plugin\AddImagesToSwatchJsonConfig;
use Yireo\NextGenImages\Test\Integration\Stub\Convertor\FakeWebpConvertor;

/**
 * @magentoAppArea frontend
 */
class AddImagesToSwatchJsonConfigTest extends AbstractTestCase
{
    private const TEST_FOLDER = 'nextgenimages-swatch-test';

    private string $mediaFolder;

    protected function setUp(): void
    {
        parent::setUp();
        FakeWebpConvertor::reset();

        $directoryList = $this->objectManager->get(DirectoryList::class);
        $this->mediaFolder = $directoryList->getPath(DirectoryList::MEDIA).'/'.self::TEST_FOLDER;

        $this->removeTestFiles();
        mkdir($this->mediaFolder, 0777, true);
        copy(__DIR__.'/../fixtures/images/test1.jpg', $this->mediaFolder.'/test1.jpg');
    }

    protected function tearDown(): void
    {
        $this->removeTestFiles();
        FakeWebpConvertor::reset();
        parent::tearDown();
    }

    /**
     * @magentoAppArea frontend
     */
    public function testIfPluginIsEnabled(): void
    {
        $this->assertInterceptorPluginIsRegistered(
            Configurable::class,
            AddImagesToSwatchJsonConfig::class,
            'Yireo_NextGenImages::addImagesToSwatchJsonConfig'
        );
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture current_store yireo_nextgenimages/settings/enabled 1
     */
    public function testReplacesExistingSwatchImageWithWebp(): void
    {
        $imageUrl = 'http://localhost/media/'.self::TEST_FOLDER.'/test1.jpg';
        $missingUrl = 'http://localhost/media/'.self::TEST_FOLDER.'/missing.jpg';
        $json = json_encode([
            93 => [
                1 => ['type' => 2, 'value' => $imageUrl, 'thumb' => $missingUrl],
                2 => ['type' => 1, 'value' => '#ff0000'],
            ],
        ]);

        $result = json_decode($this->getPlugin()->afterGetJsonSwatchConfig(
            $this->objectManager->get(Configurable::class),
            $json
        ), true);

        $this->assertSame('http://localhost/media/'.self::TEST_FOLDER.'/test1.webp', $result[93][1]['value']);
        $this->assertSame($missingUrl, $result[93][1]['thumb']);
        $this->assertSame('#ff0000', $result[93][2]['value']);
        $this->assertFileExists($this->mediaFolder.'/test1.webp');
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture current_store yireo_nextgenimages/settings/enabled 0
     */
    public function testLeavesConfigUntouchedWhenDisabled(): void
    {
        $json = json_encode([93 => [1 => ['type' => 2, 'value' => 'http://localhost/media/'.self::TEST_FOLDER.'/test1.jpg']]]);

        $this->assertSame($json, $this->getPlugin()->afterGetJsonSwatchConfig(
            $this->objectManager->get(Configurable::class),
            $json
        ));
        $this->assertSame(0, FakeWebpConvertor::$calls);
    }

    private function getPlugin(): AddImagesToSwatchJsonConfig
    {
        $convertorListing = $this->objectManager->create(ConvertorListing::class, [
            'convertors' => [$this->objectManager->create(FakeWebpConvertor::class)],
        ]);
        $imageCollector = $this->objectManager->create(ImageCollector::class, [
            'convertorListing' => $convertorListing,
        ]);

        return $this->objectManager->create(AddImagesToSwatchJsonConfig::class, [
            'imageCollector' => $imageCollector,
        ]);
    }

    private function removeTestFiles(): void
    {
        if (is_dir($this->mediaFolder)) {
            (new FileDriver())->deleteDirectory($this->mediaFolder);
        }
    }
}
