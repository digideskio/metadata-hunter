<?php

namespace Keboola\MetadataHunter;

use Keboola\MetadataHunter\Configuration\ConfigDefinition;
use Keboola\MetadataHunter\Exception\ApplicationException;
use Keboola\MetadataHunter\Exception\UserException;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\Components;
use Keboola\StorageApi\ClientException;
use Monolog\Handler\NullHandler;
use Pimple\Container;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class Application
{

    private $container;

    public function __construct($config, $token)
    {
        $container = new Container();
        $container['action'] = isset($config['action'])?$config['action']:'run';
        $container['parameters'] = $this->validateParamteters($config['parameters']);
        $container['logger'] = function ($c) {
            $logger = new Logger(APP_NAME);
            if ($c['action'] !== 'run') {
                $logger->setHandlers([new NullHandler(Logger::INFO)]);
            }
            return $logger;
        };
        
        $container['sapiClient'] = function () use ($token) {
            try {
                return new Client(['token' => $token]);
            } catch (ClientException $e) {
                throw new UserException("Sapi Client init fail: " . $e->getMessage(), $e->getCode(), $e);
            }
        };
        $this->container = $container;
    }

    public function run()
    {
        $actionMethod = $this->container['action'] . 'Action';

        if (!method_exists($this, $actionMethod)) {
            throw new UserException(sprintf("Action '%s' does not exist.", $this['action']));
        }

        try {
            return $this->$actionMethod();
        } catch (ClientException $e) {
            if ($e->getCode() == 401) {
                throw new UserException("Invalid storage token.", 401, $e);
            }
            if ($e->getCode() == 403) {
                throw new UserException("Forbidden: " . $e->getMessage(), 403, $e);
            }
            if ($e->getCode() == 404) {
                throw new UserException("Not found: " . $e->getMessage(), 404, $e);
            }
            if ($e->getCode() == 400) {
                throw new UserException($e->getMessage());
            }
            throw new ApplicationException($e->getMessage(), 500, $e);
        }
    }

    private function runAction()
    {
        // Go get some metadata
        /** @var Client $client */
        $client = $this->container['sapiClient'];
        $componentsApi = new Components($client);
        /** @var Logger $logger */
        $logger = $this->container['logger'];
        $configs = $componentsApi->getComponentConfigurations("transformation");

        $logger->info("Found " . count($configs) . " transformation configs");

        foreach ($configs as $i => $config) {

            foreach ($config['rows'] as $j => $configRow) {

                if (!empty($configRow['configuration']['input'])) {
                    foreach ($configRow['configuration']['input'] as $input) {
                        if (!empty($input['datatypes'])) {
                            var_dump($input['datatypes']);
                        }
                    }
                }

            }

        }
        // Write it to sapi

        // Return a log of what just happened
        return [
            'status' => 'ok',
            'updates' => []  // list of metadata keys and compositeId/URI that were updated.
        ];
    }

    private function validateParamteters($parameters)
    {
        // no parameters needed for `segments` action
        if ($this->container['action'] == 'noValidation') {
            return [];
        }

        try {
            $processor = new Processor();
            return $processor->processConfiguration(
                new ConfigDefinition(),
                [$parameters]
            );
        } catch (InvalidConfigurationException $e) {
            throw new UserException($e->getMessage(), 0, $e);
        }
    }
}
