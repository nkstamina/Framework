<?php
namespace Nkstamina\Framework\Extension;

use Nkstamina\Framework\Application;

interface ExtensionInterface
{
    /**
     * Boots the Extension
     */
    public function boot();

    /**
     * Shutdowns the Extension
     */
    public function shutdown();
}
