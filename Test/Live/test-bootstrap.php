<?php declare(strict_types=1);

use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Http;

require __DIR__ . '/../../../../../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$bootstrap->createApplication(Http::class);

