<?php
namespace Nkstamina\Framework\Extension;

interface ExtensionInterface
{
    /**
     * Boots the Extension
     * @return void
     */
    public function boot();

    /**
     * Shutdowns the Extension
     * @return void
     */
    public function shutdown();
}
