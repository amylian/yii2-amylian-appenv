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

### Create Yii application instance without running it

In the following example an yii-application is initialized, but Application->run() is not invoked. 

Doing this is not very common, but can be useful if components from an yii application need to be accessed
from outside the application (like maintenance-scripts) or if a mockup application is needed
in phpunit-tests.

the script:
```php
\amylian\yii\appenv\YiiInit::prepareApp(['./configuration/sqlite-app-config.php'],
                                                [
            'basePath' => __DIR__ . '/..'
]);
echo get_class(\Yii::$app->db);  // Should display \yii\db\Connection
```

In this example the config file `./configuration/sqlite-app-config.php` 
(a typical, simple yii application config file) looks like this:

```php
return [
    'id' => 'config-basic',
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'sqlite:@runtime/sqlite-db.sql',
        ]
    ]
];     
```

Under components a connection component is specified which is usually accessed as `\Yii::$app->db` in the yii application. 
As the application is initialized in this example, but the method `run()` is not called, it can also be used
outside the usual callstack. 
