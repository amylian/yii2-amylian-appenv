# yii2-amylian-appenv
**Helpers for more convenient Yii2 Application environment initialization**

---
> **Attention This package is experimental and under development - APIs are always subject to change!**
---

Copyright (c) 2018, [Andreas Prucha (Abexto - Helicon Software Development / Amylian Project)](http://www.abexto.com])

## Installation

To install this library, run the command below and you will get the latest version

``` bash
composer require amylian/yii2-amylian-appenv --dev
```

## Examples

### Create and run a Yii-Application

The following basic example index.php file initializes the composer autoloader is initialized and then
\amylian\yii\appenv\YiiInit::prepareApp is called in order to read the given config file, set the base path
and set the `YII_ENV` environment variable to `dev`. If the constant YII_ENV is set to `'dev'` or `'test'`
the constant `YII_DEBUG` is automatically set to true.

```php
require_once __DIR__.'/vendor/autoload.php'; // Init autoloader
\amylian\yii\appenv\YiiInit::prepareApp([
                                          './configuration/config.php'   // configuration file
                                        ],
                                        [
                                          'basePath'     => __DIR__,   // Base path
                                          'yiiConstants' => [
                                            'YII_ENV' => 'dev'  // Set constant YII_ENV to dev
                                          ]  
                                        ])->run();
```

This does basically the same as the following classic yii application script:

```php
defined('YII_ENV') or define('YII_ENV', 'dev');
defined('YII_DEBUG') or define('YII_DEBUG', true);

require __DIR__ . '/vendor/autoload.php';

require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/configuration/config.php';

(new yii\web\Application($config))->run();
```