<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Plugin;

use Magento\Framework\View\LayoutInterface;
use Yireo\NextGenImages\Util\HtmlReplacer;
use Yireo\NextGenImages\Util\ShouldModifyOutput;

class ReplaceTagsInHtml
{
    /**
     * @var HtmlReplacer
     */
    private $htmlReplacer;

    /**
     * @var ShouldModifyOutput
     */
    private $shouldModifyOutput;
    
    /**
     * ReplaceTags constructor.
     *
     * @param HtmlReplacer $htmlReplacer
     * @param ShouldModifyOutput $shouldModifyOutput
     */
    public function __construct(
        HtmlReplacer $htmlReplacer,
        ShouldModifyOutput $shouldModifyOutput
    ) {
        $this->htmlReplacer = $htmlReplacer;
        $this->shouldModifyOutput = $shouldModifyOutput;
    }

    /**
     * Interceptor of getOutput()
     *
     * @param LayoutInterface $layout
     * @param string $output
     * @return string
     */
    public function afterGetOutput(LayoutInterface $layout, string $output): string
    {
        if ($this->shouldModifyOutput->shouldModifyOutput($layout) === false) {
            return $output;
        }

        return $this->htmlReplacer->replace($output);
    }
}
