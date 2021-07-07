<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Logger;

use Psr\Log\LoggerInterface;
use Yireo\NextGenImages\Config\Config;

class Debugger
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Debugger constructor.
     *
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        Config $config,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @param string $msg
     * @param mixed $data
     *
     * @return bool
     */
    public function debug(string $msg, $data = null): bool
    {
        if ($this->config->isLogging() === false) {
            return false;
        }

        if (!empty($data)) {
            $msg .= ': ' . var_export($data, true);
        }

        $this->logger->notice($msg);
        return true;
    }
}
