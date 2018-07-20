<?php

namespace Bgultekin\CashierFastspring\Fastspring;

/**
 * This class helps to reach Fastspring class with laravel config and static methods.
 *
 * @author   Bilal Gultekin <bilal@gultekin.me>
 *
 * @version  0.1
 *
 * @see      https://docs.fastspring.com/integrating-with-fastspring/fastspring-api
 */
class Fastspring
{
    /**
     * Instance of Fastspring class.
     *
     * @var array
     */
    public static $instance;

    /**
     * It is not useful to construct this Fastspring class everytime.
     * This helps to construct this class with the current config.
     */
    public static function __callStatic($method, $parameters)
    {
        // if there is not any constructed instance
        // construct and save it to self::$instance
        if (!self::$instance) {
            $username = (getenv('FASTSPRING_USERNAME') ?: config('services.fastspring.username'));
            $password = (getenv('FASTSPRING_PASSWORD') ?: config('services.fastspring.password'));

            self::$instance = new ApiClient($username, $password);
        }

        return call_user_func_array([self::$instance, $method], $parameters);
    }
}
