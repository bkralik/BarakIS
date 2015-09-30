<?php

namespace App\Presenters;

use Nette,
    App\Model,
    Nette\Application\UI\Form,
    Instante\Bootstrap3Renderer\BootstrapRenderer,
    Nette\Utils\Validators;


class OsobyPresenter extends BasePresenter
{        
    /** @var uzivatel */
    private $uzivatel;
    
    /** @var role */
    private $role;

    public function __construct(Model\Uzivatel $uzivatel, Model\Role $role) {
        $this->uzivatel = $uzivatel;
        $this->role = $role;
    }
	public function renderDefault()	{
        if(!$this->user->loggedIn) {
            $this->flashMessage('Tato sekce je pouze pro registrované. Pro pokračování se přihlašte, prosím.');
            $this->redirect('Sign:in');
        }
        $this->template->osoby = $this->uzivatel->findAll()->order("jmeno ASC");
        $this->template->transformer = $this->t;
	}

    public function t($roles) {
        return(Model\UserManager::transformRolesToNames($roles));
    }
    
    public function renderEdit($id) {
        if(empty($id)) {
            $id = $this->user->id;
        }
        
        $u = $this->uzivatel->find($id);
        
        if(!$u) {
            $this->error("Uživatel s daným ID neexistuje.");
        }
        
        $defaults = $u->toArray();
        $defaults["role"] = $u->related('role', 'uzivatel_id')->fetchPairs('id', 'role');
        $this['osobaEditForm']->setDefaults($defaults);
        $this->template->osoba = $u;
    }
    
    protected function createComponentOsobaEditForm() {
        $form = new Form;

        $form->addText('jmeno', 'Jméno a příjmení')
             ->addRule(Form::FILLED, 'Jméno a příjmení musí být vyplněno.');
        
        $form->addText('email', 'Email')
             ->addRule(Form::EMAIL, 'Email musí být validní.');
        
        $form->addText('cisloJednotky', 'Číslo jednotky');
        
        $form->addCheckbox('chceMaily', 'Dostávat emaily?');
        
        if($this->user->isInRole('spravce')) {
            $form->addMultiSelect('role', 'Role/oprávnění:', Model\UserManager::$roleDiakritika)
                 ->setAttribute('class', 'form-control');
        }
        
        $form->addHidden('id');
        
        $form->addSubmit('send', 'Uložit');
        
        if($this->presenter->action == 'edit') {
            $form->addButton('delete', 'Smazat')
                     ->setAttribute('data-toggle', 'modal')
                     ->setAttribute('data-target', '#modal-delete')   
                     ->setAttribute('class', 'btn btn-danger');
        }
        
        $form->onSuccess[] = array($this, 'osobaEditFormSucceeded');
        //$form->onValidate[] = array($this, 'validateLicenceForm');
        
        $form->setRenderer(new BootstrapRenderer);
        return $form;
    }

    public function osobaEditFormSucceeded(Form $form, $values) {
        if(!$this->user->isLoggedIn()) {
            $this->flashMessage('Tato funkce je pouze pro registrované. Pro pokračování se přihlašte, prosím.');
            $this->redirect('Sign:in');
        } 
                
        if(!$this->user->isInRole('spravce') && $this->user->id != $values->id) {
            $this->flashMessage('Omlouváme se, ale tato funkce je pouze pro správce.', 'warning');
            $this->redirect('Dokumenty:default');
        }
        
        if(isset($values->role)) {
            $noveRole = $values->role;
            unset($values->role);
        }
        
        if(empty($values->id)) {
            $id = $this->uzivatel->insert($values);
        } else {
            $id = $values->id;
            $this->uzivatel->find($id)->update($values);
        }
        
        if(isset($noveRole)) {
            $aktualniRole = array_values($this->uzivatel->find($id)->related('role', 'uzivatel_id')->fetchPairs('id', 'role'));

            sort($aktualniRole);
            sort($noveRole);

            $smazane = array_diff($aktualniRole, $noveRole);
            $pridane = array_diff($noveRole, $aktualniRole);

            $this->role->deleteRoles($smazane, $id);
            $this->role->createRoles($pridane, $id);
        }
        
        $this->flashMessage('Osoba byla úspěšně uložena.', 'success');
    }
    
    public function actionDelete($id) {
        $this->testAndRedirectSpravce();
        $u = $this->uzivatel->find($id);
        
        if(!$u) {
            $this->error("Uživatel s daným ID neexistuje.");
        }
        
        $this->role->findBy(array('uzivatel_id' => $u->id))->delete();
        
        $u->delete();
        
        $this->flashMessage('Uživatel byl úspěšně smazán.', 'success');
        $this->redirect('Osoby:default');
    }
    
    public function renderNastaveni($id) {
        $this->renderEdit($id);
    }
    
    protected function createComponentOsobaPasswordForm() {
        $form = new Form;

        $form->addPassword('aktualniHeslo', 'Aktuální heslo')
             ->addRule(Form::FILLED, 'Aktuální heslo musí být vyplněno.');
        
        $form->addPassword('noveHeslo', 'Nové heslo')
             ->addRule(Form::MIN_LENGTH, 'Nové heslo musí být minimálně pět znaků dlouhé.', 5);
        
        $form->addPassword('noveHeslo2', 'Nové heslo (znovu)');

        $form->addSubmit('send', 'Změnit heslo');
        
        
        $form->onSuccess[] = array($this, 'osobaPasswordFormSucceeded');
        $form->onValidate[] = array($this, 'osobaPasswordFormValidate');
        
        $form->setRenderer(new BootstrapRenderer);
        return $form;
    }
    
    public function osobaPasswordFormSucceeded(Form $form, $values) {
        if(!$this->user->loggedIn) {
            $this->error('Pro tuto akci musí být uživatel přihlášen.');
        }
        $this->uzivatel->update($this->user->id, array('heslo' => sha1($values->noveHeslo)));
        $this->flashMessage('Heslo bylo úspěšně změněno.', 'success');
    }
    
    public function osobaPasswordFormValidate(Form $form, $values) {
        if(!$this->user->loggedIn) {
            $this->error('Pro tuto akci musí být uživatel přihlášen.');
        }
        $u = $this->uzivatel->findOneBy(array('id' => $this->user->id, 'heslo' => sha1($values->aktualniHeslo)));
        
        if(!$u) {
            $form->addError('Chyba, původní heslo není správné.');
        }
        
        if($values->noveHeslo != $values->noveHeslo2) {
            $form->addError('Chyba, nová hesla nesouhlasí.');
        }
    }
    
    protected function createComponentOsobaCreateForm() {
        $form = new Form;

        $form->addTextArea('osoby', 'Osoby')
             ->addRule(Form::FILLED, 'Osoby musí být vyplněny.');
        
        $form->addSubmit('send', 'Vytvořit osoby');
        
        $form->onSuccess[] = array($this, 'osobaCreateFormSucceeded');
        $form->onValidate[] = array($this, 'osobaCreateFormValidate');
        
        $form->setRenderer(new BootstrapRenderer);
        return $form;
    }
    
    public function osobaCreateFormValidate(Form $form, $values) {
        if(!$this->user->loggedIn) {
            $this->error('Pro tuto akci musí být uživatel přihlášen.');
        }
        
        foreach($this->parseOsoby($values->osoby) as $id => $osoba) {
            if(strlen($osoba['jmeno']) == 0) {
                $form->addError('Chyba, u '.($id+1).' osoby není zadáno jméno.');
            }
            
            if(!Validators::isEmail($osoba['email'])) {
                $form->addError('Chyba, u '.($id+1).' osoby není zadán platný email.');
            }
        }
    }
    
    public function osobaCreateFormSucceeded(Form $form, $values) {
        if(!$this->user->loggedIn) {
            $this->error('Pro tuto akci musí být uživatel přihlášen.');
        }
        
        if(!$this->user->isInRole('spravce')) {
            $this->error('Omlouváme se, ale tato funkce je pouze pro správce.');
        }
        
        $mailer = new Model\DruzstvoMailer();
        
        $osoby = $this->parseOsoby($values->osoby);
        foreach($osoby as &$osoba) {
            $heslo = Model\UserManager::genPassword(10);
            $osoba['heslo'] = sha1($heslo);
            $osoba['chceMaily'] = 1;
            $mailer->sendRegistrace($osoba['jmeno'], $heslo, $osoba['email']);
            $id = $this->uzivatel->insert($osoba);
            $this->role->insert(array('role' => 1, 'uzivatel_id' => $id));
        }
        $this->flashMessage('Uživatelé byli úspěšně zaregistrováni.', 'success');
        $this->redirect('Osoby:default');
    }
    
    private function parseOsoby($in) {
        $osoby = explode("\n", $in);
        
        $out = array();
        foreach($osoby as $osoba) {
            list($jmeno, $email, $cj) = explode(';', $osoba);
            $jmeno = trim($jmeno);
            $email = trim($email);
            $cj = trim($cj);
            
            $out[] = array('jmeno' => $jmeno, 'email' => $email, 'cisloJednotky' => $cj);
        }
        return($out);
    }
    
    public function renderCreate() {
        
    }
}
