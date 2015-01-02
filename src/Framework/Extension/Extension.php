<?php
namespace Nkstamina\Framework\Extension;

use Nkstamina\Framework\Application;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Exception\BadMethodCallException;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\Config\Resource\FileResource;

/**
 * Class Extension
 * @package Nkstamina\Framework\Extension
 */
abstract class Extension implements ExtensionInterface, ConfigurationExtensionInterface
{
    /**
     * @var Extension name
     */
    public $name;

    /**
     * @var Extension path
     */
    public $path;

    /**
     * @var Application
     */
    public $application;

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown()
    {
    }

    /**
     * Gets the Extension namespace.
     *
     * @return string The Extension namespace
     */
    public function getNamespace()
    {
        $class = get_class($this);

        return substr($class, 0, strrpos($class, '\\'));
    }

    /**
     * Returns the extension name (the class short name).
     *
     * @return string The Extension name
     */
    public function getName()
    {
        if (null !== $this->name) {
            return $this->name;
        }

        $name = get_class($this);
        $pos  = strrpos($name, '\\');

        return $this->name = false === $pos ? $name : substr($name, $pos + 1);
    }

    /**
     * Gets the Extension directory path.
     *
     * @return string The Extension absolute path
     */
    public function getPath()
    {
        if (null === $this->path) {
            $reflected  = new \ReflectionObject($this);
            $this->path = dirname($reflected->getFileName());
        }

        return $this->path;
    }

    /**
     * @param Application $application
     */
    public function setApplication(Application $application)
    {
        $this->application = $application;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, Application $app)
    {
        $reflected = new \ReflectionClass($this);
        $namespace = $reflected->getNamespaceName();

        $class = $namespace.'\\Configuration';
        if (class_exists($class)) {
            // [wip]
            //$r = new \ReflectionClass($class);
            //$container->addResource(new FileResource($r->getFileName()));

            if (!method_exists($class, '__construct')) {
                $configuration = new $class();

                return $configuration;
            }
        }
    }

    /**
     * @param array $config
     *
     * @return bool    Whether the configuration is enabled
     *
     * @throws InvalidArgumentException When the config is not enableable
     */
    protected function isConfigEnabled(array $config)
    {
        if (!array_key_exists('enabled', $config)) {
            throw new InvalidArgumentException("The config array has no 'enabled' key.");
        }

        //return (bool) $container->getParameterBag()->resolveValue($config['enabled']);
    }
}
