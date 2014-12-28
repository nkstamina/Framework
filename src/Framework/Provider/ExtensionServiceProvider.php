<?php
namespace Nkstamina\Framework\Provider;

use Nkstamina\Framework\Common\Utils;
use Nkstamina\Framework\ServiceProviderInterface;
use Nkstamina\Framework\Provider\Exception\InvalidConfigurationException;
use Pimple\Container;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ExtensionServiceProvider
 * @package Nkstamina\Framework\Provider
 */
class ExtensionServiceProvider implements ServiceProviderInterface
{
    const EXTENSION_SUFFIX = '.php';
    public $extensions;

    /**
     * {@inheritdoc}
     */
    public function register(Container $app)
    {
        $app['extensions'] = function () use ($app) {
            return $this->getExtensions($app);
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
        return 'extension_service_provider';
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

            $this->extensions[$name] = $extension;

            var_dump($extension->getPath());
        }

        return $this->extensions;
    }


    /**
     * Return an array of extensions
     *
     * @param Container $app
     *
     * @return array
     */
    protected function getExtensions(Container $app)
    {




        $extensionsFound = $this->extract($app);
        $extensions = [];

//        foreach ($extensionsFound as $extension) {
//            $extensionPathName = $extension->getRelativePathname();
//
//            if (file_exists($file = $app['app.extensions.dir'].'/'.$extensionPathName)) {
//
//                if($content = file_get_contents($file)) {
//
//                $reflected = new \ReflectionClass($content);
//                var_dump($reflected);
//                }
//            }
//
//            echo $extensionPathName;






//            $extensionName                           = strstr($extensionPathName, '/', true);
//            $extensions[$extensionName]['name']      = $extensionName;
//            $extensions[$extensionName]['pathName']  = $app['app.extensions.dir'] . '/' . $extensionName;
//            $extensions[$extensionName]['phpName']   = $extensionName . self::EXTENSION_SUFFIX;
//            $extensions[$extensionName]['namespace'] = '\\\\' . $extensionName;
//
//            echo $extensionPathName;
//
//            $classPath = $extensions[$extensionName]['pathName'].'/'.$extensions[$extensionName]['phpName'];


//            if (0 === strpos($classPath, 'Extensions')) {
                //throw new \InvalidArgumentException(sprintf('', ));
//            }

            //echo $classFile . "<br>";
//            if (file_exists($classFile)) {
//                $content = file_get_contents($classFile);
//
//                var_dump($content);
//            }
            //$c = new $class;
//        }

        return $extensions;
    }

    /**
     * extract
     *
     * @param Container $app
     *
     * @return Finder
     */
    private function extract(Container $app)
    {
        $extensions = $app['config.finder']
            ->ignoreUnreadableDirs()
            ->files()
            ->name('*Extension.php')
            ->in($app['app.extensions.dir'])
            ->sortByName()
        ;

        return $extensions;
    }
}

