<?php
/**
 * Created by PhpStorm.
 * User: wolfbolin
 * Date: 2019/3/22
 * Time: 16:59
 */

use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

$container = $app->getContainer();

// Sentry组建初始化
$container['sentry_client'] = function (Container $a) {
    $sentry_dsn = $a->get('Sentry_DSN');
    $sentry = new Raven_Client($sentry_dsn,
        [
            'version' => $a->get('Version'),
            'php_version' => phpversion()
        ]
    );
    return $sentry;
};

// MongoDB组件初始化
$container['mongodb_client'] = function (Container $a) {
    $unix = "mongodb://" . ($a->get('MongoDB')['host']);
    if (!empty($a->get('MongoDB')['port'])) {
        $unix .= ":" . $a->get('MongoDB')['port'];
    }
    $uriOptions = [];
    foreach ($a->get('MongoDB') as $key => $value) {
        if (!in_array($key, ['occam', 'entity', 'host', 'port']) && !empty($value)) {
            $uriOptions[$key] = $value;
        }
    }

    $client = new MongoDB\Driver\Manager($unix, $uriOptions);

    return $client;
};

// 异常访问处理
$container['notFoundHandler'] = function ($a) {
    return function ($request, $response) use ($a) {
        return WolfBolin\Slim\HTTP\Not_found($response);
    };
};
$container['notAllowedHandler'] = function ($a) {
    return function ($request, $response) use ($a) {
        return WolfBolin\Slim\HTTP\Not_allowed($response);
    };
};
$container['errorHandler'] = function ($a) {
    return function (Request $request, Response $response, $exception) use ($a) {
        $sentry = new Raven_Client($a['Sentry_DSN']);
        $sentry->captureException($exception, array(
            'extra' => array(
                'URL' => $request->getUri(),
                'Method' => $request->getMethod(),
                'Body' => $request->getBody()
            ),
            'tags' => array(
                'version' => $a['Version']
            )
        ));
        return WolfBolin\Slim\HTTP\Server_error($response);
    };
};
$container['phpErrorHandler'] = function ($a) {
    return function (Request $request, Response $response, $exception) use ($a) {
        $sentry = new Raven_Client($a['Sentry_DSN']);
        $sentry->captureException($exception, array(
            'extra' => array(
                'URL' => $request->getUri(),
                'Method' => $request->getMethod(),
                'Body' => $request->getBody()
            ),
            'tags' => array(
                'version' => $a['Version']
            )
        ));
        return WolfBolin\Slim\HTTP\Server_error($response);
    };
};

