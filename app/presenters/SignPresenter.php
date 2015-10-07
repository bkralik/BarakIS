<?php

namespace App\Presenters;

use Nette;
use App\Forms\SignFormFactory;
use App\Model\Log;


class SignPresenter extends BasePresenter
{
	/** @var SignFormFactory @inject */
	public $factory;

    /** @var Log @inject */
    public $log;
    
	/**
	 * Sign-in form factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSignInForm()
	{
		$form = $this->factory->create();
		$form->onSuccess[] = function ($form) {
            $this->log->l('user.login');
			$form->getPresenter()->redirect('Homepage:');
		};
		return $form;
	}


	public function actionOut()
	{
		$this->getUser()->logout();
		$this->flashMessage('Byl jste odhlášen.');
        $this->log->l('user.logout');
		$this->redirect('in');
	}

}
