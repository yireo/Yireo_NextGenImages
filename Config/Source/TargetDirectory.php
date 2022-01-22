<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class TargetDirectory implements OptionSourceInterface
{
    public const SAME_AS_SOURCE = 'same_as_source';

    public const CACHE = 'cache';

    public function toOptionArray()
    {
        return [
            ['value' => self::SAME_AS_SOURCE, 'label' => 'Same filename as source image'],
            ['value' => self::CACHE, 'label' => 'File in media cache directory'],
        ];
    }
}
