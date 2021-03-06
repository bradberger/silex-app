[![Latest Stable Version](https://poser.pugx.org/bitolaco/silex-app/v/stable)](https://packagist.org/packages/bitolaco/silex-app)
[![Total Downloads](https://poser.pugx.org/bitolaco/silex-app/downloads)](https://packagist.org/packages/bitolaco/silex-app)
[![Latest Unstable Version](https://poser.pugx.org/bitolaco/silex-app/v/unstable)](https://packagist.org/packages/bitolaco/silex-app)
[![License](https://poser.pugx.org/bitolaco/silex-app/license)](https://packagist.org/packages/bitolaco/silex-app)

This package provides a quick way to a Silex app and
various service providers via a single yaml configuration
file, and set up different configurations based on environments.

It uses Eloquent by default instead of Doctrine, because adding an ORM
is an upgrade in our opinion!

Documentation forthcoming!

## Usage

```php
<?php
require_once dirname(__FILE__) . '/vendor/autoload.php';
$app = \BitolaCo\Silex\App::setup("config.yml");
$app->run();
```

```yaml
debug: false
eloquent:
    connection:
    connections:
        default:
            driver: mysql
            host: localhost
            database: test
            username: root
            password:
            charset: utf8
            prefix:
            collation: utf8_unicode_ci
            logging:
    cache:
monolog:
    logfile: application.log
    bubble: true
    permission: null
    level: DEBUG|INFO|WARNING|ERROR
    name: MySilexApp
swiftmailer:
    options:
        host: localhost
        port: 25
        username:
        password:
        encryption:
        auth_mode:
translator:
session:
cache:
twig:
routes:
  someName:
    path: /
    defaults:
      _controller: Foo::bar
  _demo:
    path: /demo/{id}
    defaults:
      _controller: Foo::bar
    requirements:
      id: \d+
servers:
  dev:
    - YOUR-MACHINE-NAME
environments:
  dev:
    debug: true
    monolog: development.log
```

### Service Providers

The following service providers are currently supported, with more on the way.

- Eloquent
- Monolog
- Swiftmailer
- Translator
- Session
- Twig
- Cache

#### Monolog

The [monolog parameters](http://silex.sensiolabs.org/doc/providers/monolog.html) and what they 
mean [can be found here](http://silex.sensiolabs.org/doc/providers/monolog.html). It's a one-to-one
direct compilation of the parameters there. 

If you're setting the `level` parameter, be sure you're using a string (like "INFO") instead
of the PHP constant like `Logger::INFO`, since the constant won't work. 

### Environments

The main config key `servers` allows you to set up define environments. The key
is the name of the environment, and the value is an array of hostnames that are in
that environment.

When using this option, you can then override default the default configuration by 
defining variables in the environment[<name>] object.

For example, if you want debugging on your local machine, who's hostname is `dev-machine`,
you could set things up like this:


```yaml
debug: false
servers:
	dev:
		- dev-machine
environment:
	dev:
		debug: true
``` 

In a production setting (i.e. any server without a hostname of `dev-machine` debugging 
would be off, but on your local development machine, it would be on.

Machines can be assigned multiple environments, and the last environment variable 
overwrites all the previous ones.

### Routing

In the config `yaml` file, the section for routing is used as follows:

```yaml
routes:
	someName:
    	path: /
    	defaults:
      		_controller: Foo::bar
	_demo:
    	path: /demo/{id}
    	defaults:
      		_controller: Foo::bar
    	requirements:
      		id: \d+
```

