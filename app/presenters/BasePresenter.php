<?php

namespace App\Presenters;

use Nette;
use App\Model;


/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{
    protected function startup() {
        parent::startup();
        
        $loginPresenters = array('Nastenka', 'Osoby');
        $presenterName = $this->getPresenter()->name;
        
        if(in_array($presenterName, $loginPresenters) && ($this->user->isLoggedIn() != true)) {
            $this->flashMessage("Pro zobrazení stránky se přihlašte, prosím.");
            $this->redirect("Sign:in");
        }
    }
    
    protected function beforeRender() {
        parent::beforeRender();
        $this->template->isLogged = $this->user->isLoggedIn();
        if($this->template->isLogged) {
            $this->template->uzivatel = $this->user->getIdentity()->jmeno;
        }
    }
}
