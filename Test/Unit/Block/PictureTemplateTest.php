<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Block;

use Magento\Framework\Escaper;
use Magento\Framework\View\LayoutInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionProperty;
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
            'fetchpriority high, lazy config on' => ['<img src="/media/a.png" fetchpriority="high">', true, 0],
            'fetchpriority high, lazy config off' => ['<img src="/media/a.png" fetchpriority="high">', false, 0],
            'fetchpriority high, self closing' => ['<img src="/media/a.png" fetchpriority="high" />', true, 0],
            'fetchpriority high with lazy' => ['<img src="/media/a.png" fetchpriority="high" loading="lazy">', true, 1],
            'fetchpriority high with eager' => ['<img src="/media/a.png" fetchpriority="high" loading="eager">', true, 1],
        ];
    }

    public function testSourceAttributesAreEscaped()
    {
        $webpImage = new Image('/tmp/pub/media/a.webp', '/media/a.webp', '/media/a.webp 1x" onerror="alert(1)');

        $html = $this->render('<img src="/media/a.png">', false, [$webpImage]);

        $this->assertStringNotContainsString('" onerror="', $html);
        $this->assertStringContainsString('srcset="/media/a.webp 1x&quot; onerror=&quot;alert(1)"', $html);
    }

    public function testPictureAttributesAreEscaped()
    {
        $html = $this->render('<img src="/media/a.png" class="foo<script>" width="10" height="<b>">', false);

        $this->assertStringContainsString('<picture class="foo&lt;script&gt;">', $html);
        $this->assertStringNotContainsString('<picture class="foo<script>">', $html);
    }

    public function testSizesAttributeOfSourceIsEscaped()
    {
        $webpImage = new Image('/tmp/pub/media/x.webp', '/media/x.webp');
        $tag = '<img src="/media/x.jpg" sizes="100vw&quot;&gt;&lt;svg/onload=alert(1)&gt;">';

        $html = $this->render($tag, false, [$webpImage]);

        $this->assertMatchesRegularExpression('/<source [^>]*sizes="100vw&quot;&gt;&lt;svg\/onload=alert\(1\)&gt;"/', $html);
        $this->assertStringNotContainsString('<svg', $html);
    }

    private function render(string $tag, bool $addLazyLoading, array $images = []): string
    {
        $picture = (new ReflectionClass(Picture::class))->newInstanceWithoutConstructor();

        $layout = $this->getMagentoMock(LayoutInterface::class);
        $layout->method('createBlock')->willReturn($picture);

        $config = $this->getMagentoMock(Config::class);
        $config->method('addLazyLoading')->willReturn($addLazyLoading);
        $config->method('isDebugging')->willReturn(false);

        $image = $this->getMagentoMock(Image::class);
        (new PictureFactory($layout, $config))->create($image, $images, $tag, 'src');

        $block = $picture;
        $escaper = $this->getMagentoMock(Escaper::class);
        $escape = fn ($value) => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $escaper->method('escapeHtml')->willReturnCallback($escape);
        $escaper->method('escapeHtmlAttr')->willReturnCallback($escape);
        $escaperProperty = new ReflectionProperty(Picture::class, '_escaper');
        $escaperProperty->setAccessible(true);
        $escaperProperty->setValue($picture, $escaper);

        ob_start();
        include dirname(__DIR__, 3) . '/view/frontend/templates/picture.phtml';

        return (string)ob_get_clean();
    }
}
