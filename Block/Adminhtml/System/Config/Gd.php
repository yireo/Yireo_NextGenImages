<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Gd extends Field
{
    protected $_template = 'config/gd.phtml';

    /**
     * Override to render the template instead of the regular output
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->toHtml();
    }

    /**
     * Check if GD supports WebP
     *
     * @return bool
     */
    public function hasGdSupport(): bool
    {
        if (!function_exists('gd_info')) {
            return false;
        }

        if (!function_exists('imagecreatefromwebp')) {
            return false;
        }

        $gdInfo = gd_info();
        $webpMatch = false;
        foreach ($gdInfo as $gdInfoLine => $gdInfoSupport) {
            if (stristr($gdInfoLine, 'webp')) {
                $webpMatch = true;
                break;
            }
        }

        if ($webpMatch === false) {
            return false;
        }

        return true;
    }
}
