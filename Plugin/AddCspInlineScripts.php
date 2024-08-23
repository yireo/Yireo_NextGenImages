<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Plugin;

use Magento\Framework\View\Element\Template;
use Yireo\CspUtilities\Util\ReplaceInlineScripts;

class AddCspInlineScripts
{
    private ReplaceInlineScripts $replaceInlineScripts;

    public function __construct(
        ReplaceInlineScripts $replaceInlineScripts
    ) {
        $this->replaceInlineScripts = $replaceInlineScripts;
    }

    public function afterToHtml(Template $block, $html): string
    {
        if (false === strstr((string)$block->getNameInLayout(), 'yireo_nextgenimages.')) {
            return (string) $html;
        }

        return $this->replaceInlineScripts->replace((string)$html);
    }
}
