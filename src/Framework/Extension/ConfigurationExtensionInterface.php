<?php
namespace Nkstamina\Framework\Extension;

use Nkstamina\Framework\Application;
use Symfony\Component\Config\Definition\ConfigurationInterface;

interface ConfigurationExtensionInterface
{
    /**
     * Returns extension configuration
     *
     * @param array $config An array of configuration values
     * @param \Nkstamina\Framework\Application $app
     *
     * @return ConfigurationInterface|null The configuration or null
     */
    public function getConfiguration(array $config, Application $app);
}
