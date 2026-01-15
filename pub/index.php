<?php

use Juzdy\Bootstrap;

session_start();
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once realpath(__DIR__ . '/../vendor/autoload.php');

/**
 * Initialize Resource helper
 * SkibidiMadness hardcode: to remove
 */
//\Fasty\Helper\Resource::init(__DIR__ . '/..'); //@todo: remove

/**
 * Initialize configuration
 * Loads all PHP configuration files from the given glob pattern.
 * Priority of loading is determined by the order of files returned by glob().
 * Use filenames to control load order if necessary, e.g., prefix with numbers or letters.
 * @param string $files Glob pattern to the configuration files.
 */
\Juzdy\Config::init(__DIR__ . '/../etc/config/*.php');

/**
 * Initialize ErrorHandler
 * @todo: refactor
 */
\Juzdy\Error\ErrorHandler::init();

/**
 * Bootstrap and run the application
 * Uses the built-in dependency injection container to resolve and run the application.
 * Allows for easy swapping of application implementations via container preferences.
 * @see \Juzdy\Bootstrap
 * 
 * Third-party Container may be used
 */
(new \Juzdy\Container\Container())->get(Bootstrap::class)->boot();