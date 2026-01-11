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

    /**
     * Returns a date formatted in Portuguese (e.g. "Domingo, 11 de Janeiro de 2026").
     * This method is locale-independent.
     * @param int|string $timestamp_or_date
     * @return string
     */
    public static function getPortugueseDate($timestamp_or_date)
    {
        $timestamp = is_numeric($timestamp_or_date) ? $timestamp_or_date : strtotime($timestamp_or_date);

        $days = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
        $months = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        $d = getdate($timestamp);
        return $days[$d['wday']] . ', ' . $d['mday'] . ' de ' . $months[$d['mon']] . ' de ' . $d['year'];
    }
}