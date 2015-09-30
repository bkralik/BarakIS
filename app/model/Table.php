<?php

namespace App\Model;

use Nette;
    

/**
 * Reprezentuje repozitář pro databázovou tabulku
 */
abstract class Table extends Nette\Object
{

    /** 
     * @var Nette\Database\Context 
     */
	protected $database;

    /**
     *
     * @var Nette\Security\User 
     */
    protected $userService;

    /**
     * @var string
     */
    protected $tableName;


    /**
     * @param Nette\Database\Connection $db
     * @throws \Nette\InvalidStateException
     */
    public function __construct(Nette\Database\Context $db, Nette\Security\User $user)
    {
        $this->database = $db;
        $this->userService = $user;


        if ($this->tableName === NULL) {
            $class = get_class($this);
            throw new Nette\InvalidStateException("Název tabulky musí být definován v $class::\$tableName.");
        }
    }



    /**
     * Vrací celou tabulku z databáze
     * @return \Nette\Database\Table\Selection
     */
    protected function getTable()
    {
        return $this->database->table($this->tableName);
    }



    /**
     * Vrací všechny záznamy z databáze
     * @return \Nette\Database\Table\Selection
     */
    public function findAll()
    {
        return $this->getTable();
    }



    /**
     * Vrací vyfiltrované záznamy na základě vstupního pole
     * (pole array('name' => 'David') se převede na část SQL dotazu WHERE name = 'David')
     *
     * @param array $by
     *
     * @return \Nette\Database\Table\Selection
     */
    public function findBy(array $by)
    {
        return $this->getTable()->where($by);
    }



    /**
     * To samé jako findBy akorát vrací vždy jen jeden záznam
     *
     * @param array $by
     *
     * @return \Nette\Database\Table\ActiveRow|FALSE
     */
    public function findOneBy(array $by)
    {
        return $this->findBy($by)->limit(1)->fetch();
    }



    /**
     * Vrací záznam s daným primárním klíčem
     *
     * @param int $id
     *
     * @return \Nette\Database\Table\ActiveRow|FALSE
     */
    public function find($id)
    {
        return $this->getTable()->get($id);
    }
    
    /**
     * Smaže záznam/y
     *
     * @param array $by
     *
     * @return \Nette\Database\Table\ActiveRow|FALSE
     */
    public function delete(array $by)
    {
        return $this->getTable()->where($by)->delete();
    }

    public function insert($data)
    {
        return($this->getTable()->insert($data));
    }

    public function update($id, $data)
    {
        $post = $this->find($id);
        $post->update($data);
        return($post);
    }
}