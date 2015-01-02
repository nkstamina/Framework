<?php
namespace Nkstamina\Framework;

interface ApplicationInterface
{
    /**
     * Returns an array of extensions to register.
     *
     * @return ExtensionInterface[] An array of extensions instances.
     */
    public function registerExtensions();
}
