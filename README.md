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


### Initialization with multiple configuration files and an optional configuration file

In the following example multiple multiple configuration files are used and the constant `YII_ENV` is set to `dev`.

Such constants can be declared in the `yiiConstants` item of the options-array. 
Setting of constants is internally done **before** the configuration files are loaded, thus they can be used
inside the configuration files. The constant `YII_ENV` also has a magic function: If it is set to `dev` or `test`, 
`YII_DEBUG` is set to `true`, if not declared different.

In this example, the following configuration files are used:

./configuration/config-basic.php
./configuration/config-extended.php
./configuration-local/config-optional.php

Every configuration file returns an array as usual in Yii and `YiiInit::prepare()` takes care of
merging them into one configuration array. 

Every configuration file is specified using an alias. The options-array allows the definition of 
aliases which are available during the initialization. In this case, the alias `@cfg` represents the
directory `./configuration` and `@cfg-local` represents `./configuration-local`. These aliases
will also be available inside Yii as the YiiInit merges these to the aliases defined in the `aliases` item
in the application configuration.

In this example the configuration file `̍@cfg-local` is considered optional. The option `handleMissingConfigFile`
defines how non existing configuration files are handled. 
If the value is `\amylian\yii\appenv\YiiInit::CONFIG_FILE_MISSING_USE_DEFAULT` as in this example, 
the the specified default values (in this case just `['id' => 'config-missing']` are used.

**NOTE**: If an optional configuration file exists, the returned configuration array is always internally merged
with the specified default values. This means, that the configuration array will always contain all items.

**NOTE**: If the configuration file is not specified in the key, but the value, it must exist.


```php
$options       = ['basePath'                => __DIR__ . '/..',
  'yiiConstants' => ['YII_ENV' => 'dev'],
  'handleMissingConfigFile' => \amylian\yii\appenv\YiiInit::CONFIG_FILE_MISSING_USE_DEFAULT,
  'aliases'                 => [
      '@runtime'   => './runtime',
      '@cfg'       => './configuration',
      '@cfg-local' => './configuration-local'
  ]];
$configuration = \amylian\yii\appenv\YiiInit::prepare(
      [ '̍@cfg/config-basic.php',
        '@cfg/config-extended.php',
        '@cfg-local/config-optional.php' => [
            'id' => 'config-missing'
        ]], $options);
(new yii\web\Application($configuration))->run();

```

In the example above `\amylian\yii\appenv\YiiInit::prepare()` is called. It's even easier to use 
`\amylian\yii\appenv\YiiInit::prepareApp()` instead. This method uses the same parameters, 
but also creates the application object and returns it. 

```php
$options       = ['basePath'                => __DIR__ . '/..',
  'handleMissingConfigFile' => \amylian\yii\appenv\YiiInit::CONFIG_FILE_MISSING_USE_DEFAULT,
  'yiiConstants' => ['YII_ENV' => 'dev'],
  'aliases'                 => [
      '@runtime'   => './runtime',
      '@cfg'       => './configuration',
      '@cfg-local' => './configuration-local'
  ]];
\amylian\yii\appenv\YiiInit::prepareApp(
      [ '̍@cfg/config-basic.php',
        '@cfg/config-extended.php',
        '@cfg-local/config-optional.php' => [
            'id' => 'config-missing'
        ]], $options)->run()

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

### Initialize Yii application without configuration file

It's easy to initialize an application without using a separate configuration file. It's good practice not 
to do this in real applications, but it can be useful in some cases, for example in phpunit-tests, when
a yii mockup application is needed.

```php
        $app = \amylian\yii\appenv\YiiInit::prepareApp(
                        [// Begin of array of configurations
                    [// Begin of a one configuration as array
                        'id'         => 'directconfig',
                        'components' => [
                        // ...
                        ]
                    ] // End of one configuration as array
                        ], [ // Begin of init-options
                    'basePath' => __DIR__ . '/..'
                        ] // End of init-options
        );
```

