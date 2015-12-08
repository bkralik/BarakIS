<?php

namespace App\Model;

use Nette;
//use Nette\Security\Passwords;


/**
 * Users management.
 */
class UserManager extends Nette\Object implements Nette\Security\IAuthenticator
{
	const
		TABLE_NAME = 'uzivatel',
		COLUMN_ID = 'id',
		COLUMN_NAME = 'email',
		COLUMN_PASSWORD_HASH = 'heslo';//,
		//COLUMN_ROLE = 'role';
    
    const
        ROLE_CLEN = 1,
        ROLE_VYBOR = 2,
        ROLE_SPRAVCE = 3;
    
    public static $role = array(
        self::ROLE_CLEN => "clen",
        self::ROLE_SPRAVCE => "spravce",
        self::ROLE_VYBOR => "vybor"
    );
    
    public static $roleDiakritika = array(
        self::ROLE_CLEN => "člen SVJ",
        self::ROLE_SPRAVCE => "správce stránek",
        self::ROLE_VYBOR => "člen výboru SVJ"
    );

	/** @var Nette\Database\Context */
	private $database;


	public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}

    public static function transformRolesToIDs($roles, $fromNames = false) {
        $role_enum = self::$role;
        $out = array();
        foreach($roles as $role) {
            if($fromNames) {
                $role_id = array_search($role, $role_enum);
            } else {
                $role_id = $role->role;
            }
            $out[] = $role_id;
        }
        return($out);
    }
    
    public static function transformRolesToNames($roles, $fromNames = false) {
        $role_enum_diak = self::$roleDiakritika;
        $ids = self::transformRolesToIDs($roles, $fromNames);
        $out = array();
        foreach($ids as $id) {
            $out[] = $role_enum_diak[$id];
        }
        return(implode(', ', $out));
    }
    
	/**
	 * Performs an authentication.
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		list($username, $password) = $credentials;

		$row = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_NAME, $username)->where('smazan = 0')->fetch();

		if (!$row) {
			throw new Nette\Security\AuthenticationException('Neplatné přihlašovací jméno nebo heslo.', self::IDENTITY_NOT_FOUND);

		} elseif (sha1($password) !== $row[self::COLUMN_PASSWORD_HASH]) {
			throw new Nette\Security\AuthenticationException('Neplatné přihlašovací jméno nebo heslo.', self::INVALID_CREDENTIAL);
		}
        
		$arr = $row->toArray();
		unset($arr[self::COLUMN_PASSWORD_HASH]);
        
        $roles = array();
        foreach($row->related('role', 'uzivatel_id') as $role) {
            $roles[] = self::$role[$role->role];
        }
        
		return new Nette\Security\Identity($row[self::COLUMN_ID], $roles, $arr);
	}


	/**
	 * Adds new user.
	 * @param  string
	 * @param  string
	 * @return void
	 */
	public function add($username, $password)
	{
		try {
			$this->database->table(self::TABLE_NAME)->insert(array(
				self::COLUMN_NAME => $username,
				self::COLUMN_PASSWORD_HASH => sha1($password),
			));
		} catch (Nette\Database\UniqueConstraintViolationException $e) {
			throw new DuplicateNameException;
		}
	}
    
    public static function genPassword($length = 10) {
        $possibleChars = "abcdefghijklmnopqrstuvwxyz";
        $password = '';

        for($i = 0; $i < $length; $i++) {
            $rand = rand(0, strlen($possibleChars) - 1);
            $password .= substr($possibleChars, $rand, 1);
        }
        
        return($password);
    }

}


class DuplicateNameException extends \Exception
{}
