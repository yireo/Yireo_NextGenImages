<?php declare(strict_types=1);

use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Http;

$basePath = realpath(__DIR__ . '/../../../../../');
require $basePath . 'app/bootstrap.php';
$bootstrap = Bootstrap::create($basePath, $_SERVER);
$bootstrap->createApplication(Http::class);
