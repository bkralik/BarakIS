<?php

namespace App\Presenters;

use Nette;
use App\Model;
use Nette\Application\UI\Form;
use Instante\Bootstrap3Renderer\BootstrapRenderer;
use Nette\Utils\DateTime;


class NastenkaPresenter extends BasePresenter
{    
    /** @var Model\Nastenka @inject */
    public $nastenka;
    
    /** @var Model\Log @inject **/
    public $log;
    
    private static $dateFormat = 'd. m. Y';
    
	public function renderDefault()	{
        $this->template->nastenka = $this->nastenka->findAll()->order('platneOd DESC');
	}
    
    protected function createComponentNastenkaForm()
    {
        $form = new Form;
        $form->addText('nadpis', 'Nadpis');
        
        $form->addTextArea('text', 'Text')
             ->setRequired('Zadejte text příspěvku na nástěnku.');
        
        $form->addTbDatePicker('platneOd', 'Zobrazit od:', NULL, 12)
             ->setAttribute('class', 'datepicker')
             ->setOption('input-prepend', 'calendar')
             ->setRequired('Zadejte datum od kdy příspěvek zobrazit.')
             ->setFormat(self::$dateFormat);
        
        $form->addTbDatePicker('platneDo', 'Zobrazit do:', NULL, 12)
             ->setAttribute('class', 'datepicker')
             ->setOption('input-prepend', 'calendar')
             ->setFormat(self::$dateFormat);
        
        $form->addCheckbox('platnostTrvale', 'Zobrazit trvale?');
        
        $form->addCheckbox('verejne', 'Veřejné?');
        
        $form->addSubmit('send', 'Uložit');
        $form->addHidden('id');
        
        $form->onSuccess[] = array($this, 'nastenkaFormSucceeded');
        $form->onValidate[] = array($this, 'nastenkaFormValidate');
        
        $form->setRenderer(new BootstrapRenderer);
        
        return $form;
    }    
    
    public function nastenkaFormValidate(Form $form, $values)  {  
        if($values->platneDo === FALSE && $values->platnostTrvale == FALSE) {
            $form->addError('"Zobrazit do" musí být platné datum.');
        }
        
        if($values->platneOd === FALSE) {
            $form->addError('"Zobrazit od" musí být platné datum.');
        }
        
        if(!$values->platnostTrvale && $values->platneDo !== "") {
            if($values->platneDo < $values->platneOd) {
                $form->addError('"Zobrazit od" musí být menší datum než "Zobrazit do".');
            }
        }
    }
    
    public function nastenkaFormSucceeded(Form $form, $values)  {        
        if($values->platnostTrvale || $values->platneDo === "") {
            $values->platneDo = -1;
        } else {
            $values->platneDo = $values->platneDo->format('U');
        }
        unset($values->platnostTrvale);
        
        $values->platneOd = $values->platneOd->format('U');
        
        $values->autor = $this->user->getIdentity()->jmeno;
        
        if(empty($values->id)) {
            $id = $this->nastenka->insert($values);
            $this->log->l('nastenka.create', $id);
        } else {
            $id = $values->id;
            $this->nastenka->update($id, $values);
            $this->log->l('nastenka.edit', $id);
        }

        $this->flashMessage('Příspěvek na nástěnku byl úspěšeně uložen.', 'success');
        $this->redirect('Nastenka:');
    }
    
	public function renderEdit($id) {
        $n = $this->nastenka->find($id);
        if (!$n) {
            $this->error('Příspěvek nástěnky s daným ID nenalezen.');
        }
        $d = $n->toArray();
        $d['platneOd'] = date(self::$dateFormat, $d['platneOd']);
        if($d['platneDo'] > 0) {
            $d['platneDo'] = date(self::$dateFormat, $d['platneDo']);
        } else {
            $d['platneDo'] = "";
            $d['platnostTrvale'] = true;
        }
        $this['nastenkaForm']->setDefaults($d);
	}
    
    
    public function renderCreate() {
        $dateNow = new DateTime();
        $d = array(
            'platneOd' => $dateNow->format(self::$dateFormat)
        );
        $this['nastenkaForm']->setDefaults($d);
	}
    /*
    public function actionDelete($id) {
        $k = $this->kontakt->find($id);
        if(!$k) {
            $this->error('Kontakt s daným ID nenalezen.');
        }
        $zid = $k->zakaznik_id;
        
        $k->delete();
        
        $this->flashMessage('Kontakt byl smazán.', 'success');
        $this->redirect('Zakaznik:show', $zid);
    }*/
}
