<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Block;

use Yireo\NextGenImages\Block\Picture;
use Yireo\NextGenImages\Image\Image;
use Yireo\NextGenImages\Test\Unit\AbstractTestCase;

class PictureTest extends AbstractTestCase
{
    public function testImages()
    {
        $context = $this->getMagentoMock(\Magento\Framework\View\Element\Template\Context::class);
        $pictureBlock = new Picture($context);

        $pictureBlock->setImages(['foobar']);
        $this->assertSame(['foobar'], $pictureBlock->getImages());

        $pictureBlock->addImage($this->getMagentoMock(Image::class));
        $this->assertCount(2, $pictureBlock->getImages());
    }

    public function testOriginalImage()
    {
        $context = $this->getMagentoMock(\Magento\Framework\View\Element\Template\Context::class);
        $pictureBlock = new Picture($context);

        $pictureBlock->setOriginalImage('test');
        $this->assertSame('test', $pictureBlock->getOriginalImage());
    }

    public function testOriginalImageType()
    {
        $context = $this->getMagentoMock(\Magento\Framework\View\Element\Template\Context::class);
        $pictureBlock = new Picture($context);

        $pictureBlock->setOriginalImage('test.png');
        $this->assertSame('image/png', $pictureBlock->getOriginalImageType());

        $pictureBlock->setOriginalImage('test.jpg');
        $this->assertSame('image/jpg', $pictureBlock->getOriginalImageType());

        $pictureBlock->setOriginalImage('test.jpeg');
        $this->assertSame('image/jpg', $pictureBlock->getOriginalImageType());
    }

    public function testOriginalTag()
    {
        $context = $this->getMagentoMock(\Magento\Framework\View\Element\Template\Context::class);
        $pictureBlock = new Picture($context);

        $pictureBlock->setOriginalTag('test');
        $this->assertSame('test', $pictureBlock->getOriginalTag());
    }

    public function testTitle()
    {
        $context = $this->getMagentoMock(\Magento\Framework\View\Element\Template\Context::class);
        $pictureBlock = new Picture($context);

        $pictureBlock->setTitle('test');
        $this->assertSame('test', $pictureBlock->getTitle());
    }

    public function testAltText()
    {
        $context = $this->getMagentoMock(\Magento\Framework\View\Element\Template\Context::class);
        $pictureBlock = new Picture($context);

        $pictureBlock->setAltText('test');
        $this->assertSame('test', $pictureBlock->getAltText());
    }

    public function testWidth()
    {
        $context = $this->getMagentoMock(\Magento\Framework\View\Element\Template\Context::class);
        $pictureBlock = new Picture($context);

        $pictureBlock->setWidth('test');
        $this->assertSame('test', $pictureBlock->getWidth());
    }

    public function testHeight()
    {
        $context = $this->getMagentoMock(\Magento\Framework\View\Element\Template\Context::class);
        $pictureBlock = new Picture($context);

        $pictureBlock->setHeight('test');
        $this->assertSame('test', $pictureBlock->getHeight());
    }

    public function testStyle()
    {
        $context = $this->getMagentoMock(\Magento\Framework\View\Element\Template\Context::class);
        $pictureBlock = new Picture($context);

        $pictureBlock->setStyle('test');
        $this->assertSame('test', $pictureBlock->getStyle());
    }

    public function testClass()
    {
        $context = $this->getMagentoMock(\Magento\Framework\View\Element\Template\Context::class);
        $pictureBlock = new Picture($context);

        $pictureBlock->setClass('test');
        $this->assertSame('test', $pictureBlock->getClass());
    }

    public function testDebug()
    {
        $context = $this->getMagentoMock(\Magento\Framework\View\Element\Template\Context::class);
        $pictureBlock = new Picture($context);

        $pictureBlock->setDebug(true);
        $this->assertSame(true, $pictureBlock->isDebug());

        $pictureBlock->setDebug(false);
        $this->assertSame(false, $pictureBlock->isDebug());
    }

    public function testLazyLoading()
    {
        $context = $this->getMagentoMock(\Magento\Framework\View\Element\Template\Context::class);
        $pictureBlock = new Picture($context);

        $pictureBlock->setLazyLoading(true);
        $this->assertSame(true, $pictureBlock->getLazyLoading());

        $pictureBlock->setLazyLoading(false);
        $this->assertSame(false, $pictureBlock->getLazyLoading());
    }

    public function testIsDataSrc()
    {
        $context = $this->getMagentoMock(\Magento\Framework\View\Element\Template\Context::class);
        $pictureBlock = new Picture($context);

        $pictureBlock->setIsDataSrc(true);
        $this->assertSame(true, $pictureBlock->getIsDataSrc());

        $pictureBlock->setIsDataSrc(false);
        $this->assertSame(false, $pictureBlock->getIsDataSrc());
    }
}