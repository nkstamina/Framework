<?php
namespace Nkstamina\Framework;

use Nkstamina\Framework\Common\Utils;
use Nkstamina\Framework\Controller\ControllerResolver;
use Nkstamina\Framework\Provider\ConfigServiceProvider;
use Nkstamina\Framework\Provider\DatabaseServiceProvider;
use Nkstamina\Framework\Provider\RoutingServiceProvider;
use Nkstamina\Framework\Provider\TemplatingServiceProvider;
use Pimple\ServiceProviderInterface;
use Pimple\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Application
 * @package Nkstamina\Framework
 */
abstract class Application extends Container implements HttpKernelInterface, ApplicationInterface
{
    const VERSION         = '0.1.0-DEV';
    const VERSION_ID      = '010DEV';
    const MAJOR_VERSION   = '0';
    const MINOR_VERSION   = '1';
    const RELEASE_VERSION = '0';
    const EXTRA_VERSION   = '';
    const EARLY_EVENT     = 512;
    const LATE_EVENT      = -512;

    /**
     * @var array An array of providers
     */
    protected $providers = [];

    /**
     * @var array An array of extensions
     */
    protected $extensions = [];

    /**
     * @var bool Is application already booted?
     */
    protected $booted = false;

    /**
     * Constructor
     *
     * @param array $values
     */
    public function __construct(array $values = [])
    {
        parent::__construct($values);

        $app = $this;

        #$this['app.root.dir']       = realpath(__DIR__ . '/../../../../../');
        $this['app.root.dir']       = realpath(__DIR__ . '/../../../nkstamina');
        $this['app.extensions.dir'] = $app['app.root.dir'].'/extensions';
        $this['app.dir']            = $app['app.root.dir'].'/app';
        $this['app.config.dir']     = $app['app.dir'].'/config';
        $this['app.cache.dir']      = $app['app.dir'].'/cache';

        // twig
        $this['app.templates.path']   = $app['app.dir'].'/Resources/views';
        $this['twig.cache.directory'] = $this['app.cache.dir'].'/templates';
        $this['twig.cache_templates'] = false;

        // To switch between prod & dev
        // just set the APP_ENV environment variable:
        // in apache: SetEnv APP_ENV dev
        // in nginx with fcgi: fastcgi_param APP_ENV dev
        $this['env']                = getenv('APP_ENV') ? : 'dev';

        $this['request.http_port']  = 80;
        $this['request.https_port'] = 443;
        $this['debug']              = false;
        $this['charset']            = 'UTF-8';
        $this['logger']             = null;
        $this['use_cache']          = false;

        $this['resolver'] = function () use ($app) {
            return new ControllerResolver($app, $app['logger']);
        };

        $this['event_dispatcher_class'] = 'Symfony\\Component\\EventDispatcher\\EventDispatcher';
        $this['dispatcher'] = function () use ($app) {
            return new $app['event_dispatcher_class'];
        };

        $this['kernel'] = function () use ($app) {
            return new HttpKernel($app['dispatcher'], $app['resolver']);
        };

        $this['request_error'] = $this->protect(function () {
            throw new \RuntimeException('Accessed request service outside of request scope. Try moving that call to a before handler or controller.');
        });

        $this['request'] = $this['request_error'];

        $this->register(new ConfigServiceProvider($app));
        $this->register(new RoutingServiceProvider($app));
        $this->register(new TemplatingServiceProvider($app));
        $this->register(new DatabaseServiceProvider($app));

        // load Application's configuration parameters
        $this['app.parameters'] = $app->factory(function () use ($app) {
            return $this->loadApplicationParameters($app);
        });

        // Load Application's extensions
        $this['app.extensions'] = $app->factory(function () use ($app) {
            return $this->initializeExtensions($app);
        });

        $this['dispatcher']->addSubscriber(new RouterListener($app['matcher']));

        foreach ($values as $key => $value) {
            $this[$key] = $value;
        }
    }

    /**
     * Registers a service provider.
     *
     * @param ServiceProviderInterface $provider
     * @param array                    $values
     * @throws \InvalidArgumentException
     *
     * @return $this|static
     */
    public function register(ServiceProviderInterface $provider, array $values = [])
    {
        $this->providers[] = $provider;

        parent::register($provider, $values);

        return $this;
    }

    /**
     * Boots all providers
     *
     * @return bool
     */
    public function boot()
    {
        if (!$this->booted) {
            // boot all providers
            foreach ($this->providers as $provider) {
                $provider->boot($this);
            }

            // boot all extensions
            foreach($this->extensions as $extension) {
                $extension->setApplication($this);
                $extension->boot($this);
            }
        }

        $this->booted = true;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        if (!$this->booted) {
            $this->boot();
        }

        $current = HttpKernelInterface::SUB_REQUEST === $type ? $this['request'] : $this['request_error'];

        $this['request'] = $request;

        $request->attributes->add($this['matcher']->match($request->getPathInfo()));

        $response =  $this['kernel']->handle($request, $type, $catch);

        $this['request'] = $current;

        return $response;
    }

    /**
     * Handles the request and delivers the response.
     *
     * @param Request|null $request Request to process
     */
    public function run(Request $request = null)
    {
        if (null === $request) {
            $request = Request::createFromGlobals();
        }

        $response = $this->handle($request);
        $response->send();
        $this->terminate($request, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function terminate(Request $request, Response $response)
    {
        $this['kernel']->terminate($request, $response);
    }

    /**
     * Returns an array of all providers loaded
     *
     * @return array
     */
    public function getProviders()
    {
        return $this->providers;
    }

    /**
     * Sets a value for a key
     *
     * @param $key
     * @param $value
     */
    public function setValue($key, $value)
    {
        $this[$key] = $value;
    }

    /**
     * Returns a specific value for a key
     *
     * @param $key
     *
     * @return mixed
     */
    public function getValue($key)
    {
        return $this[$key];
    }

    /**
     * Initialize extensions
     *
     * @throws \LogicException
     */
    protected function initializeExtensions()
    {
        $this->extensions = [];

        foreach($this->registerExtensions() as $extension) {
            $name = $extension->getName();

            if (isset($this->extensions[$name])) {
                throw new \LogicException(sprintf('Trying to register two extensions with the same name "%s"', $name));
            }

            if (0 === strpos($extension->getPath(), 'extensions')) {
                throw new \LogicException(sprintf('The extension "%s" shoud be installed in the "%s" directory', $name, $this['app.extensions.dir']));
            }

            $this->extensions[$name] = $extension;
        }

        return $this->extensions;
    }


    /**
     * Loads application's parameters
     *
     * @param Application $app
     *
     * @return array
     */
    protected function loadApplicationParameters(Application $app)
    {
        $parameters = [];

        if (Utils::isDirectoryValid($app['app.config.dir'])) {
            $files = $app['config.finder']
                ->files()
                ->name('*.yml')
                ->in($app['app.config.dir'])
            ;

            $yaml = $app['config.parser'];

            foreach ($files as $file) {
                try {
                    $parameters[$file->getRelativePathname()] = [$yaml->parse(file_get_contents($file->getRealpath()))];
                } catch (ParseException $e) {
                    printf("Unable to parse the YAML string: %s", $e->getMessage());
                }
            }
        }

        return $parameters;
    }

    /**
     * Returns an array of all extensions loaded
     *
     * @return array
     */
    protected function getExtensions()
    {
        return $this->extensions;
    }
}
