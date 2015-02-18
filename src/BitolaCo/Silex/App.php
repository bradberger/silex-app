<?php

namespace BitolaCo\Silex;

use Silex\Application;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\SessionServiceProvider;

use DerAlex\Silex\YamlConfigServiceProvider;

use Symfony\Component\Routing\RouteCollection;

class App
{
    static public function setup($base, $file)
    {

        $app = new Application();
        $app->register(
            new YamlConfigServiceProvider(
                self::_joinPath($base, $file)
            )
        );

        $app['settings'] = self::_mergeEnvironments($app['config']);


        $app['debug'] = $app['settings']['debug'] ?: false;
        if (!empty($app['settings']['session'])) {
            $app->register(new SessionServiceProvider());
        }

        if (!empty($app['settings']['monolog'])) {

            $monologCfg = (array)$app['settings']['monolog'];
            if (array_key_exists('logfile', $monologCfg)) {
                $monologCfg['logfile'] = self::_joinPath($base, $monologCfg['logfile']);
            } else {
                $monologCfg['logfile'] = self::_joinPath($base, 'application.log');
            }

            $m = array();
            foreach ($monologCfg as $k => $v) {
                $m['monolog.' . $k] = $v;
            }

            $app->register(new MonologServiceProvider(),
                $m
            );
        }

        $app->register(new ValidatorServiceProvider());

        if (!empty($app['settings']['eloquent'])) {
            $app->register(
                new CapsuleServiceProvider(),
                array(
                    'capsule.connections' =>
                        $app['settings']['eloquent']['connections'] ?: null,
                    'capsule.connection' =>
                        $app['settings']['eloquent']['connections'] ?: null,
                    'capsule.cache' =>
                        $app['settings']['eloquent']['cache'] ?: null,
                )
            );
            $app['capsule'];
        }

        if (!empty($app['settings']['swiftmailer'])) {
            $app['swiftmailer.options'] = $app['settings']['swiftmailer']['options'];
        }

        if (!empty($app['settings']['twig'])) {
            $app->register(
                new TwigServiceProvider(),
                array(
                    'twig.path' => self::_joinPath($base, $app['settings']['twig'])
                )
            );
        }

        if (!empty($app['settings']['translator'])) {
            $app->register(
                new TranslationServiceProvider(),
                (array)$app['settings']['translator']
            );
        }

        if (!empty($app['settings']['routes'])) {
            $app['routes'] = $app->extend(
                'routes',
                function (
                    RouteCollection $routes,
                    Application $app
                ) {
                    $loader = new RouteLoader();
                    $routes->addCollection(
                        $loader->load($app['settings']['routes'])
                    );
                    return $routes;
                }
            );
        }

        return $app;

    }

    private static function _joinPath($dir, $file)
    {
        $join = (substr($dir, -1) === "/" || substr($file, 0, 1) === "/") ? "" : "/";
        return $dir . $join . $file;
    }

    private static function _mergeEnvironments($config)
    {
        $hostname = gethostname();
        $environments = empty($config['environments']) ? array() : $config['environments'];
        $servers = empty($config['servers']) ? array() : $config['servers'];
        $config['environment'] = array();
        foreach ($servers as $envKey => $hosts) {
            if (in_array($hostname, $hosts)) {
                array_push($config['environment'], $envKey);
                if (array_key_exists($envKey, $environments)) {
                    $config = array_merge($config, $environments[$envKey]);
                }
            }
        }
        return $config;
    }
}
