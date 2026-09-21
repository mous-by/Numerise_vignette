<?php

namespace App\Enums;

enum ActivityChannel: string
{
    case Web = 'web';
    case Api = 'api';
    case Console = 'console';

    /**
     * Canal de la requête courante : console (hors tests), API (préfixe api/), sinon Web.
     */
    public static function current(): self
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return self::Console;
        }

        return request()->is('api/*') ? self::Api : self::Web;
    }
}
