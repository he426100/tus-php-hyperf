<?php

declare(strict_types=1);
/**
 * This file is part of he426100/tus-php-hyperf.
 *
 * @link     https://github.com/he426100/tus-php-hyperf
 * @contact  mrpzx001@gmail.com
 * @license  https://github.com/he426100/tus-php-hyperf/blob/master/LICENSE
 */
namespace Tus;

use Tus\Cache\Cacheable;
use Tus\Cache\CacheFactory;
use Tus\Commands\ExpirationCommand;
use Tus\Middleware\Middleware;
use Tus\Middleware\TusMiddlewareInterface;
use Tus\Tus\AbstractTus;
use Tus\Tus\Server;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                Cacheable::class => CacheFactory::class,
                TusMiddlewareInterface::class => Middleware::class,
                AbstractTus::class => Server::class,
            ],
            'annotations' => [
            ],
            'commands' => [
                ExpirationCommand::class,
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for he426100/tus-php-hyperf.',
                    'source' => __DIR__ . '/../publish/tus.php',
                    'destination' => BASE_PATH . '/config/autoload/tus.php',
                ],
            ],
        ];
    }
}
