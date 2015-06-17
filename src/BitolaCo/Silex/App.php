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

    public $app;
    public $base;
    public $file;

    /**
     * @param string $base The path to the project base directory
     * @param string $file The yaml settings, relative to the base
     *                     directory of the project
     */
    public function __construct($base, $file)
    {
        $this->base = $base;
        $this->file = $file;
        $this->app = new Application();

        $this->_registerYaml();
        $this->_initSettings();
        $this->_setDebug();
        $this->_registerSession();
        $this->_registerMonolog();
        $this->_registerValidator();
        $this->_registerEloquent();
        $this->_registerSwiftMailer();
        $this->_registerTwig();
        $this->_registerTranslator();
        $this->_registerRoutes();

        return $this->app;
    }

    /**
     * @param $base
     * @param $file
     * @return App
     */
    public static function setup($base, $file)
    {
        return new App($base, $file);
    }

    /**
     *
     */
    private function _registerMonolog()
    {
        if (!empty($this->app['settings']['monolog'])) {
            $monologCfg = (array)$this->app['settings']['monolog'];
            if (array_key_exists('logfile', $monologCfg)) {
                // If relative path,
                if (strpos($this->app['settings']['monolog'], '/') === 0) {
                    $monologCfg['logfile'] = self::_joinPath($this->base, $monologCfg['logfile']);
                }
            } else {
                $monologCfg['logfile'] = self::_joinPath($this->base, 'application.log');
            }

            $m = [];
            foreach ($monologCfg as $k => $v) {
                $m['monolog.' . $k] = $v;
            }

            $this->app->register(new MonologServiceProvider(), $m);
        }
    }

    /**
     *
     */
    private function _registerYaml()
    {
        $this->app->register(
            new YamlConfigServiceProvider(
                self::_joinPath($this->base, $this->file)
            )
        );
    }

    /**
     *
     */
    private function _registerSession()
    {
        if (!empty($this->app['settings']['session'])) {
            $this->app->register(new SessionServiceProvider());
        }
    }

    /**
     *
     */
    private function _registerValidator()
    {
        $this->app->register(new ValidatorServiceProvider());
    }

    /**
     *
     */
    private function _initSettings()
    {
        $this->app['settings'] = self::_mergeEnvironments($this->app['config']);
    }

    /**
     *
     */
    private function _setDebug()
    {
        $this->app['debug'] = $this->app['settings']['debug'] ?: false;
    }

    /**
     *
     */
    private function _registerSwiftMailer()
    {
        if (!empty($this->app['settings']['swiftmailer'])) {
            $this->app['swiftmailer.options'] = $this->app['settings']['swiftmailer']['options'];
        }
    }

    /**
     *
     */
    private function _registerTranslator()
    {
        if (!empty($this->app['settings']['translator'])) {
            $this->app->register(
                new TranslationServiceProvider(),
                (array)$this->app['settings']['translator']
            );
        }
    }

    /**
     *
     */
    private function _registerRoutes()
    {
        if (!empty($this->app['settings']['routes'])) {
            $this->app['routes'] = $this->app->extend(
                'routes',
                function (
                    RouteCollection $routes,
                    Application $app
                ) {
                    $loader = new RouteLoader();
                    $routes->addCollection(
                        $loader->load($this->app['settings']['routes'])
                    );
                    return $routes;
                }
            );
        }
    }

    /**
     *
     */
    private function _registerTwig()
    {
        if (!empty($this->app['settings']['twig'])) {
            $this->app->register(
                new TwigServiceProvider(),
                [
                    'twig.path' => self::_joinPath($this->base, $this->app['settings']['twig'])
                ]
            );
        }
    }

    /**
     *
     */
    private function _registerEloquent()
    {
        if (!empty($this->app['settings']['eloquent'])) {
            $this->app->register(
                new CapsuleServiceProvider(),
                [
                    'capsule.connections' =>
                        $this->app['settings']['eloquent']['connections'] ?: null,
                    'capsule.connection' =>
                        $this->app['settings']['eloquent']['connections'] ?: null,
                    'capsule.cache' =>
                        $this->app['settings']['eloquent']['cache'] ?: null,
                ]
            );
            $this->app['capsule'];
        }
    }

    /**
     * @param $dir
     * @param $file
     * @return string
     */
    private function _joinPath($dir, $file)
    {
        $join = (substr($dir, -1) === "/" || substr($file, 0, 1) === "/") ? "" : "/";
        return $dir . $join . $file;
    }

    /**
     * @param $config
     * @return array
     */
    private function _mergeEnvironments($config)
    {
        $hostname = gethostname();
        $environments = empty($config['environments']) ? [] : $config['environments'];
        $servers = empty($config['servers']) ? [] : $config['servers'];
        $config['environment'] = [];
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
