<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\Plugin;

use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Swatches\Block\Product\Renderer\Configurable;
use Yireo\IntegrationTestHelper\Test\Integration\AbstractTestCase;
use Yireo\NextGenImages\Convertor\ConvertorListing;
use Yireo\NextGenImages\Image\ImageCollector;
use Yireo\NextGenImages\Plugin\AddImagesToSwatchJsonConfig;
use Yireo\NextGenImages\Test\Integration\Stub\Convertor\FakeWebpConvertor;
use Yireo\NextGenImages\Util\UrlConvertor;

/**
 * @magentoAppArea frontend
 */
class AddImagesToSwatchJsonConfigTest extends AbstractTestCase
{
    private const TEST_FOLDER = 'nextgenimages-swatch-test';
    private const IMAGE_URL = 'http://localhost/media/'.self::TEST_FOLDER.'/test1.jpg';

    private string $mediaFolder;

    protected function setUp(): void
    {
        parent::setUp();
        FakeWebpConvertor::reset();

        $urlConvertor = $this->objectManager->get(UrlConvertor::class);
        $this->mediaFolder = dirname($urlConvertor->getFilenameFromUrl(self::IMAGE_URL));

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
        $missingUrl = 'http://localhost/media/'.self::TEST_FOLDER.'/missing.jpg';
        $json = $this->objectManager->get(Json::class)->serialize([
            93 => [
                1 => ['type' => 2, 'value' => self::IMAGE_URL, 'thumb' => $missingUrl],
                2 => ['type' => 1, 'value' => '#ff0000'],
            ],
        ]);

        $result = $this->objectManager->get(Json::class)->unserialize($this->getPlugin()->afterGetJsonSwatchConfig(
            $this->objectManager->get(Configurable::class),
            $json
        ));

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
        $json = $this->objectManager->get(Json::class)->serialize([93 => [1 => ['type' => 2, 'value' => self::IMAGE_URL]]]);

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
