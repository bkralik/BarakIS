<?php

namespace App\Model;

use Nette;

/**
 * Description of Log
 *
 * @author bkralik
 */
class Log extends Table {
    /** @var string */
    protected $tableName = 'log';
       
    /** @var Nette\Http\Request **/
    private $request;
    
    public function __construct(Nette\Http\IRequest $request, Nette\Database\Context $db, Nette\Security\User $user) {
        parent::__construct($db, $user);
        $this->request = $request;
    }
    
    public function l($akce, $detail = NULL) {
        $userID = ($this->userService->isLoggedIn()?$this->userService->id:NULL);
        $ip = $this->request->getRemoteAddress();
        
        $this->insert(array(
            'cas' => time(),
            'ip' => $ip,
            'akce' => $akce,
            'detail' => $detail,
            'uzivatel_id' => $userID
        ));
    }
}
