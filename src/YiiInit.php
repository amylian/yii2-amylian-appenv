<?php

/*
 * BSD 3-Clause License
 * 
 * Copyright (c) 2018, Abexto - Helicon Software Development / Amylian Project
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 * 
 * * Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 * 
 * * Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 * 
 * * Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 */

namespace amylian\yii\appenv;

/**
 * Static class for convenient Yii2 Application environment initialization
 *
 * @author Andreas Prucha, Abexto - Helicon Software Development
 */
class YiiInit
{

    const CONFIG_FILE_MISSING_USE_DEFAULT = 'default';
    const CONFIG_FILE_MISSING_AUTOCREATE  = 'autocreate';
    const CONFIG_FILE_MISSING_ERROR       = 'error';

    /**
     * Flag for {@link static::destroyYiiApplication}: sets \Yii::$app to null (included in {@link static::DESTROY_DEFAULT})
     */
    const DESTROY_NULL_APP = 0x00000001;

    /**
     * Flag for {@link static::destroyYiiApplication}: Calls {@link \yii\log\Logger::flush()} (included in {@link static::DESTROY_DEFAULT})
     */
    const DESTROY_FLUSH_LOG = 0x00000002;

    /**
     * Flag for {@link static::destroyYiiApplication}: Calls {@link \yii\web\Session::close()} before  (included in {@link static::DESTROY_DEFAULT})
     */
    const DESTROY_CLOSE_SESSION = 0x00000004;

    /**
     * Flag for {@link static::destroyYiiApplication}: Calls {@link \yii\di\Container::clear()} for every item (included in {@link static::DESTROY_DEFAULT})
     */
    const DESTROY_CLEAR_CONTAINER = 0x00000008;

    /**
     * Flag for {@link static::destroyYiiApplication}: Sets \Yii::$container to null (This is not part of DESTROY_DEFAULT and usually not necessary)
     */
    const DESTROY_NULL_CONTAINER = 0x00010000;

    /**
     * Flag for {@link static::destroyYiiApplication}: All usually necessary destructions are done
     */
    const DESTROY_ALL = 0x0000ffff;

    /**
     * Flag for {@link static::destroyYiiApplication}: same as {@link static::DESTROY_ALL}
     */
    const DESTROY_DEFAULT = self::DESTROY_ALL;

    /**
     * Flag for {@link static::destroyYiiApplication}: Sets all DESTROY_Xxxx flags
     */
    const DESTROY_REALLY_EVERYTHING = 0xffffffff;

    /**
     * Flag for {@link static::destroyYiiApplication}: Does not destroy anything and turns the function call into an noop
     */
    const DESTROY_NOTHING = 0x00000000;

    public static function mergeArrays(array ...$a): array
    {
        return \yii\helpers\ArrayHelper::merge(...$a);
    }

    protected static function prepareDefaultOptions(&$options, $requiredOptions = ['basePath'])
    {
        //
        // Check if already prepared
        //
        if (isset($options['__prepared']))
            return;

        //
        // Prepare and check options array
        //
        $options = static::mergeArrays(
                        [
                    'vendorPath'              => './vendor',
                    'yiiFrameworkPath'        => '@vendor/yiisoft/yii',
                    'yiiCorePhp'              => '@yii/Yii.php',
                    'handleMissingConfigFile' => self::CONFIG_FILE_MISSING_AUTOCREATE,
                    'applicationClass'        => (php_sapi_name() == 'cli') ? \yii\console\Application::class : \yii\web\Application::class,
                    'yiiConstants'            => [],
                    'aliases'                 => []
                        ], $options
        );

        $givenOptionKeys = array_keys($options);

        $missingOptions = array_diff($requiredOptions, $givenOptionKeys);

        if ($missingOptions) {
            throw new \InvalidArgumentException('The following item(s) are required options, but missing: ' . implode(', ',
                                                                                                                      $missingOptions));
        }

        $unkownOptions = array_diff($givenOptionKeys,
                                    [
            'basePath',
            'vendorPath',
            'yiiFrameworkPath',
            'yiiCorePhp',
            'handleMissingConfigFile',
            'applicationClass',
            'yiiConstants',
            'aliases'
        ]);

        if ($unkownOptions) {
            throw new \InvalidArgumentException('Invalid $options-argument item(s): ' . implode(', ', $unkownOptions));
        }

        //
        // Set prepared flag in array. This is done before resolving the aliases
        // because this function is also called in makeFullPath and we need to signal
        // that this is not necessary
        //
        $options['__prepared'] = true;


        //
        // Resolve aliases
        //
        
        foreach ($options['aliases'] as $an => $av) {
            $options['aliases'][$an] = static::makeFullPath($av, $options);
        }
    }

    /**
     * Returns the complete path
     * 
     * if $path is a relative path, a combination of $basePath and $path is returned. Otherwise $path is returned
     * unchanged.
     * 
     * *NOTE:* Relative paths *must* begin with a ".", e.g. `'./vendor/foo/bar'`, or `'../foo/bar'`
     * 
     * @param string $path Path to resolve
     * @param string $basePath Base path to use
     * @return string resolved path
     */
    public static function makeFullPath($path, $options)
    {
        static::prepareDefaultOptions($options);
        switch (substr($path, 0, 1)) {
            case '.':
                return $options['basePath'] . '/' . $path;
            case '@':
                if (class_exists(\Yii::class, false)) {
                    $aliasPath = \Yii::getAlias($path, false);
                }
                $dirSepPos = strpos($path, '/');
                if (!$dirSepPos) {
                    $dirSepPos = strpos($path, '\\');
                }
                $alias = $dirSepPos ? substr($path, 0, $dirSepPos) : $path;
                switch ($alias) {
                    case '@app':
                        $aliasPath = $options['basePath'];
                        break;
                    case '@vendor':
                        $aliasPath = static::makeFullPath($options['vendorPath'], $options);
                        break;
                    case '@yii':
                        $aliasPath = static::makeFullPath($options['yiiFrameworkPath'], $options);
                        break;
                    default:
                        $aliasPath = (isset($options['aliases'][$alias])) ? $options['aliases'][$alias] : false;
                }
                if ($aliasPath)
                    return $dirSepPos ? substr_replace($path, $aliasPath, 0, $dirSepPos) : $aliasPath;
                else
                    throw new ConfigException('Unknown alias '.$alias);
            default:
                return $path;
        }
    }

    /**
     * Tries to read a typical Yii configuration file
     * 
     * This function tries to read a typical Yii2 configuration file (a .php include returning an array) and
     * returns its content.
     * 
     * If the file does not exist and the parameter `$createIfMissing` is true, the file is created containing the 
     * passed default values 
     * 
     * **NOTE**: $fileName does not handle aliases or relative paths. Use the return value of static::makeFullPath
     * for this parameter to resolve a path.
     * 
     * @param string $fileName Path  to the file
     * 
     */
    public static function readConfigFile($fileName, $handleMissingConnfgFile = self::CONFIG_FILE_MISSING_AUTOCREATE, array $defaultConfig = [
    ], $configFileComment = "\nConfiguration File\n\nAdd your local configuration according to the documentation of the the application here\n")
    {
        $result = @include $fileName;
        if ($result === false) {
            switch ($handleMissingConnfgFile) {
                case self::CONFIG_FILE_MISSING_AUTOCREATE:
                    $wrappedComment = '/**' . str_replace("\n", '\n * ', $configFileComment) . "\n */";
                    try {

                        $fh = @fopen($fileName, 'xt');
                        if ($fh === false) {
                            throw new ConfigFileException('Could not create configuration file ' . $fileName,
                                                          error_get_last()['type']);
                        }
                        fwrite($fh, "<?php\n");
                        fwrite($fh, $wrappedComment);
                        fwrite($fh, 'return ' . var_export($defaultConfig, true) . ';');
                    } catch (Exception $ex) {
                        throw new ConfigFileException('Could not create configuration file ' . $fileName, error_get_last()['type']);
                    } finally {
                        @fclose($fh);
                    }
                    return $defaultConfig;
                    break;
                case self::CONFIG_FILE_MISSING_USE_DEFAULT:
                    return $defaultConfig;
                default:
                    throw new ConfigFileException('Could not read configuration file ' . $fileName);
            }
        } else {
            $result = static::mergeArrays($defaultConfig, $result);
        }
        return $result;
    }

    /**
     * Sets the Yii defines from the options array 
     *      
     * Constants are set only, if they are not already defined. 
     * 
     * **Note**: If `'YII_ENV'` is set to `'dev'` or `'test'` in the array or YII_ENV is already defined,
     * `'YII_DEBUG'` is automatically set to `true`. If `'YII_ENV'` is not specified in the given array,
     * and is not already defined, `'prod'` is assumed.
     * 
     * @param array $options
     */
    protected static function setConstants($options)
    {
        $defs = isset($options['yiiConstants']) ? $options['yiiConstants'] : [];

        $yiiEnvSym = isset($defs['YII_ENV']) ? $defs['YII_ENV'] : (defined('YII_ENV') ? YII_ENV : null);

        if (($yiiEnvSym === 'dev') || ($yiiEnvSym === 'test')) {
            isset($defs['YII_DEBUG']) || $defs['YII_DEBUG'] = true;
        }

        foreach ($defs as $k => $v) {
            (defined($k)) || define($k, $v);
        }
    }

    /**
     * Prepares the Yii application environment
     * 
     * @param array[]|string[] $configs
     * @param array $options
     * @return type
     */
    public static function prepare(array $configs, array $options = [])
    {
        if (empty($configs)) {
            throw new \InvalidArgumentException(static::class . '::prepare(): Parameter $configs must contain at least one configuration file or configuration array');
        }

        static::prepareDefaultOptions($options);

        static::setConstants($options);

        if (!class_exists(\Yii::class, false)) {
            require_once static::makeFullPath($options['yiiCorePhp'], $options);
            foreach ($options['aliases'] as $an => $av) {
                \Yii::$aliases = array_merge(\Yii::$aliases, $options['aliases']);
            }
        }

        foreach ($configs as $i => $v) {
            $cfn = is_string($v) ? $v : (is_string($i) ? $i : null);
            $cfa = is_array($v) ? $v : [];
            if ($cfn) {
                $parts[] = static::readConfigFile(static::makeFullPath($cfn, $options), $options['handleMissingConfigFile'], $cfa);
            } else {
                $parts[] = $cfa;
            }
        }
        if (count($parts) > 1)
            return static::mergeArrays(...$parts);
        else
            return reset($parts);
    }

    /**
     * Prepares the environment, reads the configuration and creates the Application Object
     * 
     * Basically this function calls  [[prepare()]] with the given optios and creates 
     * the application object specied in the `'applicationClass'` item. If the application class is
     * not specified, [[\yii\console\Application]] is used if php is used from cli, 
     * otherwise the [[\yii\web\Application]] class is used.
     * 
     * @param array &$options
     * @return \yii\base\Application
     */
    public static function prepareApp(array $options)
    {

        static::prepareDefaultOptions($options);

        $configs = static::prepare($options);
        return new $options['applicationClass']($configs);
    }

    /**
     * Destroys application in Yii::$app by setting it to null.
     * 
     * **NOTE**: You do not usually use this functtion in normal applicaiotns, but it's useful 
     * if mock applications are created in units
     * 
     * @param int $destroyFlags 
     */
    public static function destroyYiiApplication($destroyFlags = self::DESTROY_DEFAULT)
    {
        if (!$destroyFlags)
            return false;

        //
        // Close Session
        //
        
        if ($destroyFlags & static::DESTROY_CLOSE_SESSION && \Yii::$app !== null) {
            if (\Yii::$app && \Yii::$app->has('session', true)) {
                \Yii::$app->session->close();
            }
        }

        //
        // Flush log
        //
        
        if ($destroyFlags & static::DESTROY_FLUSH_LOG && \Yii::$app !== null) {
            $logger = \Yii::getLogger();
            $logger->flush();
        }

        //
        //  null Yii App
        //
        
        if ($destroyFlags & static::DESTROY_NULL_APP && \Yii::$app !== null) {
            \Yii::$app = null;
        }

        //
        // Destroy all container defintions
        //
        
        if ($destroyFlags & static::DESTROY_CLEAR_CONTAINER && \Yii::$container !== null) {
            $defs = \Yii::$container->getDefinitions();
            foreach ($defs as $class => $definition) {
                \Yii::$container->clear($class);
            }
        }

        // Null container

        if ($destroyFlags & static::DESTROY_NULL_CONTAINER) {
            \Yii::$container = null;
        }
    }

}
