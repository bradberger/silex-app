<?php

namespace BitolaCo\Silex;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteLoader implements LoaderInterface
{
    private $loaded = false;

    public function load($resource, $type = null)
    {
    	
        if (true === $this->loaded) {
            throw new \RuntimeException('Do not add the "extra" loader twice');
        }

        $routes = new RouteCollection();
		$defaultRoute = array(
			'resource' => null,
			'type' => null,
			'path' => null,
			'defaults' => array(),
			'requirements' => array(),
			'prefix' => null
		);
				
		foreach($resource as $routeName => $route) {
			$params = array_merge($defaultRoute, $route);
			$routes->add(
				$routeName, 
				new Route($params['path'], $params['defaults'], $params['requirements'])
			);
		}

        $this->loaded = true;
        return $routes;

    }

    public function supports($resource, $type = null)
    {
        return 'extra' === $type;
    }

    public function getResolver()
    {
        // needed, but can be blank, unless you want to load other resources
        // and if you do, using the Loader base class is easier (see below)
    }

    public function setResolver(LoaderResolverInterface $resolver)
    {
        // same as above
    }
}