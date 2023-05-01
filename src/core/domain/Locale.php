<?php

namespace core\domain;

use Exception;

abstract class Locale
{
    const PORTUGAL = "PT";
    const BRASIL = "BR";


    public static function catechesisStartMonth(string $locale)
    {
        switch($locale)
        {
            case self::PORTUGAL:
            default:
                return "September";

            case self::BRASIL:
                return "March";
        }
    }

    public static function catechesisEndMonth(string $locale)
    {
        switch($locale)
        {
            case self::PORTUGAL:
            default:
                return "June";

            case self::BRASIL:
                return "November";
        }
    }
}