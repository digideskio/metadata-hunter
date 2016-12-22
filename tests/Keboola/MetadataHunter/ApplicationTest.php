<?php

namespace Keboola\MetadataHunter\Test;

use Keboola\Csv\CsvFile;
use Keboola\MetadataHunter\Application;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\Components;
use Keboola\StorageApi\Metadata;
use Keboola\StorageApi\Options\Components\Configuration;
use Keboola\StorageApi\Options\Components\ConfigurationRow;
use Symfony\Component\Yaml\Yaml;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    /** @var Application */
    private $application;

    private $token;
    private $config;

    /** @var Client */
    private $client;

    private $testConfigs;

    const TEST_BUCKET = "in.c-testBucket";


    public function setUp()
    {
        $this->token = getenv("KBC_TOKEN");
        $this->config = $this->getConfig();
        $this->application = new Application($this->config, $this->token);
        $this->client = new Client([
            "token" => $this->token
        ]);
        $this->initEmptyBucket();
        // create data set
        $this->client->createTable(self::TEST_BUCKET, "languages", new CsvFile(__DIR__ . '/../../data/languages.csv'));
    }

    public function tearDown()
    {
        $componentsApi = new Components($this->client);
        foreach ($this->testConfigs as $componentId => $configId) {
            $componentsApi->deleteConfiguration($componentId, $configId);
        }
        parent::tearDown();
    }

    private function initEmptyBucket()
    {
        try {
            $bucket = $this->client->getBucket(self::TEST_BUCKET);
            $tables = $this->client->listTables($bucket['id']);
            foreach ($tables as $table) {
                $this->client->dropTable($table['id']);
            }
            return $bucket['id'];
        } catch (\Keboola\StorageApi\ClientException $e) {
            return $this->client->createBucket("testBucket", "in", 'Api tests');
        }
    }


    private function getConfig($suffix = '')
    {
        $config = Yaml::parse(file_get_contents(ROOT_PATH . '/tests/data/config' . $suffix . '.yml'));
        $config['parameters']['data_dir'] = ROOT_PATH . '/tests/data/';

        return $config;
    }

    public function testWrDbDatatypesAppRun() {
        
    }
    
    public function testTransformationDatatypesAppRun()
    {

        $componentsApi = new Components($this->client);
        $metadataApi = new Metadata($this->client);

        $targets = $this->config['parameters']['targets'];

        foreach ($targets as $prey) {
            $config = new Configuration();
            $config->setComponentId($prey['componentId']);
            $config->setName("datatypes-test");
            $config->setConfiguration("");
            $configOut = $componentsApi->addConfiguration($config);

            // remember the created config so we can clean up afterwards
            $this->testConfigs[$prey['componentId']] = $configOut['id'];
            $config->setConfigurationId($configOut['id']);
            $row = new ConfigurationRow($config);
            
            $jsonConfig = json_decode(file_get_contents(ROOT_PATH . "/tests/data/{$prey['componentId']}-config.json"));
            $row->setConfiguration($jsonConfig);
            $configRow = $componentsApi->addConfigurationRow($row);
            $this->application->run();

            $idMeta = $metadataApi->listColumnMetadata("in.c-testBucket.languages.id");
            $nameMeta = $metadataApi->listColumnMetadata("in.c-testBucket.languages.name");
            $countMeta = $metadataApi->listColumnMetadata("in.c-testBucket.languages.count");

            $this->assertEquals(count($idMeta), 1);
            $this->assertEquals($idMeta[0]['provider'], "keboola.metadata-hunter");
            $this->assertEquals($idMeta[0]['key'], "datatype.snowflake.transformation");
            $this->assertEquals($idMeta[0]['value'], "VARCHAR(30)");

            $this->assertEquals($nameMeta[0]['provider'], "keboola.metadata-hunter");
            $this->assertEquals($nameMeta[0]['key'], "datatype.snowflake.transformation");
            $this->assertEquals($nameMeta[0]['value'], "VARCHAR (255)");

            $this->assertEquals($countMeta[0]['provider'], "keboola.metadata-hunter");
            $this->assertEquals($countMeta[0]['key'], "datatype.snowflake.transformation");
            $this->assertEquals($countMeta[0]['value'], "INT(10)");
        }
    }
}
