<?php

namespace Keboola\MetadataHunter\ConfigParsers;

use Keboola\MetadataHunter\Logger;
use Keboola\StorageApi\ClientException;

class TransformationConfigParser
{

    const COMPONENT_ID = "transformation";

    private $config;

    /** @var  Logger */
    private $logger;

    public function __construct($config, $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    public function getDatatypes()
    {

        $component = self::COMPONENT_ID;
        $output = array();

        foreach ($this->config['rows'] as $j => $configRow) {
            if (!empty($configRow['configuration']['input'])) {
                $backend = (isset($configRow['configuration']['backend'])) ? $configRow['configuration']['backend'] : "";
                foreach ($configRow['configuration']['input'] as $input) {
                    if (!empty($input['datatypes'])) {
                        foreach ($input['datatypes'] as $column => $type) {
                            try {
                                $this->logger->debug("Found {$component} column metadata for " . $input['source'] . "." . $column);
                                $output[$input['source'] . "." . $column][] = [
                                    "key" => "datatype." . $backend . "." . $component,
                                    "value" => $type
                                ];
                            } catch (ClientException $e) {
                                if ($e->getCode() === 404) {
                                    $this->logger->error("Could not find the object, must be an old config " . $e->getMessage());
                                }
                            }
                        }
                    }
                }
            }
        }
        return $output;
    }
}
