<?php
// CLI must be called by cli php
/*if (php_sapi_name() != 'cli') {
    syslog(LOG_ERR, "cli $cli_action call attempted by non-cli. (".php_sapi_name());
    http_response_code(400);
    exit;
}*/

/*echo php_sapi_name().'<br />';

$ipaddress = '';
if (getenv('HTTP_CLIENT_IP'))
    $ipaddress = getenv('HTTP_CLIENT_IP');
else if(getenv('HTTP_X_FORWARDED_FOR'))
    $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
else if(getenv('HTTP_X_FORWARDED'))
    $ipaddress = getenv('HTTP_X_FORWARDED');
else if(getenv('HTTP_FORWARDED_FOR'))
    $ipaddress = getenv('HTTP_FORWARDED_FOR');
else if(getenv('HTTP_FORWARDED'))
   $ipaddress = getenv('HTTP_FORWARDED');
else if(getenv('REMOTE_ADDR'))
    $ipaddress = getenv('REMOTE_ADDR');
else
    $ipaddress = 'UNKNOWN';

file_put_contents("ips.txt", $ipaddress);
exit;*/

// Ensure $cli_action is set
if (!isset($cli_action)) {
    echo 'ERROR: $cli_action must be set in calling script.';
    syslog(LOG_ERR, '$cli_action must be set in calling script');
    http_response_code(400);
    exit;
}

// Handle errors by writing to log
function cli_error_handler($log_level, $log_text, $error_file, $error_line) { 
    syslog(LOG_ERR, 'CLI Error: ' . $log_text . ' in ' . $error_file . ': ' . $error_line); 
    echo 'CLI Error: ' . $log_text . ' in ' . $error_file . ': ' . $error_line; 
}
set_error_handler('cli_error_handler');

// Configuration not present in CLI (vs web)
chdir(__DIR__.'/../admin');
set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__)) . '../admin/');
$_SERVER['HTTP_HOST'] = '';

// Version
define('VERSION', '2.3.0.2');

// Configuration (note we're using the admin config)
require_once('../admin/config.php');

// Configuration check
if (!defined('DIR_APPLICATION')) {
    echo "ERROR: cli $cli_action call missing configuration.";
    $log->write("ERROR: cli $cli_action call missing configuration.");
    http_response_code(400);
    exit;
}

// Startup
require_once(DIR_SYSTEM . 'startup.php');

// Registry
$registry = new Registry();

// Config
$config = new Config();
$config->load('default');
$config->load('admin');
$registry->set('config', $config);

// Loader
$loader = new Loader($registry);
$registry->set('load', $loader);

// Event
$event = new Event($registry);
$registry->set('event', $event);

// Event Register
if ($config->has('action_event')) {
    foreach ($config->get('action_event') as $key => $value) {
        $event->register($key, new Action($value));
    }
}

// Database
if ($config->get('db_autostart')) {
    $registry->set('db', new DB($config->get('db_type'), $config->get('db_hostname'), $config->get('db_username'), $config->get('db_password'), $config->get('db_database'), $config->get('db_port')));
}

// Url
if ($config->get('url_autostart')) {
    $registry->set('url', new Url($config->get('site_base'), $config->get('site_ssl')));
}

// Log 
$log = new Log($config->get('config_error_filename'));
$registry->set('log', $log);

function error_handler($errno, $errstr, $errfile, $errline) {
    global $log, $config;

    switch ($errno) {
        case E_NOTICE:
        case E_USER_NOTICE:
            $error = 'Notice';
            break;
        case E_WARNING:
        case E_USER_WARNING:
            $error = 'Warning';
            break;
        case E_ERROR:
        case E_USER_ERROR:
            $error = 'Fatal Error';
            break;
        default:
            $error = 'Unknown';
            break;
    }

    if ($config->get('config_error_display')) {
        echo "\n".'PHP ' . $error . ':  ' . $errstr . ' in ' . $errfile . ' on line ' . $errline."\n";
    }

    if ($config->get('config_error_log')) {
        $log->write('PHP ' . $error . ':  ' . $errstr . ' in ' . $errfile . ' on line ' . $errline);
    }

    return true;
}
set_error_handler('error_handler');

$request = new Request();
$registry->set('request', $request);

$response = new Response();
$response->addHeader('Content-Type: text/html; charset=utf-8');
$registry->set('response', $response); 


$session = new Session();
if ($config->get('session_autostart')) {
    $session->start();
}
$registry->set('session', $session);

// Language
$language = new Language($config->get('language_default'));
$language->load($config->get('language_default'));
$registry->set('language', $language);   

$document = new Document();
$registry->set('document', $document);      

// Config Autoload
if ($config->has('config_autoload')) {
    foreach ($config->get('config_autoload') as $value) {
        $loader->config($value);
    }
}

// Language Autoload
if ($config->has('language_autoload')) {
    foreach ($config->get('language_autoload') as $value) {
        $loader->language($value);
    }
}

// Library Autoload
if ($config->has('library_autoload')) {
    foreach ($config->get('library_autoload') as $value) {
        $loader->library($value);
    }
}

// Model Autoload
if ($config->has('model_autoload')) {
    foreach ($config->get('model_autoload') as $value) {
        $loader->model($value);
    }
}

$controller = new Front($registry);
$action = new Action($cli_action);
$controller->dispatch($action, new Action('error/not_found'));

// Output
$response->output();

?>