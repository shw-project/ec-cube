<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */
namespace Eccube\Service\Composer;

use Composer\Console\Application;
use Eccube\Annotation\Inject;
use Eccube\Annotation\Service;
use Eccube\Service\PluginService;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Class ComposerService
 * @package Eccube\Service\Composer
 * @Service
 */
class ComposerService
{
    /**
     * @Inject("config")
     * @var array
     */
    protected $appConfig;

    /**
     * @Inject(PluginService::class)
     * @var PluginService
     */
    protected $pluginService;

    /**
     * @var Application $consoleApplication
     */
    private $consoleApplication;

    private $workingDir;

    /**
     * Run get info command
     *
     * @param string $pluginName format foo/bar or foo/bar:1.0.0 or "foo/bar 1.0.0"
     * @return array
     */
    public function execInfo($pluginName)
    {
        $output = $this->runCommand(array(
            'command' => 'info',
            'package' => $pluginName,
        ));
        $parser = new ParseOutputCommand($output, ParseOutputCommand::INFO_TYPE);

        return $parser->parse();
    }

    /**
     * Run execute command
     *
     * @param string $packageName format foo/bar or foo/bar:1.0.0 or "foo/bar 1.0.0"
     * @return array
     */
    public function execRequire($packageName)
    {
        $output = $this->runCommand(array(
            'command' => 'require',
            'packages' => array($packageName),
            '--no-interaction' => true,
            '--profile' => true,
            '--prefer-dist' => true,
            '--ignore-platform-reqs' => true,
        ));

        $parser = new ParseOutputCommand($output, ParseOutputCommand::REQUIRE_TYPE);

        return $parser->parse();
    }

    /**
     * Run remove command
     *
     * @param string $packageName format foo/bar or foo/bar:1.0.0 or "foo/bar 1.0.0"
     * @return void
     */
    public function execRemove($packageName)
    {
        $this->runCommand(array(
            'command' => 'remove',
            'packages' => array($packageName),
            '--ignore-platform-reqs' => true,
            '--no-interaction' => true,
            '--profile' => true,
        ));
    }

    /**
     * Get require
     *
     * @param string $packageName
     * @param string $callback
     * @param null   $typeFilter
     */
    public function foreachRequires($packageName, $callback, $typeFilter = null)
    {
        $info = $this->execInfo($packageName);
        if (isset($info['requires'])) {
            foreach ($info['requires'] as $name => $version) {
                $package = $this->execInfo($name);
                if (is_null($typeFilter) || @$package['type'] === $typeFilter) {
                    $callback($package);
                }
            }
        }
    }

    /**
     * Run get config information
     *
     * @param string $key
     * @param null   $value
     * @return array|mixed
     */
    public function execConfig($key, $value = null)
    {
        $commands = array(
            'command' => 'config',
            'setting-key' => $key,
            'setting-value' => $value,
        );
        if ($value) {
            $commands['setting-value'] = $value;
        }
        $output = $this->runCommand($commands);
        $parser = new ParseOutputCommand($output, ParseOutputCommand::CONFIG_TYPE);

        return $parser->parse();
    }

    /**
     * Get config list
     *
     * @return array
     */
    public function getConfig()
    {
        $output = $this->runCommand(array(
            'command' => 'config',
            '--list' => true,
        ));
        $parser = new ParseOutputCommand($output, ParseOutputCommand::LIST_TYPE);

        return $parser->parse();
    }

    /**
     * Set work dir
     *
     * @param string $workingDir
     */
    public function setWorkingDir($workingDir)
    {
        $this->workingDir = $workingDir;
    }

    /**
     * Init composer console application
     */
    private function init()
    {
        set_time_limit(0);
        @ini_set('memory_limit', '1536M');
        // Config for some environment
        putenv('COMPOSER_HOME='.$this->appConfig['plugin_realdir'].'/.composer');
        $consoleApplication = new Application();
        $consoleApplication->resetComposer();
        $consoleApplication->setAutoExit(false);
        $this->consoleApplication = $consoleApplication;
        $this->workingDir = $this->workingDir ? $this->workingDir : $this->appConfig['root_dir'];
    }

    /**
     * Run composer command
     *
     * @param array $commands
     * @return string
     */
    private function runCommand($commands)
    {
        $this->init();
        $commands['--working-dir'] = $this->workingDir;
        $commands['--no-ansi'] = 1;
        $input = new ArrayInput($commands);
        $output = new BufferedOutput();

        $exitCode = $this->consoleApplication->run($input, $output);

        $log = $output->fetch();
        if ($exitCode) {
            log_error($log);
            throw new \RuntimeException($log);
        }
        log_info($log, $commands);

        return $log;
    }
}
