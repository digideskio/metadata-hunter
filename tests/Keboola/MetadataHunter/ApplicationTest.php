<?php

namespace Keboola\MetadataHunter\Test;

use Keboola\Csv\CsvFile;
use Keboola\MetadataHunter\Application;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    /** @var Application */
    private $application;

    private $config;

    public function setUp()
    {
        $this->config = $this->getConfig();
        $this->application = new Application($this->config);
    }

    private function getConfig($suffix = '')
    {
        $config = Yaml::parse(file_get_contents(ROOT_PATH . '/tests/data/config' . $suffix . '.yml'));
        $config['parameters']['data_dir'] = ROOT_PATH . '/tests/data/';

        return $config;
    }

    public function testAppRun()
    {
        $this->application->run();
    }
}
