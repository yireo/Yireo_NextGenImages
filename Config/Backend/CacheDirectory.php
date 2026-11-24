<?php

declare(strict_types=1);

namespace Yireo\NextGenImages\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\ValidatorException;
use Yireo\NextGenImages\Config\CacheDirectoryValidator;

class CacheDirectory extends Value
{
    /**
     * @return $this
     * @throws ValidatorException
     */
    public function beforeSave()
    {
        $value = trim((string)$this->getValue(), " \f\n\r\t\v\x00");
        if ($value !== '' && !CacheDirectoryValidator::isValid($value)) {
            throw new ValidatorException(
                __('The media cache directory should be a folder within "media/", like "media/nextgenimages/".')
            );
        }

        $this->setValue($value);

        return parent::beforeSave();
    }
}
