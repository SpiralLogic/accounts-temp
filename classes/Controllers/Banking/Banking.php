<?php
  namespace ADV\Controllers\Banking;

  use ADV\App\Bank\Bank;
  use Item_Order;
  use GL_Bank;
  use ADV\Core\Event;
  use GL_UI;
  use DB_Company;
  use GL_ExchangeRate;
  use Bank_Currency;
  use ADV\Core\Num;
  use GL_QuickEntry;
  use Debtor_Branch;
  use Bank_Account;
  use Item_Line;
  use Sales_Branch;
  use ADV\App\User;
  use ADV\App\Debtor\Debtor;
  use ADV\App\Dates;
  use ADV\App\Ref;
  use ADV\App\Validation;
  use ADV\Core\JS;
  use ADV\App\Creditor\Creditor;
  use ADV\Core\Input\Input;
  use ADV\Core\Cell;
  use ADV\App\Forms;
  use ADV\App\Display;
  use ADV\App\Form\Button;
  use ADV\Core\Table;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Banking extends \ADV\App\Controller\Action
  {
    /** @var \Item_Order */
    protected $order;
    protected $security;
    protected $trans_type;
    protected $trans_no;
    protected $type;

    protected function before() {
      if ($this->Input->get('NewPayment')) {
        $this->newOrder(ST_BANKPAYMENT);
      } elseif ($this->Input->get('NewDeposit')) {
        $this->newOrder(ST_BANKDEPOSIT);
      } elseif ($this->Input->hasSession('pay_items')) {
        $this->order = $this->Input->session('pay_items');
      }
      $this->security = $this->order->trans_type == ST_BANKPAYMENT ? SA_PAYMENT : SA_DEPOSIT;
      $this->JS->openWindow(950, 500);
      $this->type = $this->order->trans_type == ST_BANKPAYMENT ? 'Payment' : 'Deposit';
      $this->setTitle('Bank Account ' . $this->type . ' Entry');
      if (REQUEST_GET) {
        if ($this->Input->hasGet('account', 'amount', 'memo', 'date')) {
          $_POST['bank_account'] = $this->Input->get('account');
          $_POST['total_amount'] = $_POST['amount'] = abs($this->Input->get('amount'));
          $_POST['memo_']        = $this->Input->get('memo');
          $_POST['date_']        = $this->Input->get('date');
        }
      }
      if (Forms::isListUpdated('PersonDetailID')) {
        $br                 = Sales_Branch::get($this->Input->post('PersonDetailID'));
        $_POST['person_id'] = $br['debtor_id'];
        $this->Ajax->activate('person_id');
      }
      if (isset($_POST['_date__changed'])) {
        $this->Ajax->activate('_ex_rate');
      }
      $id = Forms::findPostPrefix(MODE_DELETE);
      if ($id != -1) {
        $this->deleteItem($id);
      }
      if (isset($_POST['addLine'])) {
        $this->addLine();
      }
      if (isset($_POST['updateItem'])) {
        $this->updateItem();
      }
      if (isset($_POST['cancelItem'])) {
        Item_Line::start_focus('_code_id_edit');
      }
      if (isset($_POST['go'])) {
        $this->quickEntries();
      }
    }

    /**
     * @param $type
     */
    protected function newOrder($type) {
      if (isset($this->order)) {
        unset($_SESSION['pay_items']);
      }
      $this->order    = $_SESSION['pay_items'] = new Item_Order($type);
      $_POST['date_'] = Dates::_newDocDate();
      if (!Dates::_isDateInFiscalYear($_POST['date_'])) {
        $_POST['date_'] = Dates::_endFiscalYear();
      }
      $this->order->tran_date = $_POST['date_'];
    }

    /**
     * @param $id
     */
    protected function deleteItem($id) {
      $this->order->remove_gl_item($id);
      Item_Line::start_focus('_code_id_edit');
    }

    /**
     * @return bool
     */
    protected function addLine() {
      if (!$this->checkItemData()) {
        return;
      }
      $amount = ($this->order->trans_type == ST_BANKPAYMENT ? 1 : -1) * Validation::input_num('amount');
      $this->order->add_gl_item($_POST['code_id'], $_POST['dimension_id'], $_POST['dimension2_id'], $amount, $_POST['LineMemo']);
      Item_Line::start_focus('_code_id_edit');
    }

    /**
     * @return bool
     */
    protected function checkItemData() {
      if ($this->Input->post('PayType') == PT_QUICKENTRY && $this->order->count_gl_items() < 1) {
        Event::error('You must select and add quick entry before adding extra lines!');
        $this->JS->setFocus('total_amount');
        return false;
      }
      //if (!Validation::post_num('amount', 0))
      //{
      //	Event::error( _("The amount entered is not a valid number or is less than zero."));
      //	$this->JS->setFocus('amount');
      //	return false;
      //}
      if ($_POST['code_id'] == $_POST['bank_account']) {
        Event::error(_("The source and destination accouts cannot be the same."));
        $this->JS->setFocus('code_id');
        return false;
      }
      if (Bank_Account::is($_POST['code_id'])) {
        Event::error(_("You cannot make a " . $this->type . " from a bank account. Please use the transfer funds facility for this."));
        $this->JS->setFocus('code_id');
        return false;
      }
      return true;
    }

    protected function updateItem() {
      $amount = ($this->order->trans_type == ST_BANKPAYMENT ? 1 : -1) * Validation::input_num('amount');
      if ($_POST['updateItem'] != "" && $this->checkItemData()) {
        $this->order->update_gl_item($_POST['Index'], $_POST['code_id'], $_POST['dimension_id'], $_POST['dimension2_id'], $amount, $_POST['LineMemo']);
      }
      Item_Line::start_focus('_code_id_edit');
    }

    protected function quickEntries() {
      $result                = GL_QuickEntry::addEntry(
                                            $this->order, $_POST['person_id'], Validation::input_num('total_amount'), $this->order->trans_type == ST_BANKPAYMENT ? QE_PAYMENT :
                                                QE_DEPOSIT
      );
      $_POST['total_amount'] = Num::_priceFormat(0);
      $this->Ajax->activate('total_amount');
      ($result === false) ? $this->JS->setFocus('person_id') : Item_Line::start_focus('_code_id_edit');
    }

    protected function index() {
      $this->runAction();
      Forms::start();
      $this->header($this->order);
      Table::start('tablesstyle2 width90 pad10');
      echo '<tr>';
      echo "<td>";
      $this->items();
      echo "<br><table class='center'>";
      Forms::textareaRow(_("Memo"), 'memo_', null, 50, 3);
      echo "</table>";
      echo "</td>";
      echo '</tr>';
      Table::end(1);
      echo '<div class="center">';
      echo (new Button(FORM_ACTION, COMMIT, COMMIT))->type(Button::SUCCESS)->preIcon(ICON_SUBMIT);
      echo "</div>";
      Forms::end();
    }

    /**
     * @return void
     */
    protected function header() {
      $payment = $this->order->trans_type == ST_BANKPAYMENT;
      $this->Ajax->start_div('pmt_header');
      Table::startOuter('standard width90'); // outer table
      Table::section(1);
      Bank_Account::row($payment ? _("From:") : _("To:"), 'bank_account', null, true);
      Forms::dateRow(_("Date:"), 'date_', '', true, 0, 0, 0, null, true);
      Table::section(2, "33%");
      if (!isset($_POST['PayType'])) {
        if (isset($_GET['PayType'])) {
          $_POST['PayType'] = $_GET['PayType'];
        } else {
          $_POST['PayType'] = "";
        }
      }
      if (!isset($_POST['person_id'])) {
        if (isset($_GET['PayPerson'])) {
          $_POST['person_id'] = $_GET['PayPerson'];
        } else {
          $_POST['person_id'] = "";
        }
      }
      if (isset($_POST['_PayType_update'])) {
        $_POST['person_id'] = '';
        $this->Ajax->activate('pmt_header');
        $this->Ajax->activate('code_id');
        $this->Ajax->activate('pagehelp');
        $this->Ajax->activate('editors');
      }
      Bank::payment_person_type_row($payment ? _("Pay To:") : _("From:"), 'PayType', $_POST['PayType'], true);
      switch ($_POST['PayType']) {
        case PT_MISC :
          Forms::textRowEx($payment ? _("To the Order of:") : _("Name:"), 'person_id', 40, 50);
          break;
        //case PT_WORKORDER :
        //	workorders_list_row(_("Work Order:"), 'person_id', null);
        //	break;
        case PT_SUPPLIER :
          Creditor::row(_("Supplier:"), 'person_id', null, false, true, false, true);
          break;
        case PT_CUSTOMER :
          Debtor::row(_("Customer:"), 'person_id', null, false, true, false, true);
          if ($this->Input->post('person_id') && Validation::check(Validation::BRANCHES, _("No Branches for Customer"), $_POST['person_id'])
          ) {
            Debtor_Branch::row(_("Branch:"), $_POST['person_id'], 'PersonDetailID', null, false, true, true, true);
          } else {
            $_POST['PersonDetailID'] = ANY_NUMERIC;
            Forms::hidden('PersonDetailID');
          }
          break;
        case PT_QUICKENTRY :
          GL_QuickEntry::row(_("Type") . ":", 'person_id', null, ($payment ? QE_PAYMENT : QE_DEPOSIT), true);
          $qid = GL_QuickEntry::get($this->Input->post('person_id'));
          if (Forms::isListUpdated('person_id')) {
            unset($_POST['total_amount']); // enable default
            $this->Ajax->activate('total_amount');
          }
          Forms::AmountRow(
               $qid['base_desc'] . ":", 'total_amount', Num::_priceFormat($qid['base_amount']), null, "&nbsp;&nbsp;" . Forms::submit('go', _("Go"), false, false, true)
          );
          break;
        //case payment_person_types::Project() :
        //	Dimensions::select_row(_("Dimension:"), 'person_id', $_POST['person_id'], false, null, true);
        //	break;
      }
      $person_currency = Bank_Currency::for_payment_person($_POST['PayType'], $_POST['person_id']);
      $bank_currency   = Bank_Currency::for_company($_POST['bank_account']);
      GL_ExchangeRate::display($bank_currency, $person_currency, $_POST['date_']);
      Table::section(3, "33%");
      Forms::refRow(_("Reference:"), 'ref', '', Ref::get_next($this->order->trans_type));
      Table::endOuter(1); // outer table
      $this->Ajax->end_div();
    }

    /**
     * @static
     * @return void
     */
    protected function items() {
      $title = _($this->type . " Items");
      Display::heading($title);
      $this->Ajax->start_div('items_table');
      Table::start('tables_style grid width95');
      $th = [
        _("Account Code"),
        _("Account Description"),
        _("Amount"),
        _("Memo"),
        ""
      ];
      if (count($this->order->gl_items)) {
        $th[] = '';
      }
      Table::header($th);
      $id = Forms::findPostPrefix(MODE_EDIT);
      foreach ($this->order->gl_items as $line => $item) {
        if ($id != $line) {
          Cell::label($item->code_id);
          Cell::label($item->description);
          //Cell::amount(abs($item->amount));
          if ($this->order->trans_type == ST_BANKDEPOSIT) {
            Cell::amount(-$item->amount);
          } else {
            Cell::amount($item->amount);
          }
          Cell::label($item->reference);
          Forms::buttonEditCell("Edit$line", _("Edit"), _('Edit document line'));
          Forms::buttonDeleteCell("Delete$line", _("Delete"), _('Remove line from document'));
          echo '</tr>';
        } else {
          $this->itemControls($line);
        }
      }
      if ($id == -1) {
        $this->itemControls();
      }
      if ($this->order->count_gl_items()) {
        Table::label(_("Total"), Num::_format(abs($this->order->gl_items_total()), User::_price_dec()), " colspan='2' class='alignright'", "class='alignright'", 3);
      }
      Table::end();
      $this->Ajax->end_div();
    }

    /**
     * @static
     *
     * @param null $Index
     *
     * @return void
     */
    protected function itemControls($Index = null) {
      $payment = $this->order->trans_type == ST_BANKPAYMENT;
      echo '<tr>';
      $id = Forms::findPostPrefix(MODE_EDIT);
      if ($Index != -1 && $Index == $id) {
        $item                   = $this->order->gl_items[$Index];
        $_POST['code_id']       = $item->code_id;
        $_POST['dimension_id']  = $item->dimension_id;
        $_POST['dimension2_id'] = $item->dimension2_id;
        $_POST['amount']        = Num::_priceFormat(abs($item->amount));
        $_POST['description']   = $item->description;
        $_POST['LineMemo']      = $item->reference;
        Forms::hidden('Index', $id);
        echo GL_UI::all('code_id', null, true, true);
        $this->Ajax->activate('items_table');
      } else {
        if (REQUEST_GET && !count($this->order->gl_items) && $this->Input->get('amount', Input::NUMERIC)) {
          $_POST['amount'] = $_GET['amount'];
        } else {
          $_POST['amount'] = Num::_priceFormat(0);
        }
        $_POST['dimension_id']  = 0;
        $_POST['dimension2_id'] = 0;
        if (isset($_POST['_code_id_update'])) {
          $this->Ajax->activate('code_id');
        }
        if ($_POST['PayType'] == PT_CUSTOMER) {
          $acc              = Sales_Branch::get_accounts($_POST['PersonDetailID']);
          $_POST['code_id'] = $acc['receivables_account'];
        } elseif ($_POST['PayType'] == PT_SUPPLIER) {
          $acc              = Creditor::get_accounts_name($_POST['person_id']);
          $_POST['code_id'] = $acc['payable_account'];
        } //elseif ($_POST['PayType'] == PT_WORKORDER)
        //	$_POST['code_id'] = DB_Company::_get_pref('default_assembly_act');
        else {
          $_POST['code_id'] = DB_Company::_get_pref($payment ? 'default_cogs_act' : 'default_inv_sales_act');
        }
        echo GL_UI::all('code_id', null, true, true);
      }
      Forms::amountCells(null, 'amount');
      Forms::textCellsEx(null, 'LineMemo', 35, 255);
      if ($id != -1) {
        Forms::buttonCell('updateItem', _("Update"), _('Confirm changes'), ICON_UPDATE);
        Forms::buttonCell('cancelItem', _("Cancel"), _('Cancel changes'), ICON_CANCEL);
        JS::_setFocus('amount');
      } else {
        Forms::submitCells('addLine', _("Add Item"), "colspan=2", _('Add new item to document'), true);
      }
      echo '</tr>';
    }

    protected function commit() {
      if (!$this->canProcess()) {
        return;
      }
      $trans            = GL_Bank::add_bank_transaction(
                                 $this->order->trans_type, $_POST['bank_account'], $this->order, $_POST['date_'], $_POST['PayType'], $_POST['person_id'], $this->Input->post('PersonDetailID'), $_POST['ref'], $_POST['memo_']
      );
      $this->trans_type = $trans[0];
      $this->trans_no   = $trans[1];
      Dates::_newDocDate($_POST['date_']);
      $this->order->clear_items();
      unset($_SESSION['pay_items']);
      $this->pageComplete();
    }

    /**
     * @return bool
     */
    protected function canProcess() {
      if ($this->order->count_gl_items() < 1) {
        Event::error(_("You must enter at least one payment line."));
        $this->JS->setFocus('code_id');
        return false;
      }
      if ($this->order->gl_items_total() == 0.0) {
        Event::error(_("The total bank amount cannot be 0."));
        $this->JS->setFocus('code_id');
        return false;
      }
      if (!Ref::is_new($_POST['ref'], $this->order->trans_type)) {
        $_POST['ref'] = Ref::get_next($this->order->trans_type);
      }
      if (!Dates::_isDate($_POST['date_'])) {
        Event::error(_("The entered date is invalid."));
        $this->JS->setFocus('date_');
        return false;
      } elseif (!Dates::_isDateInFiscalYear($_POST['date_'])) {
        Event::error(_("The entered date is not in fiscal year."));
        $this->JS->setFocus('date_');
        return false;
      }
      return true;
    }

    protected function pageComplete() {
      Event::success(_($this->type . " " . $this->trans_no . " has been entered"));
      Display::note(GL_UI::view($this->trans_type, $this->trans_no, _("&View the GL Postings for this " . $this->type)));
      Display::link_params($_SERVER['DOCUMENT_URI'], _("Enter A &Payment"), "NewPayment=yes");
      Display::link_params($_SERVER['DOCUMENT_URI'], _("Enter A &Deposit"), "NewDeposit=yes");
      $this->Ajax->activate('_page_body');
      $this->Page->endExit();
    }

    protected function runValidation() {
      Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
    }
  }

