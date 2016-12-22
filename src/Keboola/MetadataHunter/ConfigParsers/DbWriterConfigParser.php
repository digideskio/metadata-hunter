<?php

namespace Keboola\MetadataHunter\ConfigParsers;

use Keboola\MetadataHunter\Logger;
use Keboola\StorageApi\ClientException;

class DbWriterConfigParser
{
    private $component;

    private $config;

    /** @var  Logger */
    private $logger;

    public function __construct($component, $config, $logger)
    {
        $this->component = $component;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function getDatatypes()
    {
        $output = array();
        if (isset($this->config['configuration']['parameters'])) {
            if (isset($this->config['configuration']['parameters']['tables'])) {
                $tables = $this->config['configuration']['parameters']['tables'];
                foreach ($tables as $table) {
                    $tableId = $table['tableId'];
                    foreach ($table['items'] as $item) {
                        $output[$tableId . "." . $item['name']][] = [
                            "key" => "datatype." . $this->component,
                            "value" => $item['type'] ."(" . $item['size'] . ")"
                        ];
                    }
                }
            }
        }
        return $output;
    }
}
