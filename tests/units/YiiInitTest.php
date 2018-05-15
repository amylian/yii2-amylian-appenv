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

namespace amylian\yii\appenv\tests\units;

/**
 * Description of YiiInitTest
 *
 * @author Andreas Prucha, Abexto - Helicon Software Development
 */
class YiiInitTest extends \PHPUnit\Framework\TestCase
{
    use \amylian\phpunit\traits\AssertClassExistsTrait;

    protected function setUp()
    {
        parent::setUp();
        //
        // Destroy as much as possible after each test - we need a new app framework
        //
        if (class_exists('\Yii', false)) {
            \amylian\yii\appenv\YiiInit::destroyYiiApplication();
        }
    }
    
    public function testPrepareWithCustomYiiCore()
    {
        $this->assertClassNotExists(\Yii::class, 'Yii class exists before initialization - not good');
        $configuration = \amylian\yii\appenv\YiiInit::prepare(['./configuration/config-basic.php'], 
                ['basePath' => __DIR__ . '/..',
                 'yiiCorePhp' => './classes/CustomYiiCore.php']);
        $this->assertClassExists(\Yii::class);
        $this->assertEquals('yes', \Yii::$classMarker);
    }

    public function testPrepareWithoutConfig()
    {
        $this->expectException(\InvalidArgumentException::class);
        \amylian\yii\appenv\YiiInit::prepare([], []);
    }

    public function testPrepareWithConfigBasic()
    {
        $configuration = \amylian\yii\appenv\YiiInit::prepare(['./configuration/config-basic.php'], ['basePath' => __DIR__ . '/..']);
        $this->assertArrayHasKey('id', $configuration);
        $this->assertSame($configuration['id'], 'config-basic');
    }

    public function testPrepareWithConfigExtended()
    {
        $configuration = \amylian\yii\appenv\YiiInit::prepare(
                        ['./configuration/config-basic.php',
                    './configuration/config-extended.php'], ['basePath' => __DIR__ . '/..']);
        $this->assertArrayHasKey('id', $configuration);
        $this->assertEquals($configuration['id'], 'config-extended');
    }

    public function testPrepareWithConfigExtendedPartiallyMissing()
    {
        $configuration = \amylian\yii\appenv\YiiInit::prepare(
                        ['./configuration/config-basic.php',
                    './configuration/config-extended.php' => [
                        'defaultField' => 1234]
                        ], ['basePath' => __DIR__ . '/..']);
        $this->assertArrayHasKey('id', $configuration);
        $this->assertArrayHasKey('defaultField', $configuration);
        $this->assertEquals($configuration['id'], 'config-extended');
        $this->assertEquals($configuration['defaultField'], 1234);
    }

    protected function deleteConfigurationMissing()
    {
        @unlink(\amylian\yii\appenv\YiiInit::makeFullPath('./runtime/config-missing.php', ['basePath' => __DIR__ . '/..']));
    }

    public function testPrepareWithConfigWithMissingUseDefaults()
    {
        $this->deleteConfigurationMissing();
        $options       = ['basePath'                => __DIR__ . '/..',
            'handleMissingConfigFile' => \amylian\yii\appenv\YiiInit::CONFIG_FILE_MISSING_USE_DEFAULT];
        $configuration = \amylian\yii\appenv\YiiInit::prepare(
                        ['./configuration/config-basic.php',
                    './configuration/config-extended.php',
                    './runtime/config-missing.php' => [
                        'id' => 'config-missing'
                    ]], $options);
        $this->assertArrayHasKey('id', $configuration);
        $this->assertEquals($configuration['id'], 'config-missing');
        $this->assertFileNotExists(\amylian\yii\appenv\YiiInit::makeFullPath('./runtime/config-missing.php', $options));
    }

    public function testPrepareWithConfigFileAutoCreate()
    {
        $this->deleteConfigurationMissing();
        $missingDefault = [
            'id' => 'config-missing'
        ];
        $this->expectException(\amylian\yii\appenv\ConfigFileException::class);
        $options        = [
            'basePath'                => __DIR__ . '/..',
            'handleMissingConfigFile' => \amylian\yii\appenv\YiiInit::CONFIG_FILE_MISSING_AUTOCREATE];
        $configuration  = \amylian\yii\appenv\YiiInit::prepare(
                        [
                    './configuration/config-basic.php',
                    './configuration/config-extended.php',
                    './runtime/config-missing.php' => $missingDefault], $options);
        $this->assertArrayHasKey('id', $configuration);
        $this->assertEquals($configuration['id'], 'config-missing');
        $this->assertFileExists(\amylian\yii\appenv\YiiInit::makeFullPath('./runtime/config-missing.php', $options));
        $writtenData    = @include \amylian\yii\appenv\YiiInit::makeFullPath('./runtime/config-missing.php', $options);
        $this->assertEquals($missingDefault, $writtenData);
    }

    public function testDefineYiiConstants()
    {
        \amylian\yii\appenv\YiiInit::prepare(['./configuration/config-basic.php'], ['basePath'     => __DIR__ . '/..',
            'yiiConstants' => [
                'XYZ_CONST' => '4321'
        ]]);
        $this->assertEquals(XYZ_CONST, 4321);
    }

}
