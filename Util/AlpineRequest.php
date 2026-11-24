<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Util;

use Magento\Framework\App\RequestInterface;

class AlpineRequest
{
    private const HEADER = 'X-Alpine-Request';

    public function __construct(
        private readonly RequestInterface $request
    ) {
    }

    public function isAlpineRequest(): bool
    {
        if (!method_exists($this->request, 'getHeader')) {
            return false;
        }

        return in_array($this->request->getHeader(self::HEADER), ['true', '1'], true);
    }
}
