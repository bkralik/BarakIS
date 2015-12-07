<?php

namespace App\Presenters;

use Nette;
use App\Model;


class HomepagePresenter extends BasePresenter
{    
    /** @var Model\Nastenka @inject */
    public $nastenka;
    
	public function renderDefault()	{
        $nastenkaSelect = $this->nastenka->findAll()->where("platneOd < ?", time())->where("platneDo > ? OR platneDo = -1", time());
        if(!$this->user->loggedIn) {
            $nastenkaSelect = $nastenkaSelect->where("verejne = 1");
        }
        $this->template->nastenka = $nastenkaSelect;
	}
}
