<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Plugin;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Swatches\Block\Product\Renderer\Configurable;
use Yireo\NextGenImages\Config\Config;
use Yireo\NextGenImages\Image\Image;
use Yireo\NextGenImages\Image\ImageCollector;
use Yireo\NextGenImages\Plugin\AddImagesToSwatchJsonConfig;
use Yireo\NextGenImages\Test\Unit\AbstractTestCase;

class AddImagesToSwatchJsonConfigTest extends AbstractTestCase
{
    private const SWATCH_IMAGE = 'http://localhost/media/attribute/swatch/swatch_image/30x20/r/e/red.png';
    private const SWATCH_THUMB = 'http://localhost/media/attribute/swatch/swatch_thumb/110x90/r/e/red.png';

    public function testReturnsOriginalWhenDisabled(): void
    {
        $json = json_encode([93 => [1 => ['type' => 2, 'value' => self::SWATCH_IMAGE]]]);
        $plugin = $this->getPlugin(false, []);
        $this->assertSame($json, $plugin->afterGetJsonSwatchConfig($this->getSubject(), $json));
    }

    public function testReplacesImageSwatchUrlsWithWebp(): void
    {
        $json = json_encode([
            93 => [
                1 => ['type' => 2, 'value' => self::SWATCH_IMAGE, 'thumb' => self::SWATCH_THUMB, 'label' => 'Red'],
                'additional_data' => '{"swatch_input_type":"visual"}',
            ],
        ]);

        $plugin = $this->getPlugin(true, [
            self::SWATCH_IMAGE => [$this->getImage('avif'), $this->getImage('webp', 'image')],
            self::SWATCH_THUMB => [$this->getImage('webp', 'thumb')],
        ]);

        $result = json_decode($plugin->afterGetJsonSwatchConfig($this->getSubject(), $json), true);
        $this->assertSame('http://localhost/media/image.webp', $result[93][1]['value']);
        $this->assertSame('http://localhost/media/thumb.webp', $result[93][1]['thumb']);
        $this->assertSame('Red', $result[93][1]['label']);
        $this->assertSame('{"swatch_input_type":"visual"}', $result[93]['additional_data']);
    }

    public function testKeepsOriginalUrlWhenNoWebpIsAvailable(): void
    {
        $json = json_encode([93 => [1 => ['type' => 2, 'value' => self::SWATCH_IMAGE, 'thumb' => self::SWATCH_THUMB]]]);
        $plugin = $this->getPlugin(true, [
            self::SWATCH_IMAGE => [],
            self::SWATCH_THUMB => [$this->getImage('avif')],
        ]);

        $result = json_decode($plugin->afterGetJsonSwatchConfig($this->getSubject(), $json), true);
        $this->assertSame(self::SWATCH_IMAGE, $result[93][1]['value']);
        $this->assertSame(self::SWATCH_THUMB, $result[93][1]['thumb']);
    }

    public function testIgnoresNonImageSwatches(): void
    {
        $json = json_encode([
            93 => [
                1 => ['type' => 1, 'value' => '#ff0000'],
                2 => ['type' => 0, 'value' => 'XL'],
                3 => ['type' => 2, 'value' => ''],
            ],
        ]);

        $imageCollector = $this->getMagentoMock(ImageCollector::class);
        $imageCollector->expects($this->never())->method('collect');
        $plugin = new AddImagesToSwatchJsonConfig($this->getConfig(true), new Json(), $imageCollector);

        $result = json_decode($plugin->afterGetJsonSwatchConfig($this->getSubject(), $json), true);
        $this->assertSame(json_decode($json, true), $result);
    }

    public function testReturnsOriginalForEmptyConfig(): void
    {
        $plugin = $this->getPlugin(true, []);
        $this->assertSame('[]', $plugin->afterGetJsonSwatchConfig($this->getSubject(), '[]'));
    }

    private function getPlugin(bool $enabled, array $imagesByUrl): AddImagesToSwatchJsonConfig
    {
        $imageCollector = $this->getMagentoMock(ImageCollector::class);
        $imageCollector->method('collect')->willReturnCallback(
            fn ($url) => $imagesByUrl[$url] ?? []
        );

        return new AddImagesToSwatchJsonConfig($this->getConfig($enabled), new Json(), $imageCollector);
    }

    private function getConfig(bool $enabled): Config
    {
        $config = $this->getMagentoMock(Config::class);
        $config->method('enabled')->willReturn($enabled);
        return $config;
    }

    private function getSubject(): Configurable
    {
        return $this->getMagentoMock(Configurable::class);
    }

    private function getImage(string $code, string $name = 'image'): Image
    {
        return new Image('/var/www/html/pub/media/' . $name . '.' . $code, 'http://localhost/media/' . $name . '.' . $code);
    }
}
