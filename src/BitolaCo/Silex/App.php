<?php

namespace BitolaCo\Silex;

class App
{
	private static function _mergeEnvironments($config) {
		$hostname = gethostname();
		$environments = empty($config['environments']) ? array() : $config['environments'];
		$servers = empty($config['servers']) ? array() : $config['servers'];
		$config['environment'] = array();
		foreach($servers as $envKey => $hosts) {
			if(in_array($hostname, $hosts)) {
				array_push($config['environment'], $envKey);
				if(array_key_exists($envKey, $environments)) {
					$config = array_merge($config, $environments[$envKey]);
				}
			}
		}
		return $config;
	}
    
    private static function _joinPath($dir, $file)
    {
        $join = (substr($dir, -1) === "/" || substr($file, 0, 1) === "/") ? "" : "/";
        return $dir.$join.$file;
    }
	
    static public function setup($base, $file)
    {

        $app = new \Silex\Application();
        $app->register(
            new \DerAlex\Silex\YamlConfigServiceProvider(
                self::_joinPath($base, $file)
            )
        );

		$app['settings'] = self::_mergeEnvironments($app['config']);

		
		$app['debug'] = $app['settings']['debug'] ? : false;
        if (! empty($app['settings']['session'])) {
            $app->register(new \Silex\Provider\SessionServiceProvider());
        }

        if (! empty($app['settings']['monolog'])) {
            $app->register(
                new \Silex\Provider\MonologServiceProvider(),
                array(
                    'monolog.logfile' => self::_joinPath($base, $app['settings']['monolog'])
                )
            );
        }
        
        $app->register(new \Silex\Provider\ValidatorServiceProvider());

        if (! empty($app['settings']['eloquent'])) {
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

        if (! empty($app['settings']['swiftmailer'])) {
            $app['swiftmailer.options'] = $app['settings']['swiftmailer']['options'];
        }

        if (! empty($app['settings']['twig'])) {
            $app->register(
                new \Silex\Provider\TwigServiceProvider(),
                array(
                    'twig.path' => self::_joinPath($base, $app['settings']['twig'])
                )
            );
        }

        if (! empty($app['settings']['translator'])) {
            $app->register(
                new \Silex\Provider\TranslationServiceProvider(),
                (array) $app['settings']['translator']
            );
        }
		
		if (! empty($app['settings']['routes'])) {
			$app['routes'] = $app->extend(
				'routes', 
				function (
					\Symfony\Component\Routing\RouteCollection $routes, 
					\Silex\Application $app
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
}