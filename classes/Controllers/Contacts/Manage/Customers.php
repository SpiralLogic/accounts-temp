<?php
  namespace ADV\Controllers\Contacts\Manage;

  use ADV\App\Debtor\Debtor;
  use ADV\Core\HTML;
  use ADV\App\Contact\Postcode;
  use Tax_Groups;
  use Inv_Location;
  use Sales_UI;
  use Sales_CreditStatus;
  use GL_UI;
  use GL_Currency;
  use Sales_Type;
  use Contact_Log;
  use ADV\Core\Event;
  use ADV\Core\Cache;
  use ADV\Core\View;
  use ADV\Core\MenuUI;
  use ADV\Core\JS;
  use ADV\Core\HTMLmin;
  use ADV\App\Validation;
  use ADV\App\Form\Form;
  use ADV\Core\Input\Input;
  use ADV\App\UI;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Customers extends \ADV\App\Controller\Action
  {
    /** @var Debtor */
    protected $debtor;
    protected $security = SA_CUSTOMER;
    protected function before() {
      if ($this->action == SAVE) {
        $this->debtor = new Debtor();
        $this->debtor->save($_POST['company']);
      } elseif ($this->action == FETCH && $this->Input->request('id', Input::NUMERIC) > 0) {
        $this->debtor        = new Debtor($this->Input->request('id', Input::NUMERIC));
        $data['contact_log'] = Contact_Log::read($this->debtor->id, CT_CUSTOMER);
        $this->Session->setGlobal('debtor_id', $this->debtor->id);
      } elseif ($this->action == 'newBranch') {
        $this->debtor   = new Debtor($this->Input->request('id', Input::NUMERIC));
        $data['branch'] = $this->debtor->addBranch();
      } elseif ($this->action == 'deleteBranch') {
        $this->debtor = new Debtor($this->Input->request('id', Input::NUMERIC));
        $this->debtor->deleteBranch($this->Input->post('branch_id', Input::NUMERIC));
      } else {
        $this->debtor = new Debtor();
      }
      if (REQUEST_POST && REQUEST_AJAX) {
        if ($this->debtor) {
          $data['company'] = $this->debtor;
          $data['status']  = $this->debtor->getStatus();
        }
        $this->JS->renderJSON($data);
      }
      $this->JS->footerFile("/js/company.js");
      $this->setTitle("Customers");
    }
    protected function index() {
      if (isset($_POST['delete'])) {
        $this->delete();
      }
      echo $this->generateForm();
      $this->JS->onload("Company.fetchUrl='/Contacts/Manage/Customers';Company.setValues(" . json_encode(['company' => $this->debtor]) . ");")->setFocus(
        $this->debtor->id ? 'name' : 'customer'
      );
    }
    /**
     * @return string
     */
    protected function generateForm() {
      $cache = Cache::_get('customer_form');
      $cache = null;
      if ($cache) {
        $this->JS->addState($cache[1]);
        return $form = $cache[0];
      }
      $js = new JS();
      $js->autocomplete('customer', 'Company.fetch', 'Debtor');
      $form = new Form();
      $menu = new MenuUI('disabled', 'companyedit');
      $menu->setJSObject($js);
      $view          = new View('contacts/customers');
      $view['frame'] = $this->Input->get('frame') || $this->Input->get('id');
      $view->set('menu', $menu);
      $view->set(
        'branchlist', UI::select(
          'branchList', array_map(
            function ($v) {
              return $v->br_name;
            }, $this->debtor->branches
          ), ['class' => 'med', 'name' => 'branchList'], null, new HTML
        )
      );
      $form->group('shipping_details')->text('branch[contact_name]')->label('Contact:');
      $form->text('branch[phone]')->label('Phone Number:');
      $form->text('branch[phone2]')->label("Alt Phone Number:");
      $form->text('branch[fax]')->label("Fax Number:");
      $form->text('branch[email]')->label("Email:");
      $form->textarea('branch[br_address]', ['cols' => 37, 'rows' => 4])->label('Street:');
      $branch_postcode = new Postcode([
                                      'city'     => ['branch[city]'], //
                                      'state'    => ['branch[state]'], //
                                      'postcode' => ['branch[postcode]']
                                      ], $js);
      $view->set('branch_postcode', $branch_postcode->getForm());
      $form->group('accounts_details')->text('accounts[contact_name]')->label('Accounts Contact:');
      $form->text('accounts[phone]')->label('Phone Number:');
      $form->text('accounts[phone2]')->label('Alt Phone Number:');
      $form->text('accounts[fax]')->label('Fax Number:');
      $form->text('accounts[email]')->label('Email:');
      $form->textarea('accounts[br_address]', ['cols' => 37, 'rows' => 4])->label('Street:');
      $accounts_postcode = new Postcode([
                                        'city'     => ['accounts[city]'], //
                                        'state'    => ['accounts[state]'], //
                                        'postcode' => ['accounts[postcode]'] //
                                        ], $js);
      $view->set('accounts_postcode', $accounts_postcode->getForm());
      $form->hidden('accounts_id');
      $form->group('accounts');
      $has_access = !$this->User->hasAccess(SA_CUSTOMER_CREDIT);
      $form->percent('discount', ["disabled" => $has_access])->label("Discount Percent:");
      $form->percent('payment_discount', ["disabled" => $has_access])->label("Prompt Payment Discount:");
      $form->amount('credit_limit', ["disabled" => $has_access])->label("Credit Limit:");
      $form->text('tax_id')->label("GSTNo:");
      $form->custom(Sales_Type::select('sales_type'))->label('Sales Type:');
      $form->arraySelect('inactive', ['No', 'Yes'])->label('Inactive:');
      if (!$this->debtor->id) {
        $form->custom(GL_Currency::select('curr_code'))->label('Currency Code:');
      } else {
        //$form->label('Currency Code:', 'curr_code', $this->debtor->curr_code);
        $form->hidden('curr_code');
      }
      $form->custom(GL_UI::payment_terms('payment_terms'))->label('Payment Terms:');
      $form->custom(Sales_CreditStatus::select('credit_status'))->label('Credit Status:');
      $form->group();
      $form->textarea('messageLog', ['style' => 'height:100px;width:95%;margin:0 auto;', 'cols' => 100])->setContent(Contact_Log::read($this->debtor->id, CT_CUSTOMER));
      /** @noinspection PhpUndefinedMethodInspection */
      $contacts = new View('contacts/contact');
      $view->set('contacts', $contacts->render(true));
      $form->hidden('branch_id');
      $form->custom(Sales_UI::persons('branch[salesman]'))->label('Salesman:');
      $form->custom(Sales_UI::areas('branch[area]'))->label('Sales Area:');
      $form->custom(Sales_UI::groups('branch[group_no]'))->label('Sales Group:');
      $form->custom(Inv_Location::select('branch[default_location]'))->label('Dispatch Location:');
      $form->custom(Sales_UI::shippers('branch[default_ship_via]'))->label('Default Shipper:');
      $form->custom(Tax_Groups::select('branch[tax_group_id]'))->label('Tax Group:');
      $form->arraySelect('branch[disable_trans]', ['Yes', 'No'])->label('Disabled: ');
      $form->text('webid', ['disabled' => true])->label("Websale ID");
      $form->custom(GL_UI::all('branch[sales_account]', null, true, false, true))->label('Sales Account:');
      $form->custom(GL_UI::all('branch[receivables_account]', null, true, false, false))->label('Receivables Account:');
      $form->custom(GL_UI::all('branch[sales_discount_account]', null, false, false, true))->label('Discount Account:');
      $form->custom(GL_UI::all('branch[payment_discount_account]', null, false, false, true))->label('Prompt Payment Account:');
      $form->textarea('branch[notes]', ['cols' => 100, 'rows' => 10])->label('General Notes:');
      $view['debtor_id'] = $this->debtor->id;
      $form->hidden('frame', $this->Input->request('frame'));
      $view->set('form', $form);
      $form->hidden('type', CT_CUSTOMER);
      $contact_form = new Form();
      $view['date'] = date('Y-m-d H:i:s');
      $contact_form->text('contact_name')->label('Contact:');
      $contact_form->textarea('message', ['cols' => 100, 'rows' => 10])->label('Entry:');
      $view->set('contact_form', $contact_form);
      if (!$this->Input->get('frame')) {
        $shortcuts = [
          [
            'caption' => 'Create Quote',
            'Create Quote for this customer!',
            'data'    => '/sales/order?type=' . ST_SALESQUOTE . '&add=' . ST_SALESQUOTE . '&debtor_id=',
            'attrs'   => ['class' => 'btn']
          ],
          [
            'caption' => 'Create Order',
            'Create Order for this customer!',
            'data'    => '/sales/order?type=30&add=' . ST_SALESORDER . '&debtor_id=',
            'attrs'   => ['class' => 'btn']
          ],
          [
            'caption' => 'Print Statement',
            'Print Statement for this Customer!',
            'data'    => '/reporting/prn_redirect.php?REP_ID=108&PARAM_2=0&PARAM_4=0&PARAM_5=0&PARAM_6=0&PARAM_0=',
            'attrs'   => ['class' => 'btn']
          ],
          [
            'caption' => 'Email Statement',
            'Email Statement for this Customer!',
            'attrs'   => ['class' => 'btn email-button']
          ],
          ['caption' => 'Customer Payment', 'Make customer payment!', 'data' => '/sales/payment?debtor_id=', 'attrs' => ['class' => 'btn']]
        ];
        $view->set('shortcuts', $shortcuts);
        UI::emailDialogue(CT_CUSTOMER, $js);
      }
      $form     = HTMLmin::minify($view->render(true));
      $js_state = $js->getState();
      Cache::_set('customer_form', [$form, $js_state]);
      $this->JS->addState($js_state);
      return $form;
    }
    protected function delete() {
      $this->debtor->delete();
      $status = $this->debtor->getStatus();
      Event::notice($status['message']);
    }
    protected function after() {
    }
    /**
     * @internal param $prefix
     * @return bool|mixed
     */
    protected function runValidation() {
      Validation::check(Validation::SALES_TYPES, _("There are no sales types defined. Please define at least one sales type before adding a customer."));
      Validation::check(Validation::SALESPERSONS, _("There are no sales people defined in the system. At least one sales person is required before proceeding."));
      Validation::check(Validation::SALES_AREA, _("There are no sales areas defined in the system. At least one sales area is required before proceeding."));
      Validation::check(Validation::SHIPPERS, _("There are no shipping companies defined in the system. At least one shipping company is required before proceeding."));
      Validation::check(Validation::TAX_GROUP, _("There are no tax groups defined in the system. At least one tax group is required before proceeding."));
    }
  }

