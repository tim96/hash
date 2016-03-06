<?php
/**
 * Created by PhpStorm.
 * User: tim
 * Date: 9/6/2015
 * Time: 7:04 PM
 */

use Symfony\Component\HttpFoundation\Response;

$debug = $app['debug'];
$app->error(function (\Exception $e, $code) use ($debug) {
    switch ($code) {
        case 404:
            $message = 'The requested page could not be found.';
            break;
        default:
            if ($debug) {
                echo "Fatal error: ".$e->getMessage()."<br/>";
            }
            $message = 'We are sorry, but something went terribly wrong.';
            break;
    }

    return new Response($message);
});

// $env = $debug ? 'dev' : 'prod';
// $app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__."/../config/$env.json"));
$app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__."/../config/config.json"));