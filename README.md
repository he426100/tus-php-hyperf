# Hyperf 可恢复文件上传组件

该组件移植了 Tus-php 组件（[Tus-php](https://github.com/ankitpokhrel/tus-php )）相对完整的功能特性，除了 TusPhp\Tus\Client。

* Swoole无法获取 `php://input`，用 `Swoole\Http\Request->getContent()` 代替

## 安装

```shell script
composer require he426100/tus-php-hyperf
```

## 发布配置

```shell script
php bin/hyperf.php vendor:publish he426100/tus-php-hyperf
```

> 文件位于 `config/autoload/tus.php`。

## 使用示例

* nano/index.php
```
<?php

use Tus\Tus\Server;
use Hyperf\Nano\Factory\AppFactory;

require __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();

$app->config([
    'cache.default' => [
        'driver' => \Hyperf\Cache\Driver\RedisDriver::class,
        'packer' => \Hyperf\Utils\Packer\PhpSerializerPacker::class,
        'prefix' => 'c:',
    ],
]);

$app->addRoute(['HEAD', 'GET', 'POST', 'PUT', 'PATCH', 'OPTIONS', 'DELETE'], '/', function(Server $server) {
    return $server->serve();
});

$app->run();
```

* uppy.html
```
<!doctype html>
<html>
    <head>
    <meta charset="utf-8">
    <title>Uppy</title>
    <link href="https://releases.transloadit.com/uppy/v2.7.0/uppy.min.css" rel="stylesheet">
    </head>
    <body>
    <div id="drag-drop-area"></div>

    <script src="https://releases.transloadit.com/uppy/v2.7.0/uppy.min.js"></script>
    <script>
        var uppy = new Uppy.Core()
        .use(Uppy.Dashboard, {
            inline: true,
            target: '#drag-drop-area'
        })
        .use(Uppy.Tus, { 
            endpoint: 'http://localhost:9501',
            chunkSize: 1 * 1024 * 1024
        })

        uppy.on('complete', (result) => {
        console.log('Upload complete! We’ve uploaded these files:', result.successful)
        })
    </script>
    </body>
</html>
```