<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Plugin;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Image as FrameworkImage;
use Magento\Framework\Phrase;
use PHPUnit\Framework\Attributes\DataProvider;
use Yireo\NextGenImages\Config\Config;
use Yireo\NextGenImages\Convertor\ConvertorInterface;
use Yireo\NextGenImages\Convertor\ConvertorListing;
use Yireo\NextGenImages\Exception\ConvertorException;
use Yireo\NextGenImages\Image\Image;
use Yireo\NextGenImages\Image\ImageFactory;
use Yireo\NextGenImages\Logger\Debugger;
use Yireo\NextGenImages\Plugin\ConvertAfterImageSave;
use Yireo\NextGenImages\Test\Unit\AbstractTestCase;

class ConvertAfterImageSaveTest extends AbstractTestCase
{
    /**
     * @dataProvider getEmptyDestinations
     */
    #[DataProvider('getEmptyDestinations')]
    public function testReturnsOriginalWithEmptyDestination(?string $destination): void
    {
        $imageFactory = $this->getMagentoMock(ImageFactory::class);
        $imageFactory->expects($this->never())->method('createFromPath');

        $plugin = $this->getPlugin($imageFactory, []);
        $return = new \stdClass();
        $this->assertSame($return, $plugin->afterSave($this->getSubject(), $return, $destination));
    }

    public static function getEmptyDestinations(): array
    {
        return [[null], ['']];
    }

    public function testReturnsOriginalWhenImageCanNotBeCreated(): void
    {
        $imageFactory = $this->getMagentoMock(ImageFactory::class);
        $imageFactory->method('createFromPath')->willThrowException(new NoSuchEntityException(new Phrase('foo')));

        $convertor = $this->getMagentoMock(ConvertorInterface::class);
        $convertor->expects($this->never())->method('convertImage');

        $plugin = $this->getPlugin($imageFactory, [$convertor]);
        $this->assertSame('original', $plugin->afterSave($this->getSubject(), 'original', '/tmp/foo.jpg'));
    }

    public function testReturnsOriginalWhenConversionFails(): void
    {
        $imageFactory = $this->getMagentoMock(ImageFactory::class);
        $imageFactory->method('createFromPath')->willReturn(new Image('/tmp/foo.jpg', '/media/foo.jpg'));

        $failingConvertor = $this->getMagentoMock(ConvertorInterface::class);
        $failingConvertor->method('convertImage')->willThrowException(new ConvertorException('foo'));

        $fileSystemFailingConvertor = $this->getMagentoMock(ConvertorInterface::class);
        $fileSystemFailingConvertor->method('convertImage')
            ->willThrowException(new FileSystemException(new Phrase('foo')));

        $workingConvertor = $this->getMagentoMock(ConvertorInterface::class);
        $workingConvertor->expects($this->once())->method('convertImage');

        $plugin = $this->getPlugin($imageFactory, [$failingConvertor, $fileSystemFailingConvertor, $workingConvertor]);
        $this->assertSame('original', $plugin->afterSave($this->getSubject(), 'original', '/tmp/foo.jpg'));
    }

    public function testReturnsOriginalAfterConversion(): void
    {
        $imageFactory = $this->getMagentoMock(ImageFactory::class);
        $imageFactory->expects($this->once())->method('createFromPath')->with('/tmp/foo.jpg')
            ->willReturn(new Image('/tmp/foo.jpg', '/media/foo.jpg'));

        $convertor = $this->getMagentoMock(ConvertorInterface::class);
        $convertor->expects($this->once())->method('convertImage');

        $plugin = $this->getPlugin($imageFactory, [$convertor]);
        $this->assertSame('original', $plugin->afterSave($this->getSubject(), 'original', '/tmp/foo.jpg'));
    }

    private function getPlugin(ImageFactory $imageFactory, array $convertors): ConvertAfterImageSave
    {
        $config = $this->getMagentoMock(Config::class);
        $config->method('enabled')->willReturn(true);
        $config->method('convertImagesOnSave')->willReturn(true);

        $convertorListing = $this->getMagentoMock(ConvertorListing::class);
        $convertorListing->method('getConvertors')->willReturn($convertors);

        return new ConvertAfterImageSave(
            $convertorListing,
            $this->getMagentoMock(Debugger::class),
            $config,
            $imageFactory
        );
    }

    private function getSubject(): FrameworkImage
    {
        return $this->getMagentoMock(FrameworkImage::class);
    }
}
