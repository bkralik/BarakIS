<?php

namespace App\Presenters;

use Nette,
    App\Model,
    Nette\Application\UI\Form;


class KontaktPresenter extends BasePresenter
{        
    /** @var Model\Uzivatel @inject */
    public $uzivatel;
    
    /** @var Model\Role @inject */
    public $role;
    
    /** @var Model\DruzstvoMailer @inject */
    public $mailer;
    
    /** @var Model\Log @inject */
    public $log;
    
	public function renderDefault()	{
        if($this->user->isLoggedIn()) {
            $identity = $this->user->getIdentity();
            $defaults["email"] = $identity->data['email'];
            $defaults["name"] = $identity->data['jmeno'];
            $this['emailForm']->setDefaults($defaults);
        }
	}

    protected function createComponentEmailForm()
    {
        $form = new Form;
        $form->addText('email', 'Váš email')
             ->addRule(Form::EMAIL, 'Prosím, zadejte platný email.');
        
        $form->addText('name', 'Vaše jméno')
             ->setRequired('Prosím, zadejte vaše jméno.');
        
        $form->addTextArea('message', 'Zpráva')
             ->addRule(Form::MIN_LENGTH, 'Zpráva je příliš krátká.', 5);
        
        $form->addSubmit('send', 'Odeslat');
        
        $form->onSuccess[] = array($this, 'emailFormSucceeded');
        $form->onValidate[] = array($this, 'validateEmailForm');
        return $form;
    }
    
    public function validateEmailForm(Form $form)
    {
        $values = $form->getHttpData();
        
        $recaptchaSecret = $this->context->parameters["ReCaptchaSecret"];
        $recaptcha = new \ReCaptcha\ReCaptcha($recaptchaSecret);
        
        $httpRequest = $this->context->getByType('Nette\Http\Request');
        
        $resp = $recaptcha->verify($values["g-recaptcha-response"], $httpRequest->getRemoteAddress());
        if ($resp->isSuccess()) {
            // verified!
        } else {
            $errors = $resp->getErrorCodes();
            if(in_array("missing-input-response", $errors)) {
                $form->addError("Prosím, zaškrtněte políčko \"Nejsem robot\"");
            } else {
                $form->addError("Při odesílání zprávy došlo chybě, zkuste to prosím znovu.");
            }
        }
    }

    // volá se po úspěšném odeslání formuláře
    public function emailFormSucceeded(Form $form, $values)
    {
        $spravci = $this->role->findBy(array(
            'role' => array(2, 3)
        ));
        
        $spravciEmaily = array();
        foreach($spravci as $spravce) {
            $spravciEmaily[] = $spravce->uzivatel->email;
        }
        
        $this->mailer->sendKontaktMail($values->email, $values->name, $values->message,  array_unique($spravciEmaily));

        $this->flashMessage('Zpráva byla úspěšeně odeslána.');
        $this->log->l('kontakt.send');
        $this->redirect('Kontakt:');
    }
}
