<?php

/**
 * @author sinacek
 */

class Accountancy_CashbookPresenter extends Accountancy_BasePresenter {

    /**
     * @var Ucetnictvi_UserService
     */
    protected $Uservice;

    function startup() {
        parent::startup();
        $this->service = new Ucetnictvi_AkceService();
        $this->Uservice = new Ucetnictvi_UserService();
        $this->paragony = $this->service->getParagony();
    }

    function beforeRender() {
        parent::beforeRender();
        $this->template->isInMinus = $this->paragony->isInMinus(); // musi byt v before render aby se vyhodnotila az po handleru
    }
    
//    function renderOffline(){
//        $this->template->form = $this['formOffline'];
//    }
//    
//    function createComponentFormOffline($name) {
//        $form = new AppForm($this, $name);
//        $form->getElementPrototype()->class('ajax');
//        $form->addText("text", "Text:");
//        $form->addText("date", "Datum:");
//        $form->addSubmit("ok", "Poslat")->getControlPrototype()->onclick("send();");
//        $form->onSuccess[] = array($this, $name . 'Submitted');
//    }
//    
//    function formOfflineSubmitted(AppForm $form){
//        $values = $form->values;
//        dump($values);
//        die();
//    }

    

    //compare 2 paragons by date and type
    // <editor-fold defaultstate="collapsed" desc="cmp">
    function cmp(Paragon $a, Paragon $b) {
        $adate = strtotime($a->date);
        $bdate = strtotime($b->date);
        if ($adate == $bdate) {
            if (in_array($a->type, $this->categoriesIn))
                return -1;
            if (in_array($b->type, $this->categoriesIn))
                return 1;
            return 0;
        }
        return ($adate < $bdate) ? -1 : 1;
    }
    // </editor-fold>

    /**
     * vyhodnotí řetězec
     */
    // <editor-fold defaultstate="collapsed" desc="solveString">
    function solveString($str) {
        preg_match_all('/(?P<cislo>[0-9]+)(?P<operace>[\+\*]+)?/', $str, $matches);
        $maxIndex = count($matches['cislo']);
        foreach ($matches['operace'] as $index => $op) {
            if ($op == "*" && $index + 1 <= $maxIndex) {
                $matches['cislo'][$index + 1] = $matches['cislo'][$index] * $matches['cislo'][$index + 1];
                $matches['cislo'][$index] = 0;
            }
        }
        $res = 0;
        foreach ($matches['cislo'] as $num) {
            $res += $num;
        }
        return $res;
    }
// </editor-fold>

    function renderDefault() {
        $this->template->list = $this->paragony->getAll();
        $this->template->formIn = $this['formInAdd'];
        $this->template->formOut = $this['formOutAdd'];
        
        $this->template->autoCompleter = $this->Uservice->getUsersToAC();
    }

    function renderEdit($id) {
        $this->setView("default");
        $formIn = $this['formInEdit'];
        $formOut = $this['formOutEdit'];

        $defaults = (array) $this->paragony->get($id);

        if (isset($defaults['date']))
            $defaults['date'] = date("j.n.Y", $defaults['date']->getTimestamp());
        $defaults['id'] = $id;
        $defaults['price'] = $defaults['priceText'];
        
        $formIn->setDefaults($defaults);
        $formOut->setDefaults($defaults);
        $this->template->list = $this->paragony->getAll();
        $this->template->formIn = $formIn;
        $this->template->formOut = $formOut;

        $this->template->autoCompleter = $this->Uservice->getUsersToAC();
        $this->template->hideButtons = true;
    }

    //FORM OUT

    function createComponentFormOutAdd($name) {
        $form = self::makeFormOUT($this, $name);
        $form->addSubmit('send', 'Uložit');
        $form->onSuccess[] = array($this, 'formAddSubmitted');
        $form->setDefaults(array('type' => 'un'));
        return $form;
    }

    function formAddSubmitted(AppForm $form) {
        $values = $form->getValues();
        $values['priceText'] = $values['price'];
        $values['price'] = $this->solveString($values['price']);
        $add = $this->paragony->add(new Paragon($values));
        $this->flashMessage($add ? "Paragon byl úspěšně přidán do seznamu." : "Paragon se nepodařilo přidat do seznamu.", "fail");
        if ($this->paragony->isInMinus())
            $this->flashMessage("Dostali jste se do záporné hodnoty.", "fail");
        if ($this->isAjax()) {
            $this->invalidateControl("tabs");
            $this->invalidateControl("paragony");
            $this->invalidateControl("flashmesages");
        } else {
            $this->redirect("this");
        }
    }

    function createComponentFormOutEdit($name) {
        $form = self::makeFormOUT($this, $name);
        $form->addHidden('id');
        $form->addSubmit('send', 'Uložit');
        $form->onSuccess[] = array($this, 'formEditSubmitted');
        return $form;
    }

    /**
     *
     * @param Presenter $thisP
     * @param <type> $name
     * @return AppForm
     */
    protected static function makeFormOUT($thisP, $name) {
        $form = new AppForm($thisP, $name);
        $form->addDatePicker("date", "Ze dne:", 15)
                ->addRule(Form::FILLED, 'Zadejte datum');
        //@TODO kontrola platneho data, problem s componentou
        $form->addText("komu", "Vyplaceno komu:", 20, 30)
                ->addRule(Form::FILLED, 'Zadejte komu to bylo vyplaceno');
        $form->addText("ucel", "Účel výplaty:", 20, 50)
                ->addRule(Form::FILLED, 'Zadejte účel výplaty')
                ->getControlPrototype()->placeholder("3 první položky");
        $form->addText("price", "Cena celkem: ", 20, 100)
                ->addRule(Form::REGEXP, 'Zadejte platnou částku bez mezer', "/^([0-9]+[\+\*])*[0-9]+$/")
                ->getControlPrototype()->placeholder("vzorce např.20+15*3");
        $types = $thisP->categoriesOut;

        if (!$thisP->service->getAction()->dotace)
            unset($types['do']);
        $form->addRadioList("type", "Typ: ", $types)
                ->addRule(Form::FILLED, 'Zadej typ paragonu');
        return $form;
    }

    
    //FORM IN
    
    function createComponentFormInAdd($name) {
        $form = $this->makeFormIn($this, $name);
        $form->addSubmit('send', 'Uložit');
        $form->onSuccess[] = array($this, 'formAddSubmitted');
        $form->setDefaults(array('type' => 'pp'));
        return $form;
    }

    function createComponentFormInEdit($name) {
        $form = self::makeFormIn($this, $name);
        $form->addHidden('id');
        $form->addSubmit('send', 'Uložit');
        $form->onSuccess[] = array($this, 'formEditSubmitted');
        return $form;
    }

    function formEditSubmitted(AppForm $form) {
        $values = $form->getValues();
        $id = $values['id'];
        unset($values['id']);
        $values['priceText'] = $values['price'];
        $values['price'] = $this->solveString($values['price']);

        $add = $this->paragony->set($id, new Paragon($values));
        $this->flashMessage($add ? "Paragon byl úspěšně upraven." : "Paragon se nepodařilo upravit.", $add ? "" :"fail");
        if ($this->paragony->isInMinus())
            $this->flashMessage("Dostali jste se do záporné hodnoty.", "fail");
        $this->redirect("default");
    }

    protected static function makeFormIn($thisP, $name) {
        $form = new AppForm($thisP, $name);
        $form->addDatePicker("date", "Ze dne:", 15)
                ->addRule(Form::FILLED, 'Zadejte datum');
        $form->addText("komu", "Prijato od:", 20, 30)
                ->addRule(Form::FILLED, 'Zadejte komu to bylo vyplaceno');
        $form->addText("ucel", "Účel příjmu:", 20, 50)
                ->addRule(Form::FILLED, 'Zadejte účel přijmu');
        $form->addText("price", "Částka: ", 20, 100)
                ->addRule(Form::REGEXP, 'Zadejte platnou částku', "/^([0-9]+[\+\*])*[0-9]+$/")
                ->getControlPrototype()->placeholder("vzorce 20+15*3");
        $types = $thisP->categoriesIn;
        if (!$thisP->service->getAction()->dotace)
            unset($types['di']);

        $form->addRadioList("type", "Typ: ", $types)
                ->addRule(Form::FILLED, 'Zadej typ paragonu');
        return $form;
    }

    function handleClearList() {
        $this->paragony->clearList();

        if ($this->isAjax()) {
            $this->terminate();
        } else {
            $this->redirect('this');
        }
    }
 
    function handleRemove($id) {
        if($this->paragony->remove($id)){
            $this->flashMessage("Paragon byl smazán");
        } else {
            $this->flashMessage("Paragon se nepodařilo smazat");
        }

        if ($this->isAjax()) {
            $this->invalidateControl("paragony");
            $this->invalidateControl("flashmesages");
        } else {
            $this->redirect('this');
        }
    }

    function handleSort(){
        $this->paragony->sortParagons();
        $this->redirect("this");
    }


    

}

