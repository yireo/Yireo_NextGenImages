<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\Block;

use PHPUnit\Framework\Attributes\DataProvider;
use Yireo\NextGenImages\Block\PictureFactory;
use Yireo\IntegrationTestHelper\Test\Integration\AbstractTestCase;
use Yireo\NextGenImages\Image\Image;
use Yireo\NextGenImages\Util\UrlConvertor;

class PictureTest extends AbstractTestCase
{
    /**
     * @return void
     * @magentoConfigFixture current_store yireo_nextgenimages/settings/lazy_loading 0
     */
    public function testPictureCreation()
    {
        $this->setAreaCodeToFrontend();

        $originalImage = new Image('/tmp/pub/images/test.png', '/images/test.png');
        $images = [new Image('/tmp/pub/images/test.webp', '/images/test.webp')];

        $pictureFactory = $this->objectManager->create(PictureFactory::class);
        $picture = $pictureFactory->create($originalImage, $images, '<img src="/images/test.png" class="foobar" fetchpriority="high"/>');

        $html = $picture->toHtml();
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('<source type="image/webp" srcset="/images/test.webp"', $html);
        $this->assertStringContainsString('<img src="/images/test.png" class="foobar" fetchpriority="high"/>', $html);

        preg_match_all('#<source ([^>]+)>#msi', $html, $matches);
        foreach ($matches[0] as $match) {
            $this->assertStringNotContainsString('class="foobar"', $match);
            $this->assertStringNotContainsString('fetchpriority="high"', $match);
        }
    }

    /**
     * @return void
     * @magentoConfigFixture current_store yireo_nextgenimages/settings/lazy_loading 1
     */
    public function testPictureCreationWithLazyLoading()
    {
        $this->setAreaCodeToFrontend();

        $originalImage = new Image('/tmp/pub/images/test.png', '/images/test.png');
        $images = [new Image('/tmp/pub/images/test.webp', '/images/test.webp')];

        $pictureFactory = $this->objectManager->create(PictureFactory::class);
        $picture = $pictureFactory->create($originalImage, $images, '<img src="/images/test.png"/>');

        $html = $picture->toHtml();
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('<source type="image/webp" srcset="/images/test.webp"', $html);
        $this->assertStringContainsString('<img src="/images/test.png" loading="lazy" />', $html);
    }

    /**
     * @dataProvider getLoadingAttributeCases
     * @magentoConfigFixture current_store yireo_nextgenimages/settings/lazy_loading 1
     */
    #[DataProvider('getLoadingAttributeCases')]
    public function testLoadingAttributeWithLazyLoadingEnabled(string $tag, int $expectedWhenEnabled, int $expectedWhenDisabled, string $expectedValue)
    {
        $this->assertLoadingAttribute($tag, $expectedWhenEnabled, $expectedValue);
    }

    /**
     * @dataProvider getLoadingAttributeCases
     * @magentoConfigFixture current_store yireo_nextgenimages/settings/lazy_loading 0
     */
    #[DataProvider('getLoadingAttributeCases')]
    public function testLoadingAttributeWithLazyLoadingDisabled(string $tag, int $expectedWhenEnabled, int $expectedWhenDisabled, string $expectedValue)
    {
        $this->assertLoadingAttribute($tag, $expectedWhenDisabled, $expectedWhenDisabled > 0 ? $expectedValue : '');
    }

    public static function getLoadingAttributeCases(): array
    {
        return [
            'no loading attribute' => ['<img src="/images/test.png">', 1, 0, 'lazy'],
            'lazy' => ['<img src="/images/test.png" loading="lazy">', 1, 1, 'lazy'],
            'lazy, self closing' => ['<img src="/images/test.png" loading="lazy" />', 1, 1, 'lazy'],
            'eager' => ['<img src="/images/test.png" loading="eager">', 1, 1, 'eager'],
            'fetchpriority high' => ['<img src="/images/test.png" fetchpriority="high">', 0, 0, ''],
            'fetchpriority high, self closing' => ['<img src="/images/test.png" fetchpriority="high" />', 0, 0, ''],
            'fetchpriority high with lazy' => ['<img src="/images/test.png" fetchpriority="high" loading="lazy">', 1, 1, 'lazy'],
            'fetchpriority high with eager' => ['<img src="/images/test.png" fetchpriority="high" loading="eager">', 1, 1, 'eager'],
        ];
    }

    private function assertLoadingAttribute(string $tag, int $expectedCount, string $expectedValue): void
    {
        $this->setAreaCodeToFrontend();

        $originalImage = new Image('/tmp/pub/images/test.png', '/images/test.png');
        $images = [new Image('/tmp/pub/images/test.webp', '/images/test.webp')];

        $pictureFactory = $this->objectManager->create(PictureFactory::class);
        $html = $pictureFactory->create($originalImage, $images, $tag)->toHtml();

        $this->assertMatchesRegularExpression('#<img [^>]+>#', $html);
        preg_match('#<img [^>]+>#', $html, $imgMatch);
        $this->assertSame($expectedCount, substr_count($imgMatch[0], 'loading='), $html);

        if ($expectedValue !== '') {
            $this->assertStringContainsString('loading="'.$expectedValue.'"', $imgMatch[0]);
        }

        if ($expectedValue === 'eager') {
            $this->assertStringNotContainsString('loading="lazy"', $html);
        }

        preg_match_all('#<source [^>]+>#', $html, $sourceMatches);
        $this->assertNotEmpty($sourceMatches[0]);
        foreach ($sourceMatches[0] as $sourceMatch) {
            $this->assertStringNotContainsString('loading=', $sourceMatch);
        }
    }
}
