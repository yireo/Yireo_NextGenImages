<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Convertor;

class ConvertorListing
{
    /**
     * @var ConvertorInterface[]
     */
    private $convertors;

    /**
     * ConvertorListing constructor.
     * @param ConvertorInterface[] $convertors
     */
    public function __construct(array $convertors = [])
    {
        $this->convertors = $convertors;
    }

    /**
     * @return ConvertorInterface[]
     */
    public function getConvertors(): array
    {
        return $this->convertors;
    }
}
