<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Block;

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Template\Context;
use PHPUnit\Framework\Attributes\DataProvider;
use Yireo\NextGenImages\Block\Picture;
use Yireo\NextGenImages\Image\Image;
use Yireo\NextGenImages\Test\Unit\AbstractTestCase;

class PictureTest extends AbstractTestCase
{
    public function testImages()
    {
        $context = $this->getMagentoMock(Context::class);
        $pictureBlock = new Picture($context);

        $pictureBlock->setImages(['foobar']);
        $this->assertSame(['foobar'], $pictureBlock->getImages());

        $pictureBlock->addImage($this->getMagentoMock(Image::class));
        $this->assertCount(2, $pictureBlock->getImages());
    }

    public function testOriginalImage()
    {
        $context = $this->getMagentoMock(Context::class);
        $pictureBlock = new Picture($context);

        $image = new Image('/tmp/pub/images/test.png', '/images/test.png');
        $pictureBlock->setOriginalImage($image);
        $this->assertSame('/images/test.png', $pictureBlock->getOriginalImage()->getUrl());
    }

    public function testOriginalImageType()
    {
        $context = $this->getMagentoMock(Context::class);
        $pictureBlock = new Picture($context);

        $image = new Image('/tmp/pub/images/test.png', '/images/test.png');
        $pictureBlock->setOriginalImage($image);
        $this->assertSame('image/png', $pictureBlock->getOriginalImageType());

        $image = new Image('/tmp/pub/images/test.jpg', '/images/test.jpg');
        $pictureBlock->setOriginalImage($image);
        $this->assertSame('image/jpg', $pictureBlock->getOriginalImageType());

        $image = new Image('/tmp/pub/images/test.jpeg', '/images/test.jpeg');
        $pictureBlock->setOriginalImage($image);
        $this->assertSame('image/jpg', $pictureBlock->getOriginalImageType());
    }

    public function testOriginalTag()
    {
        $context = $this->getMagentoMock(Context::class);
        $pictureBlock = new Picture($context);

        $pictureBlock->setOriginalTag('test');
        $this->assertSame('test', $pictureBlock->getOriginalTag());
    }

    public function testTitle()
    {
        $context = $this->getMagentoMock(Context::class);
        $pictureBlock = new Picture($context);

        $pictureBlock->setTitle('test');
        $this->assertSame('test', $pictureBlock->getTitle());
    }

    public function testAltText()
    {
        $context = $this->getMagentoMock(Context::class);
        $pictureBlock = new Picture($context);

        $pictureBlock->setAltText('test');
        $this->assertSame('test', $pictureBlock->getAltText());
    }

    public function testWidth()
    {
        $context = $this->getMagentoMock(Context::class);
        $pictureBlock = new Picture($context);

        $pictureBlock->setWidth('test');
        $this->assertSame('test', $pictureBlock->getWidth());
    }

    public function testHeight()
    {
        $context = $this->getMagentoMock(Context::class);
        $pictureBlock = new Picture($context);

        $pictureBlock->setHeight('test');
        $this->assertSame('test', $pictureBlock->getHeight());
    }

    public function testStyle()
    {
        $context = $this->getMagentoMock(Context::class);
        $pictureBlock = new Picture($context);

        $pictureBlock->setStyle('test');
        $this->assertSame('test', $pictureBlock->getStyle());
    }

    public function testClass()
    {
        $context = $this->getMagentoMock(Context::class);
        $pictureBlock = new Picture($context);

        $pictureBlock->setClass('test');
        $this->assertSame('test', $pictureBlock->getClass());
    }

    public function testDebug()
    {
        $context = $this->getMagentoMock(Context::class);
        $pictureBlock = new Picture($context);

        $pictureBlock->setDebug(true);
        $this->assertSame(true, $pictureBlock->isDebug());

        $pictureBlock->setDebug(false);
        $this->assertSame(false, $pictureBlock->isDebug());
    }

    public function testLazyLoading()
    {
        $context = $this->getMagentoMock(Context::class);
        $pictureBlock = new Picture($context);

        $pictureBlock->setLazyLoading(true);
        $this->assertSame(true, $pictureBlock->getLazyLoading());

        $pictureBlock->setLazyLoading(false);
        $this->assertSame(false, $pictureBlock->getLazyLoading());
    }

    public function testIsDataSrc()
    {
        $context = $this->getMagentoMock(Context::class);
        $pictureBlock = new Picture($context);

        $pictureBlock->setIsDataSrc(true);
        $this->assertSame(true, $pictureBlock->getIsDataSrc());

        $pictureBlock->setIsDataSrc(false);
        $this->assertSame(false, $pictureBlock->getIsDataSrc());
    }
    /**
     * @dataProvider provideSourceAttributes
     */
    #[DataProvider('provideSourceAttributes')]
    public function testSourceAttributesAreEscaped(string $tag, string $expected)
    {
        $pictureBlock = $this->getPictureBlockWithEscaper();
        $pictureBlock->setOriginalTag($tag);

        $sourceAttributes = $pictureBlock->getSourceAttributesAsString();
        $this->assertSame($expected, $sourceAttributes);
        $this->assertStringNotContainsString('<svg', $sourceAttributes);
    }

    public static function provideSourceAttributes(): array
    {
        return [
            'sizes and media' => [
                '<img src="/media/x.jpg" sizes="100vw" media="(min-width: 600px)" alt="x">',
                'sizes="100vw" media="(min-width: 600px)"',
            ],
            'sizes breaking out' => [
                '<img src="/media/x.jpg" sizes="100vw&quot;&gt;&lt;svg/onload=alert(1)&gt;">',
                'sizes="100vw&quot;&gt;&lt;svg/onload=alert(1)&gt;"',
            ],
            'media breaking out' => [
                '<img src="/media/x.jpg" media="all&quot; onload=&quot;alert(1)">',
                'media="all&quot; onload=&quot;alert(1)"',
            ],
            'single quoted value with double quotes' => [
                '<img src="/media/x.jpg" sizes=\'100vw" onload="alert(1)\'>',
                'sizes="100vw&quot; onload=&quot;alert(1)"',
            ],
            'attribute names containing sizes' => [
                '<img src="/media/x.jpg" data-sizes="100vw" sizesx="1" data-media="all">',
                '',
            ],
        ];
    }

    public function testOriginalAttributesAreEscaped()
    {
        $pictureBlock = $this->getPictureBlockWithEscaper();
        $pictureBlock->setOriginalTag('<img src="/media/x.jpg" alt=\'a "b" <svg>\' title="t">');

        $this->assertSame(
            ['alt="a &quot;b&quot; &lt;svg&gt;"', 'title="t"'],
            $pictureBlock->getOriginalAttributes()
        );
    }

    public function testOriginalAttributesWithInvalidNamesAreSkipped()
    {
        $pictureBlock = $this->getPictureBlockWithEscaper();
        $pictureBlock->setOriginalTag('<img src="/media/x.jpg" a"b="1" <svg="2" title="t">');

        foreach ($pictureBlock->getOriginalAttributes() as $attribute) {
            $this->assertMatchesRegularExpression('/^[a-zA-Z_:@][a-zA-Z0-9_:.@-]*="[^"]*"$/', $attribute);
        }

        $this->assertContains('title="t"', $pictureBlock->getOriginalAttributes());
    }

    private function getPictureBlockWithEscaper(): Picture
    {
        $context = $this->getMagentoMock(Context::class);
        $context->method('getEscaper')->willReturn(new Escaper());

        return new Picture($context);
    }
}
