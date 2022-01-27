<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Convertor;

use Yireo\NextGenImages\Exception\ConvertorException;
use Yireo\NextGenImages\Image\Image;

interface ConvertorInterface
{
    /**
     * @param Image $image
     * @return image
     * @throws ConvertorException
     */
    public function convertImage(Image $image): Image;
}
