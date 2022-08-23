<?php


namespace core\domain;


abstract class Sacraments
{
    const BAPTISM = 0;
    const FIRST_COMMUNION = 1;
    const PROFESSION_OF_FAITH = 2;  //Actually this is NOT a sacrament, but it behaves like so in this application...
    const CHRISMATION = 3;

    /**
     * Converts a textual (external) representation of a sacrament into the corresponding internal class.
     * @param string $sacrament
     * @return int
     */
    public static function sacramentFromString(string $sacrament)
    {
        switch (strtolower($sacrament))
        {
            case "baptism":
            case "baptismo":
            case "batismo":
                return Sacraments::BAPTISM;

            case "first communion":
            case "primeira comunhão":
            case "primeiracomunhao":
            case "eucaristia":
                return Sacraments::FIRST_COMMUNION;

            case "profession of faith":
            case "profissão de fé":
            case "profissaofe":
                return Sacraments::PROFESSION_OF_FAITH;

            case "chrismation":
            case "crisma":
            case "confirmação":
            case "confirmacao":
                return Sacraments::CHRISMATION;

            default:
                return null;
        }
    }


    /**
     * Converts a sacrament constant into a textual representation, to be used internally in some functions.
     * @param int $sacrament - One of the constants from class Sacraments
     * @return string|null
     */
    public static function toInternalString(int $sacrament)
    {
        switch($sacrament)
        {
            case Sacraments::BAPTISM:
                return "baptismo";

            case Sacraments::FIRST_COMMUNION:
                return "primeiraComunhao";

            case Sacraments::PROFESSION_OF_FAITH:
                return "profissaoFe";

            case Sacraments::CHRISMATION:
                return "confirmacao";

            default:
                return null;
        }
    }


    /**
     * Converts a sacrament constant into a textual representation, to be used externally in the frontend.
     * @param int $sacrament - One of the constants from class Sacraments
     * @return string|null
     */
    public static function toExternalString(int $sacrament)
    {
        switch($sacrament)
        {
            case Sacraments::BAPTISM:
                return "Baptismo";

            case Sacraments::FIRST_COMMUNION:
                return "Eucaristia (Primeira Comunhão)";

            case Sacraments::PROFESSION_OF_FAITH:
                return "Profissão de Fé";

            case Sacraments::CHRISMATION:
                return "Confirmação (Crisma)";

            default:
                return null;
        }
    }
}