<?php

namespace core\domain;

use Exception;

abstract class WeekDay
{
    const SUNDAY = 0;
    const MONDAY = 1;
    const TUESDAY = 2;
    const WEDNESDAY = 3;
    const THURSDAY = 4;
    const FRIDAY = 5;
    const SATURDAY = 6;


    /**
     * Converts an internal week day representation into a Portuguese string
     * suitable for display in the UI.
     * @param int $weekDay
     * @return string
     * @throws Exception
     */
    public static function toPortugueseString(int $weekDay)
    {
        switch($weekDay)
        {
            case self::SUNDAY:
                return "Domingo";

            case self::MONDAY:
                return "Segunda-feira";

            case self::TUESDAY:
                return "Terça-feira";

            case self::WEDNESDAY:
                return "Quarta-feira";

            case self::THURSDAY:
                return "Quinta-feira";

            case self::FRIDAY:
                return "Sexta-feira";

            case self::SATURDAY:
                return "Sábado";

            default:
                throw new Exception("WeekDay::toPortugueseString: Invalid week day");
        }
    }


    /**
     * Converts an internal week day representation into an English string
     * suitable to be used, for example, in php date functions.
     * @param int $weekDay
     * @return string
     * @throws Exception
     */
    public static function toString(int $weekDay)
    {
        switch($weekDay)
        {
            case self::SUNDAY:
                return "sunday";

            case self::MONDAY:
                return "monday";

            case self::TUESDAY:
                return "tuesday";

            case self::WEDNESDAY:
                return "wednesday";

            case self::THURSDAY:
                return "thursday";

            case self::FRIDAY:
                return "friday";

            case self::SATURDAY:
                return "saturday";

            default:
                throw new Exception("WeekDay::toString: Invalid week day");
        }
    }
}