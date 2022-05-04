<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Convertor;

class ConvertorListing
{
    /**
     * @var ConvertorInterface[]
     */
    private $convertors = [];

    /**
     * ConvertorListing constructor.
     * @param ConvertorInterface[] $convertors
     */
    public function __construct(array $convertors = [])
    {
        foreach ($convertors as $convertor) {
            $this->addConvertor($convertor);
        }
    }

    /**
     * @param ConvertorInterface $convertor
     * @return void
     */
    public function addConvertor(ConvertorInterface $convertor)
    {
        $this->convertors[] = $convertor;
    }

    /**
     * @return ConvertorInterface[]
     */
    public function getConvertors(): array
    {
        return $this->convertors;
    }
}
