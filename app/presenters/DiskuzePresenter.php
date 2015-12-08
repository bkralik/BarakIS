<?php

namespace App\Presenters;

use Nette;
use App\Model;
use Nette\Application\UI\Form;
use Instante\Bootstrap3Renderer\BootstrapRenderer;
use Nette\Utils\DateTime;


class DiskuzePresenter extends BasePresenter
{    
    /** @var Model\Diskuze @inject */
    public $diskuze;
    
    /** @var Model\Log @inject **/
    public $log;

    
	public function renderDefault()	{
        $this->template->vlakna = $this->diskuze->findBy(array('parent IS NULL'))->order('cas DESC');
	}
    
    public function renderVlakno($id) {
        $basePrispevek = $this->diskuze->find($id);
        
        if(!$basePrispevek) {
            $this->error('Chyba, toto vlákno neexistuje.');
        }
        
        $this->template->basePrispevek = $basePrispevek;
        
        $this->template->prispevky = $this->diskuze->findAll()->where('parent = ? OR id = ?', $id, $id)->order('cas ASC');
    }
    
    protected function createComponentNewVlaknoForm() {
        $form = new Form;
        $form->addText('titulek', 'Titulek vlákna')
             ->setRequired('Zadejte prosím titulek vlákna.');
        
        $form->addTextArea('text', 'Text příspěvku', NULL, 10)
             ->setRequired('Před vytvořením nového vlákna zadejte prosím text prvního příspěvku.');

        $form->addSubmit('send', 'Vytvořit vlákno');
        
        $form->onSuccess[] = array($this, 'newVlaknoFormSucceeded');
        //$form->onValidate[] = array($this, 'nastenkaFormValidate');
        
        $form->setRenderer(new BootstrapRenderer);
        
        return $form;
    } 
    
    public function newVlaknoFormSucceeded(Form $form, $values) {        
        $values->cas = time();
        $values->parent = NULL;
        $values->uzivatel_id = $this->user->id;
        
        $id = $this->diskuze->insert($values);
        $this->log->l('diskuze.createvlakno', $id);

        $this->flashMessage('Nové diskuzní vlákno bylo přidáno.', 'success');
        $this->redirect('Diskuze:vlakno', $id);
    }
    
    public function renderEdit($id) {
        $prispevek = $this->diskuze->find($id);
        if(!$prispevek) {
            $this->error('Chyba, tento příspěvek neexistuje.');
        }
        
        $parentId = ($prispevek->parent?$prispevek->parent:$prispevek->id);
        
        $basePrispevek = $this->diskuze->find($parentId);
        if(!$basePrispevek) {
            $this->error('Chyba, toto vlákno neexistuje.');
        }
        
        $this->template->basePrispevek = $basePrispevek;
        $d = $prispevek->toArray();
        $this['prispevekForm']->setDefaults($d);
    }
    
    public function renderCreate($id) {
        $basePrispevek = $this->diskuze->find($id);
        
        if(!$basePrispevek) {
            $this->error('Chyba, toto vlákno neexistuje.');
        }
        
        $this->template->basePrispevek = $basePrispevek;
        $d['parent'] = $id;
        $this['prispevekForm']->setDefaults($d);
    }
    
    protected function createComponentPrispevekForm() {
        $form = new Form;        
        $form->addTextArea('text', 'Text příspěvku', NULL, 10)
             ->setRequired('Prosím zadejte text příspěvku.');

        $form->addSubmit('send', 'Uložit příspěvek');
        $form->addHidden('parent');
        $form->addHidden('id');
        
        $form->onSuccess[] = array($this, 'prispevekFormSucceeded');
        //$form->onValidate[] = array($this, 'nastenkaFormValidate');
        
        $form->setRenderer(new BootstrapRenderer);
        
        return $form;
    } 
    
    public function prispevekFormSucceeded(Form $form, $values) { 
        $parentId = ($values->parent?$values->parent:$values->id);
        if(empty($values->id)) {
            $values->cas = time();
            $values->uzivatel_id = $this->user->id;
            $id = $this->diskuze->insert($values);
            $this->log->l('diskuze.createpost', $id);
            $this->flashMessage('Diskuzní příspěvek byl úspěšně přidán.', 'success');
        } else {
            $id = $values->id;
            $values->editcas = time();
            unset($values->parent);
            $this->diskuze->update($id, $values);
            $this->log->l('nastenka.editpost', $id);
            $this->flashMessage('Diskuzní příspěvek byl úspěšně upraven.', 'success');
        }

        
        $this->redirect('Diskuze:vlakno', $parentId);
    }
}
