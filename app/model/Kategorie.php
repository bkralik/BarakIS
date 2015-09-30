<?php

namespace App\Model;

/**
 * Description of Kategorie
 *
 * @author bkralik
 */
class Kategorie extends Table {
    /**
    * @var string
    */
    protected $tableName = 'kategorie';
    
    public function getSelect() {
        return($this->findAll()->fetchPairs('id', 'jmeno'));
    }
}
