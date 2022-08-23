<?php

namespace catechesis;

use Exception;

/**
 * A class providing random biblic phrases like "fortune cookies".
 * Loads "fortunes" from JSON files.
 */
class Fortune
{
    private $_fortunes = array();


    function __construct()
    {
        $this->loadFortunes(__DIR__ . '/fortunes/biblical_phrases_PT_PT.json');
    }

    /**
     * Loads fortunes from a JSON file.
     * The file must be a list of objects, each containing two attributes:
     *  - 'reference' - the biblic reference, or author of the quote
     *  - 'citation' - the biblic citation / phrase itself
     * @param string $file
     * @return void
     */
    private function loadFortunes(string $file)
    {
        $this->_fortunes = json_decode(file_get_contents($file), true);
    }


    /**
     * Returns a random fortune, i.e. an object containing three attributes:
     *   - 'reference' - the biblic reference, or author of the quote
     *   - 'citation' - the biblic citation / phrase itself
     *   - 'index' - the index of the phrase in this object (so that it can be asked again)
     * @return mixed
     */
    public function getRandom()
    {
        $index = array_rand($this->_fortunes);
        $res = $this->_fortunes[$index];
        $res['index'] = $index;
        return $res;
    }


    /**
     * Returns a fortune given its index.
     * The returned object has three attributes:
     *   - 'reference' - the biblic reference, or author of the quote
     *   - 'citation' - the biblic citation / phrase itself
     *   - 'index' - the index of the phrase in this object (so that it can be asked again)
     * @param int $index
     * @return mixed
     * @throws Exception
     */
    public function getFortune(int $index)
    {
        if($index < 0  || $index >= count($this->_fortunes))
            throw new Exception("Fortune::getFortune: Index out of bounds");

        $res = $this->_fortunes[$index];
        $res['index'] = $index;
        return $res;
    }
}