<?php

namespace App\Model;

/**
 * Description of Role
 *
 * @author bkralik
 */
class Role extends Table {
    /**
    * @var string
    */
    protected $tableName = 'role';
    
    /**
     * Funkce pro mazání rolí od uživatele
     * 
     * @param int[] $roles Pole rolí k smazání
     * @param int $uid ID uživatele
     * @return int Počet smazaných rolí
     */
    public function deleteRoles($roles, $uid) {
        if(count($roles) == 0) {
            return(0);
        }
        
        return($this->findBy(
            array(
                'role' => $roles,
                'uzivatel_id' => $uid
                )
            )->delete());
    }
    
    /**
     * Funkce pro vytváření rolí uživatele
     * 
     * @param int[] $roles Pole rolí k vytvoření
     * @param int $uid ID uživatele
     * @return int Počet přidaných rolí
     */
    public function createRoles($roles, $uid) {
        if(count($roles) == 0) {
            return(0);
        }
        
        $i = 0;
        foreach($roles as $p) {
            $this->insert(array(
                'role' => $p,
                'uzivatel_id' => $uid
            ));
            $i++;
        }
        return($i);
    }
}
