<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Util;

use Magento\Framework\View\Element\Template;
use Yireo\NextGenImages\Test\Unit\AbstractTestCase;
use Yireo\NextGenImages\Util\BlockRenderStack;

class BlockRenderStackTest extends AbstractTestCase
{
    public function testIsEmptyByDefault(): void
    {
        $this->assertTrue((new BlockRenderStack())->isEmpty());
    }

    public function testNestedBlocks(): void
    {
        $stack = new BlockRenderStack();
        $parent = $this->getMagentoMock(Template::class);
        $child = $this->getMagentoMock(Template::class);

        $stack->push($parent);
        $stack->push($child);
        $stack->pop($child);
        $this->assertFalse($stack->isEmpty());

        $stack->pop($parent);
        $this->assertTrue($stack->isEmpty());
    }

    public function testPopRemovesLeakedChildren(): void
    {
        $stack = new BlockRenderStack();
        $parent = $this->getMagentoMock(Template::class);
        $child = $this->getMagentoMock(Template::class);

        $stack->push($parent);
        $stack->push($child);
        $stack->pop($parent);
        $this->assertTrue($stack->isEmpty());
    }

    public function testPopOfUnknownBlockIsIgnored(): void
    {
        $stack = new BlockRenderStack();
        $parent = $this->getMagentoMock(Template::class);
        $stack->push($parent);
        $stack->pop($this->getMagentoMock(Template::class));
        $this->assertFalse($stack->isEmpty());
    }

    public function testRecursiveRenderingOfSameBlock(): void
    {
        $stack = new BlockRenderStack();
        $block = $this->getMagentoMock(Template::class);
        $stack->push($block);
        $stack->push($block);
        $stack->pop($block);
        $this->assertFalse($stack->isEmpty());
        $stack->pop($block);
        $this->assertTrue($stack->isEmpty());
    }
}
