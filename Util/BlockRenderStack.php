<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Util;

use Magento\Framework\View\Element\BlockInterface;

class BlockRenderStack
{
    /**
     * @var int[]
     */
    private array $blockIds = [];

    public function push(BlockInterface $block): void
    {
        $this->blockIds[] = spl_object_id($block);
    }

    public function pop(BlockInterface $block): void
    {
        $position = array_search(spl_object_id($block), array_reverse($this->blockIds, true), true);
        if ($position === false) {
            return;
        }

        array_splice($this->blockIds, (int)$position);
    }

    public function isEmpty(): bool
    {
        return $this->blockIds === [];
    }
}
