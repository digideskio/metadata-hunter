<?php

use Keboola\MetadataHunter\Application;
use Keboola\MetadataHunter\Exception\ApplicationException;
use Keboola\MetadataHunter\Exception\UserException;
use Keboola\MetadataHunter\Logger;
use Symfony\Component\Yaml;

require_once(dirname(__FILE__) . "/bootstrap.php");

echo "FUCK YOU: " . getenv("KBC_TOKEN");
defined('KBC_URL') || define('KBC_URL', getenv('KBC_URL')? getenv('KBC_URL') : 'https://connection.keboola.com');
defined('KBC_TOKEN') || define('KBC_TOKEN', getenv('KBC_TOKEN')? getenv('KBC_TOKEN') : 'token');


$logger = new Logger(APP_NAME);
try {
    $arguments = getopt("d::", ["data::"]);
    if (!isset($arguments["data"])) {
        throw new UserException('Data folder not set.');
    }

    $config = Yaml\Yaml::parse(file_get_contents($arguments["data"] . "/config.yml"));

    var_dump($config);

    $config['parameters']['data_dir'] = $arguments['data'];
    $app = new Application($config, getenv("KBC_TOKEN"));
    $result = $app->run();
    echo "RAN APP!";
    if (isset($config['action'])) {
        echo json_encode($result);
        exit(0);
    }
} catch (UserException $e) {
    if (isset($config['action']) && $config['action'] != 'run') {
        echo json_encode([
            'status' => 'error',
            'error' => 'User Error',
            'message' => $e->getMessage()
        ]);
    } else {
        $logger->log('error', $e->getMessage(), (array) $e->getData());
    }
    exit(1);
} catch (ApplicationException $e) {
    $logger->log('error', $e->getMessage(), (array) $e->getData());
    exit(2);
} catch (\Exception $e) {
    $logger->log('error', $e->getMessage(), [
        'errFile' => $e->getFile(),
        'errLine' => $e->getLine(),
        'trace' => $e->getTrace()
    ]);
    exit(2);
}
$logger->log('info', "Metadata Writer finished successfully.");

exit(0);
