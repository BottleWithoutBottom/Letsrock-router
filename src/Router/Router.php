<?php

namespace Bitrock\Router;
use Bitrock\LetsEnv;
use Bitrock\Models\Singleton;

abstract class Router extends Singleton
{
    public CONST BOOTSTRAP_MODE = 'BOOTSTRAP_MODE';

    abstract public function handle();

    public static function preHook()
    {
        return !empty(LetsEnv::getInstance()->getEnv(static::BOOTSTRAP_MODE));
    }
}