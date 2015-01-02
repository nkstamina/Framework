<?php

namespace Nkstamina\Framework\Provider;

use Nkstamina\Framework\Common\Utils;
use Nkstamina\Framework\ServiceProviderInterface;
use Nkstamina\Framework\Provider\Exception\InvalidTemplateDirectoryException;
use Nkstamina\Framework\Extension\Exception\InvalidExtensionException;
use Pimple\Container;

/**
 * Class TemplatingServiceProvider
 * @package Nkstamina\Framework\Provider
 */
class TemplatingServiceProvider implements ServiceProviderInterface
{
    const EXTENSION_TEMPLATE_PATH = 'Resources/views';

    /**
     * {@inheritdoc}
     */
    public function register(Container $app)
    {
        $app['twig.path']            = array($app['app.templates.path']);
        $app['twig.templates']       = array();

        $app['twig.loader'] = function () use ($app) {
            $loaders = [];

            $twigLoaderFs = new \Twig_Loader_Filesystem($app['twig.path']);

            $currentController = $app['request']->get('_controller');
            $found = false;
            $templateViewDirectory = '';

            foreach ($app['app.extensions'] as $name => $object) {
                if (false !== strpos($currentController, $name)) {
                    $templateViewDirectory = $object->getPath().'/'.self::EXTENSION_TEMPLATE_PATH;

                    if (!Utils::isDirectoryValid($templateViewDirectory)) {
                        throw new InvalidTemplateDirectoryException(sprintf(
                            '"%s" is not a directory',
                            $templateViewDirectory
                        ));
                    }

                    $found = true;
                    break;
                }
            }

            if (!$found) {
                throw new InvalidExtensionException(sprintf(
                    'No extension found to manage controller "%s". Please check its spell in your routing.yml file or create a valid extension for this controller',
                    $currentController
                ));
            }

            $twigLoaderFs->addPath($templateViewDirectory);
            $loaders[] = $twigLoaderFs;
            $loaders[] = new \Twig_Loader_Array($app['twig.templates']);

            return new \Twig_Loader_Chain($loaders);
        };

        $app['twig.environment'] = function () use ($app) {
            $isTemplateMustBeCached = $app['twig.cache_templates'];
            $templateCacheDirectory = $app['twig.cache.directory'];

            $options = [];
            if ($isTemplateMustBeCached &&
                $this->isTemplateCacheDirectoryValid($templateCacheDirectory)) {
                $options = ['cache' => $templateCacheDirectory];
            }

            return new \Twig_Environment($app['twig.loader'], $options);
        };

        $app['twig'] = function () use ($app) {
            return $app['twig.environment'];
        };
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Container $app)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'templating_service_provider';
    }

    /**
     * Check if template cache directory is valid
     *
     * @param string $directory
     *
     * @return bool
     * @throws \Exception
     */
    private function isTemplateCacheDirectoryValid($directory)
    {
        return Utils::isDirectoryValid($directory);
    }
}
