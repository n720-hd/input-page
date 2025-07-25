<?php
declare(strict_types=1);

use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\Application;

/**
 * Clean Architecture Bootstrap
 * Entry point that sets up the application following Uncle Bob's architecture
 */

// Error reporting
error_reporting(E_ALL);

// Define paths
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

try {
    // Load Composer autoloader first
    require_once BASE_PATH . '/vendor/autoload.php';
    
    // Load configuration first
    $config = include APP_PATH . '/config/config.php';
    
    // Load class autoloader (needs config)
    include APP_PATH . '/config/loader.php';
    
    // Create DI container (Composition Root)
    $di = new FactoryDefault();
    $di->setShared('config', $config);

    // Load clean services (our composition root)
    include APP_PATH . '/config/services.php';

    // Set up clean router
    include APP_PATH . '/config/router.php';

    // Create application
    $application = new Application($di);
    
    // Handle the request
    echo $application->handle($_SERVER['REQUEST_URI'])->getContent();

} catch (\Exception $e) {
    // Always display error for debugging
    echo "Bootstrap Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}