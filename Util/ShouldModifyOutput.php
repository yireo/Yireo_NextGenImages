<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Util;

use Magento\Framework\View\LayoutInterface;
use Yireo\NextGenImages\Config\Config;

class ShouldModifyOutput
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var string[]
     */
    private $skippedHandles = [];

    /**
     * @param Config $config
     * @param string[] $skippedHandles
     */
    public function __construct(
        Config $config,
        array $skippedHandles = []
    ) {
        $this->config = $config;
        $this->skippedHandles = $skippedHandles;
    }

    /**
     * @param LayoutInterface $layout
     * @return bool
     */
    public function shouldModifyOutput(LayoutInterface $layout): bool
    {
        if (!$this->config->enabled()) {
            return false;
        }

        $handles = $layout->getUpdate()->getHandles();
        if (empty($handles)) {
            return false;
        }

        foreach ($handles as $handle) {
            if (strstr($handle, '_email_')) {
                return false;
            }
        }

        if (array_intersect($this->skippedHandles, $handles)) {
            return false;
        }

        return true;
    }

    /**
     * @return string[]
     */
    public function getSkippedHandles(): array
    {
        return $this->skippedHandles;
    }
}
