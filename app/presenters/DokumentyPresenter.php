<?php

namespace App\Presenters;

use Nette,
    App\Model,
    Nette\Application\UI\Form,
    Instante\Bootstrap3Renderer\BootstrapRenderer;


class DokumentyPresenter extends BasePresenter
{        
    /** @var Model\Dokument */
    private $dokument;
    
    /** @var Model\Kategorie */
    private $kategorie;
    
    /** @var Model\Uzivatel */
    private $uzivatel;
    
    /** @var Model\DruzstvoMailer */
    private $druzstvoMailer;
    
    public function __construct(Model\Dokument $dokument, Model\Kategorie $kategorie, Model\Uzivatel $uzivatel, Model\DruzstvoMailer $dm) {
        $this->dokument = $dokument;
        $this->kategorie = $kategorie;
        $this->uzivatel = $uzivatel;
        $this->druzstvoMailer = $dm;
    }
    
	public function renderDefault()	{
        if($this->user->isLoggedIn()) {
            $dokumenty = $this->dokument->findAll();
        } else {
            $dokumenty = $this->dokument->findBy(array("verejne" => 1));
        }

        $this->template->dokumenty = $dokumenty->order("casNahrani DESC");
	}
    
    public function renderUpload() {
        $this->testAndRedirectSpravce();
    }
    
    protected function createComponentDokumentUploadForm() {
        $form = new Form;
        
        $kategorie = $this->kategorie->getSelect();
                
        $form->addUpload('soubor', 'Soubor')
             ->addRule(Form::FILLED, 'Soubor musí být vybrán.');
        
        $form->addText('popis', 'Popis')
             ->addRule(Form::FILLED, 'Popis souboru musí být vyplněn.');
        
        $form->addSelect('kategorie_id', 'Kategorie', $kategorie);
        
        $form->addCheckbox('verejne', 'Veřejný soubor');
        
        $form->addSubmit('send', 'Nahrát soubor');
        
        
        $form->onSuccess[] = array($this, 'dokumentUploadFormSucceeded');
        //$form->onValidate[] = array($this, 'validateLicenceForm');
        
        $form->setRenderer(new BootstrapRenderer);
        return $form;
    }

    public function dokumentUploadFormSucceeded(Form $form, $values) {  
        $this->testAndRedirectSpravce();
        if(!$values->soubor->isOk()) {
            $this->flashMessage("Chyba při nahrávání. Zkuste soubor nahrát znovu.", 'error');
            $this->redirect('Dokumenty:upload');
        }
        
        $dbDokument['soubor'] = uniqid('', TRUE);
        $values->soubor->move(Model\Dokument::DOKUMENTY_FOLDER.$dbDokument['soubor']);
        
        $dbDokument['jmeno'] = $values->soubor->getName();
        $dbDokument['popis'] = $values->popis;
        $dbDokument['kategorie_id'] = $values->kategorie_id;
        $dbDokument['verejne'] = $values->verejne;
        $dbDokument['casNahrani'] = time();
        
        $id = $this->dokument->insert($dbDokument);
        $pocet = $this->sendDokumentMails($id);
        
        $this->flashMessage('Dokument byl úspěšně nahrán, emaily byly rozeslány '.$pocet.' lidem.', 'success');
        $this->redirect('Dokumenty:default');        
    }
    
    public function sendDokumentMails($docID) {
        $d = $this->dokument->find($docID);
        
        $lidi = $this->uzivatel->findBy(array('chceMaily' => 1));
        
        $link = $this->link('//Dokumenty:download', $d->id);
        
        //$mailer = new Model\DruzstvoMailer();
        return($this->druzstvoMailer->sendDokument($d, $lidi, $link));
    }
    
    public function actionDownload($id) {        
        $d = $this->dokument->find($id);
        
        if(!$d) {
            $this->error("Dokument s daným ID neexistuje.");
        }
        
        if(!$d->verejne && !$this->user->isLoggedIn()) {
            $this->flashMessage('Tato funkce je pouze pro registrované. Pro pokračování se přihlašte, prosím.');
            $this->redirect('Sign:in');
        }
        
        $this->sendResponse(new \Nette\Application\Responses\FileResponse(Model\Dokument::DOKUMENTY_FOLDER.$d->soubor, $d->jmeno));
    }

    protected function createComponentDokumentEditForm() {
        $form = new Form;
        
        $kategorie = $this->kategorie->getSelect();
                
        $form->addText('jmeno', 'Jméno souboru')
             ->addRule(Form::FILLED, 'Jméno souboru musí být vyplněno.');
        
        $form->addText('popis', 'Popis')
             ->addRule(Form::FILLED, 'Popis souboru musí být vyplněn.');
        
        $form->addSelect('kategorie_id', 'Kategorie', $kategorie);
        
        $form->addCheckbox('verejne', 'Veřejný soubor');
        
        $form->addSubmit('send', 'Uložit');
        
        $form->addButton('delete', 'Smazat')
                 ->setAttribute('data-toggle', 'modal')
                 ->setAttribute('data-target', '#modal-delete')   
                 ->setAttribute('class', 'btn btn-danger');
        
        $form->addHidden('id');
        
        $form->onSuccess[] = array($this, 'dokumentEditFormSucceeded');
        //$form->onValidate[] = array($this, 'validateLicenceForm');
        
        $form->setRenderer(new BootstrapRenderer);
        return $form;
    }

    public function dokumentEditFormSucceeded(Form $form, $values) {     
        $this->testAndRedirectSpravce();
        $d = $this->dokument->find($values->id);
        
        if(!$d) {
            $this->error("Dokument s daným ID neexistuje.");
        }
        
        unset($values->delete);
        $d->update($values);
        
        $this->flashMessage('Dokument byl úspěšně upraven.', 'success');
        $this->redirect('Dokumenty:default');        
    }
    
    public function renderEdit($id) {
        $this->testAndRedirectSpravce();
        $d = $this->dokument->find($id);
        
        if(!$d) {
            $this->error("Dokument s daným ID neexistuje.");
        }

        $this['dokumentEditForm']->setDefaults($d->toArray());
        $this->template->dokument = $d;
    }
    
    public function actionDelete($id) {
        $this->testAndRedirectSpravce();
        $d = $this->dokument->find($id);
        
        if(!$d) {
            $this->error("Dokument s daným ID neexistuje.");
        }
        
        $d->delete();
        
        $this->flashMessage('Dokument byl úspěšně smazán.', 'success');
        $this->redirect('Dokumenty:default');
    }
    
    public function testAndRedirectSpravce() {
        if(!$this->user->isLoggedIn()) {
            $this->flashMessage('Tato funkce je pouze pro registrované. Pro pokračování se přihlašte, prosím.');
            $this->redirect('Sign:in');
        } 
                
        if(!$this->user->isInRole('spravce')) {
            $this->flashMessage('Omlouváme se, ale tato funkce je pouze pro správce.', 'warning');
            $this->redirect('Dokumenty:default');
        }
    }
}
