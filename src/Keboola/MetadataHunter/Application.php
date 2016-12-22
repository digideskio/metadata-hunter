<?php

namespace Keboola\MetadataHunter;

use Keboola\MetadataHunter\ConfigParsers\DbWriterConfigParser;
use Keboola\MetadataHunter\ConfigParsers\TransformationConfigParser;
use Keboola\MetadataHunter\Configuration\ConfigDefinition;
use Keboola\MetadataHunter\Exception\ApplicationException;
use Keboola\MetadataHunter\Exception\UserException;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\Metadata;
use Keboola\StorageApi\Components;
use Keboola\StorageApi\ClientException;
use Monolog\Handler\NullHandler;
use Pimple\Container;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class Application
{

    const APP_NAME = "keboola.metadata-hunter";
    
    private $container;

    public function __construct($config, $token)
    {
        $container = new Container();
        $container['action'] = isset($config['action']) ? $config['action'] : 'run';
        $container['parameters'] = $this->validateParamteters($config['parameters']);
        $container['logger'] = function ($c) {
            $logger = new Logger(APP_NAME);
            if ($c['action'] !== 'run') {
                $logger->setHandlers([new NullHandler(Logger::INFO)]);
            }
            return $logger;
        };

        $container['targets'] = $config['parameters']['targets'];
        
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
        /** @var Logger $logger */
        $logger = $this->container['logger'];
        if (!method_exists($this, $actionMethod)) {
            throw new UserException(sprintf("Action '%s' does not exist.", $this['action']));
        }

        try {
            return $this->$actionMethod();
        } catch (ClientException $e) {
            if ($e->getCode() == 401) {
                throw new UserException("Invalid storage token.", 401, $e);
            } else if ($e->getCode() == 403) {
                throw new UserException("Forbidden: " . $e->getMessage(), 403, $e);
            } else if ($e->getCode() == 404) {
                // We'll log 404's as it is possible for configurations to exist on tables that have been deleted.
                $logger->error("404: " . $e->getMessage());
                //throw new UserException("Not found: " . $e->getMessage(), 404, $e);
            } else if ($e->getCode() == 400) {
                throw new UserException($e->getMessage());
            } else {
                throw new ApplicationException($e->getMessage(), 500, $e);
            }
        }
    }

    private function runAction()
    {
        /** @var Client $client */
        $client = $this->container['sapiClient'];
        $componentsApi = new Components($client);
        $metadataApi = new Metadata($client);

        /** @var Logger $logger */
        $logger = $this->container['logger'];

        foreach ($this->container['targets'] as $target) {
            $component = $target['componentId'];
            $configs = $componentsApi->getComponentConfigurations($component);

            $logger->info("Found " . count($configs) . " {$component} configs");

            foreach ($configs as $i => $config) {
                switch ($component) {
                    case "transformation":
                        $parser = new TransformationConfigParser($config, $logger);
                        break;
                    case (preg_match('/wr\-db.*/', $component) ? true : false):
                        $parser = new DbWriterConfigParser($component, $config, $logger);
                        break;
                    default:
                        throw new UserException("I do not know how to parse configs for {$component} component.");
                        break;
                }
                $meta = $parser->getDatatypes();
                foreach ($meta as $column => $metaArray) {
                    $metadataApi->postColumnMetadata($column, self::APP_NAME, $metaArray);
                }
            }
        }

        // Return a log of what just happened
        return [
            'status' => 'ok',
            'updates' => []  // TODO: list of metadata keys and compositeId/URI that were updated?
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
