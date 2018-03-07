<?php

  namespace ADV\Controllers\Contacts\Manage;

  use ADV\App\Creditor\Creditor;
  use ADV\App\Contact\Postcode;
  use GL_UI;
  use GL_Currency;
  use Tax_Groups;
  use Contact_Log;
  use ADV\Core\View;
  use ADV\Core\Cache;
  use ADV\Core\MenuUI;
  use ADV\Core\JS;
  use ADV\Core\HTMLmin;
  use ADV\App\Validation;
  use ADV\Core\Input\Input;
  use ADV\App\Form\Form;
  use ADV\App\UI;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Suppliers extends \ADV\App\Controller\Action
  {
    /** @var Creditor */
    protected $creditor;
    protected function before() {
      if ($this->action == SAVE) {
        $this->creditor = new Creditor();
        $this->creditor->save($_POST['company']);
      } elseif ($this->action == FETCH && $this->Input->request('id', Input::NUMERIC) > 0) {
        $this->creditor      = new Creditor($this->Input->request('id', Input::NUMERIC));
        $data['contact_log'] = Contact_Log::read($this->creditor->id, CT_CUSTOMER);
        $this->Session->setGlobal('creditor_id', $this->creditor->id);
      } else {
        $this->creditor = new Creditor();
      }
      if (REQUEST_POST && REQUEST_AJAX) {
        if ($this->creditor) {
          $data['company'] = $this->creditor;
        }
        $data['status'] = $this->creditor->getStatus();
        $this->JS->renderJSON($data);
      }
      $this->JS->footerFile("/js/company.js");
    }
    protected function index() {
      if (isset($_POST['delete'])) {
        $this->delete();
      }
      echo $this->generateForm();
      $this->JS->onload("Company.fetchUrl='/Contacts/Manage/Suppliers';Company.setValues(" . json_encode(['company' => $this->creditor]) . ");")->setFocus($this->creditor->id ? 'name' : 'supplier');
    }
    /**
     * @return string
     */
    protected function generateForm() {
      $cache = Cache::_get('supplier_form');
      $cache = null; //Cache::_get('supplier_form');
      if ($cache) {
        $this->JS->addState($cache[1]);
        return $form = $cache[0];
      }
      $js = new JS();
      $js->autocomplete('supplier', 'Company.fetch', 'Creditor');
      $form = new Form();
      $menu = new MenuUI('disabled');
      $menu->setJSObject($js);
      $view          = new View('contacts/supplier');
      $view['frame'] = $this->Input->get('frame') || $this->Input->get('id');
      $view->set('menu', $menu);
      $form->text('name', ['class' => 'width60'])->label('Supplier Name:');
      $form->text('id', ['class' => 'small', 'maxlength' => 7])->label('Supplier ID:');
      $view->set('form', $form);
      $view->set('creditor_id', $this->creditor->id);
      $form->text('contact')->label('Contact:');
      $form->text('phone')->label('Phone Number:');
      $form->text('fax')->label('Fax Number:');
      $form->text('email')->label('Email:');
      $form->textarea('address', ['cols' => 37, 'rows' => 4])->label('Street:');
      $postcode = new Postcode(array(
                                    'city'     => array('city'),
                                    'state'    => array('state'),
                                    'postcode' => array('postcode')
                               ), $js);
      $view->set('postcode', $postcode->getForm());
      $form->text('supp_phone')->label('Phone Number:');
      $form->textarea('supp_address', ['cols' => 37, 'rows' => 4])->label('Address:');
      $supp_postcode = new Postcode(array(
                                         'city'     => array('supp_city'),
                                         'state'    => array('supp_state'),
                                         'postcode' => array('supp_postcode')
                                    ), $js);
      $view->set('supp_postcode', $supp_postcode->getForm());
      $form->percent('payment_discount', ["disabled" => !$this->User->hasAccess(SA_SUPPLIERCREDIT)])->label("Prompt Payment Discount:");
      $form->amount('credit_limit', ["disabled" => !$this->User->hasAccess(SA_SUPPLIERCREDIT)])->label("Credit Limit:");
      $form->text('tax_id')->label("GST No:");
      $form->text('account_no')->label("Acccount #:");
      $form->custom(Tax_Groups::select('tax_group_id'))->label('Tax Group:');
      $form->textarea('notes')->label('General Notes:');
      $form->arraySelect('inactive', ['No', 'Yes'])->label('Inactive:');
      if (!$this->creditor->id) {
        $form->custom(GL_Currency::select('curr_code'))->label('Currency Code:');
      } else {
        $form->custom($this->creditor->curr_code)->label('Currency Code:');
        $form->hidden('curr_code');
      }
      $form->custom(GL_UI::payment_terms('payment_terms'))->label('Payment Terms:');
      $form->custom(GL_UI::all('payable_account', null, false, false, true))->label('Payable Account:');
      $form->custom(GL_UI::all('payment_discount_account', null, false, false, true))->label('Prompt Payment Account:');
      $view->set('form', $form);
      $form->hidden('type', CT_SUPPLIER);
      $contact_form = new Form();
      $view['date'] = date('Y-m-d H:i:s');
      $contact_form->text('contact_name')->label('Contact:');
      $contact_form->textarea('message', ['cols' => 100, 'rows' => 10])->label('Entry:');
      $view->set('contact_form', $contact_form);
      if (!$this->Input->get('frame')) {
        $shortcuts = [
          [
            'caption' => 'Supplier Payment',
            'Make supplier payment!',
            'data'    => '/purchases/payment.php?creditor_id='
          ],
          ['caption' => 'Create Order', 'Create Order for this supplier!', 'data' => '/purchases/invoice.php?New=1&creditor_id='],
        ];
        $view->set('shortcuts', $shortcuts);
        UI::emailDialogue(CT_SUPPLIER, $js);
      }
      $form     = HTMLmin::minify($view->render(true));
      $js_state = $js->getState();
      Cache::_set('supplier_form', [$form, $js_state]);
      $this->JS->addState($js_state);
      return $form;
    }
    protected function runValidation() {
      Validation::check(Validation::SALES_AREA, _("There are no sales areas defined in the system. At least one sales area is required before proceeding."));
      Validation::check(Validation::SHIPPERS, _("There are no shipping companies defined in the system. At least one shipping company is required before proceeding."));
      Validation::check(Validation::TAX_GROUP, _("There are no tax groups defined in the system. At least one tax group is required before proceeding."));
    }
    private function delete() {
    }
  }

