<?php

// Set default timezone
date_default_timezone_set('Asia/Jakarta');

// Load environment variables
if (file_exists(BASE_PATH . '/.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(BASE_PATH);
    $dotenv->load();
}

defined('BASE_PATH') || define('BASE_PATH', getenv('BASE_PATH') ?: realpath(dirname(__FILE__) . '/../..'));
defined('APP_PATH') || define('APP_PATH', BASE_PATH . '/app');

return new \Phalcon\Config\Config([
    'database' => [
        'adapter'  => 'Mysql',
        'host'     => $_ENV['DB_HOST'] ?? 'localhost',
        'username' => $_ENV['DB_USERNAME'] ?? 'root',
        'password' => $_ENV['DB_PASSWORD'] ?? 'password',
        'dbname'   => $_ENV['DB_NAME'] ?? 'todo_api',
        'charset'  => $_ENV['DB_CHARSET'] ?? 'utf8',
    ],
    'application' => [
        'appDir'           => APP_PATH . '/',
        'viewsDir'       => APP_PATH . '/views/',            
        'controllersDir'   => APP_PATH . '/controllers/',       
        'modelsDir'        => APP_PATH . '/models/',            
        'servicesDir'      => APP_PATH . '/services/',          
        'repositoriesDir'  => APP_PATH . '/repositories/',      
        'middlewareDir'    => APP_PATH . '/middleware/',         
        'validatorsDir'    => APP_PATH . '/validators/',         
        'usecasesDir'      => APP_PATH . '/usecases/',
        'migrationsDir'    => BASE_PATH . '/database/migrations/', 
        'logsDir'          => BASE_PATH . '/storage/logs/',     
        'cacheDir'         => BASE_PATH . '/storage/cache/',     
        'baseUri'          => '/',
    ]
]);