<?php
namespace Nkstamina\Framework;

interface ControllerInterface
{
    /**
     * Renders a template
     *
     * @param       $name
     * @param array $value
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($name, array $value = []);
}
