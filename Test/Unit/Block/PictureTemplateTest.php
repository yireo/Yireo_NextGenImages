<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Block;

use Magento\Framework\Escaper;
use Magento\Framework\View\LayoutInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Yireo\NextGenImages\Block\Picture;
use Yireo\NextGenImages\Block\PictureFactory;
use Yireo\NextGenImages\Config\Config;
use Yireo\NextGenImages\Image\Image;
use Yireo\NextGenImages\Test\Unit\AbstractTestCase;

class PictureTemplateTest extends AbstractTestCase
{
    /**
     * @dataProvider provideTags
     */
    #[DataProvider('provideTags')]
    public function testLoadingAttributeIsEmittedOnce(string $tag, bool $addLazyLoading, int $expectedCount)
    {
        $html = $this->render($tag, $addLazyLoading);

        $this->assertSame($expectedCount, substr_count($html, 'loading='), $html);
    }

    public static function provideTags(): array
    {
        return [
            'lazy already present, lazy config on' => ['<img src="/media/a.png" loading="lazy">', true, 1],
            'lazy already present, self closing' => ['<img src="/media/a.png" loading="lazy" />', true, 1],
            'lazy already present, lazy config off' => ['<img src="/media/a.png" loading="lazy">', false, 1],
            'eager present, lazy config on' => ['<img src="/media/a.png" loading="eager">', true, 1],
            'no loading attribute, lazy config on' => ['<img src="/media/a.png">', true, 1],
            'no loading attribute, lazy config off' => ['<img src="/media/a.png">', false, 0],
        ];
    }

    private function render(string $tag, bool $addLazyLoading): string
    {
        $picture = (new ReflectionClass(Picture::class))->newInstanceWithoutConstructor();

        $layout = $this->getMagentoMock(LayoutInterface::class);
        $layout->method('createBlock')->willReturn($picture);

        $config = $this->getMagentoMock(Config::class);
        $config->method('addLazyLoading')->willReturn($addLazyLoading);
        $config->method('isDebugging')->willReturn(false);

        $image = $this->getMagentoMock(Image::class);
        (new PictureFactory($layout, $config))->create($image, [], $tag, 'src');

        $block = $picture;
        $escaper = $this->getMagentoMock(Escaper::class);

        ob_start();
        include dirname(__DIR__, 3) . '/view/frontend/templates/picture.phtml';

        return (string)ob_get_clean();
    }
}
