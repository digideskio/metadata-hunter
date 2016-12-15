<?php

namespace Keboola\MetadataHunter\Test;

use Keboola\Csv\CsvFile;
use Keboola\MetadataHunter\Application;
use Symfony\Component\Yaml\Yaml;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    private $token;

    /** @var Application */
    private $application;

    private $config;

    public function setUp()
    {
        $this->token = getenv("KBC_TOKEN");
        $this->config = $this->getConfig();
        $this->application = new Application($this->config, $this->token);
    }

    private function getConfig($suffix = '')
    {
        $config = Yaml::parse(file_get_contents(ROOT_PATH . '/tests/data/config' . $suffix . '.yml'));
        $config['parameters']['data_dir'] = ROOT_PATH . '/tests/data/';

        return $config;
    }

    public function testAppRun()
    {
        echo "Running the test";
        $this->application->run();
    }
}
