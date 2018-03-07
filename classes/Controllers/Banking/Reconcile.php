<?php
  namespace ADV\Controllers\Banking;

  use ADV\Core\Input\Input;
  use ADV\App\Form\DropDown;
  use ADV\Core\Event;
  use DB_Comments;
  use GL_UI;
  use ADV\App\SysTypes;
  use ADV\Core\Arr;
  use ADV\App\Pager\Pager;
  use ADV\Core\View;
  use Bank_UI;
  use GL_Account;
  use Bank_Account;
  use ADV\Core\Num;
  use Bank_Undeposited;
  use Bank_Trans;
  use ADV\App\Dates;
  use ADV\App\Validation;
  use ADV\App\Display;
  use ADV\App\Forms;
  use ADV\App\UI;
  use ADV\App\Bank\Bank;
  use ADV\Core\Cell;
  use ADV\Core\Table;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  /**
   * @property \ADV\Core\Input\Input Input
   */
  class Reconcile extends \ADV\App\Controller\Action
  {
    /** @var Num Num */
    protected $Num;
    /** @var Dates Dates */
    protected $Dates;
    /** @var */
    protected $bank_account;
    /** @var */
    protected $bank_date;
    /** @var */
    protected $reconcile_date;
    /** @var */
    protected $begin_date;
    /** @var */
    protected $end_date;
    /** @var bool * */
    protected $accountHasStatements = false;
    protected $security = SA_RECONCILE;
    /**

     */
    protected function before() {
      $this->Dates          = Dates::i();
      $this->Num            = Num::i();
      $this->bank_account   = & $this->Input->postGlobal('bank_account', INPUT::NUMERIC, Bank_Account::get_default()['id']);
      $this->bank_date      = & $this->Input->postGlobal('bank_date', [$this, 'checkDate'], $this->Dates->today());
      $this->reconcile_date = & $this->Input->post('reconcile_date', null, $this->Dates->sqlToDate($_POST['bank_date']));
      $this->JS->openWindow(950, 500);
      $this->JS->footerFile('/js/libs/redips-drag-min.js');
      $this->JS->footerFile('/js/reconcile.js');
      if ($this->Input->post('reset')) {
        // GL_Account::reset_sql_for_reconcile($this->bank_account, $this->reconcile_date);
        $this->updateData();
      }
      if (Forms::isListUpdated('bank_account')) {
        $this->Session->setGlobal('bank_account', $this->bank_account);
        $this->Ajax->activate('bank_date');
        $this->updateData();
      }
      $this->accountHasStatements = Bank_Account::hasStatements($this->bank_account);
      if (Forms::isListUpdated('bank_date')) {
        $this->reconcile_date = $this->Dates->sqlToDate($this->bank_date);
        $this->Session->setGlobal('bank_date', $this->bank_date);
        $this->Ajax->activate('bank_date');
        $this->updateData();
      }
      if ($this->Input->post('_reconcile_date_changed')) {
        $this->bank_date = $this->Dates->dateToSql($this->reconcile_date);
        $this->Ajax->activate('bank_date');
        $this->updateData();
      }
      if ($this->accountHasStatements && $this->bank_date) {
        $this->end_date   = $this->Dates->dateToSql($this->bank_date);
        $this->begin_date = GL_Account::get_reconcile_start($this->bank_account, $this->end_date);
      } elseif ($this->accountHasStatements) {
        $this->begin_date = null;
        $this->end_date   = $this->Dates->today();
      }
      $id = Forms::findPostPrefix('_rec_');
      if ($id != -1) {
        $this->updateCheckbox($id);
      }
      $this->setTitle("Reconcile Bank Account");
      $this->runAction();
    }
    /**

     */
    protected function index() {
      Forms::start();
      Table::start();
      echo '<tr>';
      Bank_Account::cells(_("Account:"), 'bank_account', null, true);
      Bank_UI::reconcile_cells(_("Bank Statement:"), $this->Input->post('bank_account'), 'bank_date', null, true, _("New"));
      Forms::buttonCell("reset", "reset", "reset");
      echo '</tr>';
      Table::end();
      $this->displaySummary();
      echo "<hr><div id='drag'>";
      echo $this->render();
      echo '</div>';
      Forms::end();
      if (!$this->Ajax->inAjax() || REQUEST_AJAX) {
        $this->addDialogs();
      }
      $this->JS->addLive("Adv.Reconcile.setUpGrid();");
    }
    /**

     */
    protected function addDialogs() {
      $date_dialog  = new View('ui/date_dialog');
      $date_changer = new \ADV\Core\Dialog('Change Date', 'dateChanger', $date_dialog->render(true), ['resizeable' => false, 'modal' => true]);
      $date_changer->addButton('Save', 'Adv.Reconcile.changeDate(this)');
      $date_changer->addButton('Cancel', '$(this).dialog("close")');
      $date_changer->setTemplateData(['id' => '', 'date' => $this->begin_date]);
      $date_changer->show();
      $bank_accounts = Bank_Account::getAll();
      $bank_dialog   = new View('ui/bank_dialog');
      $bank_dialog->set('bank_search', UI::select('changeBank', $bank_accounts, [], null, true));
      $bank_dialog->render();
    }
    /**
     * @return string
     */
    protected function render() {
      ob_start();
      echo '<div id="newgrid">';
      $this->accountHasStatements ? $this->statementLayout() : $this->simpleLayout();
      echo '</div>';
      return ob_get_clean();
    }
    /**
     * @return bool
     */
    protected function simpleLayout() {
      $sql = GL_Account::get_sql_for_reconcile($this->bank_account, $this->reconcile_date);
      $act = Bank_Account::get($this->bank_account);
      Display::heading($act['bank_account_name'] . " - " . $act['bank_curr_code']);
      $cols  = array(
        _("Type")        => array('fun' => array($this, 'formatType'), 'ord' => ''), //
        _("#")           => array('fun' => array($this, 'formatTrans'), 'ord' => ''), //
        _("Reference")   => array('fun' => [$this, 'formatReference']), //
        _("Date")        => array('type' => 'date', 'ord' => ''), //
        _("Debit")       => array('align' => 'right', 'fun' => array($this, 'formatDebit'), 'ord' => ''), //
        _("Credit")      => array('align' => 'right', 'insert' => true, 'fun' => array($this, 'formatCredit'), 'ord' => ''), //
        _("Person/Item") => array('fun' => array($this, 'formatInfo')), //
        array('insert' => true, 'fun' => array($this, 'formatGL')), //
        "X"              => array('insert' => true, 'fun' => array($this, 'formatCheckbox')), //
        ['insert' => true, 'fun' => array($this, 'formatDropdown')], ////
      );
      $table = \ADV\App\Pager\Pager::newPager('bank_rec', $cols);
      $table->setData($sql);
      $table->width       = "80";
      $table->rowFunction = [$this, 'formatRow'];
      $table->display($table);
      return true;
    }
    /**
     * @return bool
     */
    protected function statementLayout() {
      $rec             = Bank_Trans::getPeriod($this->bank_account, $this->begin_date, $this->end_date);
      $statement_trans = Bank_Account::getStatement($this->bank_account, $this->begin_date, $this->end_date);
      if (!$statement_trans) {
        return $this->simpleLayout();
      }
      $known_trans                 = [];
      $known_headers               = [
        'type',
        'trans_no',
        'ref',
        'trans_date',
        'id',
        'amount',
        'person_id',
        'person_type_id',
        'reconciled'
      ];
      $known_headers               = array_combine(array_values($known_headers), array_pad([], count($known_headers), ''));
      $statement_transment_headers = array_combine(array_keys($statement_trans[0]), array_values(array_pad([], count($statement_trans[0]), '')));
      while ($v = array_shift($statement_trans)) {
        $amount = $v['state_amount'];
        if ($v['reconciled_to_id']) {
          foreach ($rec as $p => $q) {
            if ($q['id'] == $v['reconciled_to_id']) {
              $matched = $rec[$p] + $v;
              unset($rec[$p]);
              $known_trans[] = $matched;
              continue 2;
            }
          }
        }
        foreach ($rec as $p => $q) {
          if (Num::_round($q['amount'], 2) == $amount) {
            $matched = $rec[$p] + $v;
            unset($rec[$p]);
            $known_trans[] = $matched;
            continue 2;
          }
        }
        $newv = $known_headers;
        Arr::append($newv, $v);
        $known_trans[] = $newv;
      }
      foreach ($rec as &$r) {
        Arr::append($r, $statement_transment_headers);
      }
      Arr::append($known_trans, $rec);
      usort($known_trans, [$this, 'sortByOrder']);
      $cols  = [
        'Type'   => ['fun' => array($this, 'formatType')], //
        '#'      => ['align' => 'center', 'fun' => array($this, 'formatTrans')], //
        ['type' => 'skip'], //
        'Date'   => ['type' => 'date'], //
        'Debit'  => ['align' => 'right', 'fun' => array($this, 'formatDebit')], //
        'Credit' => ['align' => 'right', 'insert' => true, 'fun' => array($this, 'formatCredit')], //
        'Info'   => ['fun' => array($this, 'formatInfo')], //
        'GL'     => ['fun' => array($this, 'formatGL')], //
        ['fun' => array($this, 'formatCheckbox')], //
        'Banked' => ['type' => 'date'], //
        'Amount' => ['align' => 'right', 'class' => 'bold'], //
        'Memo'   => ['class' => 'state_memo'], //
        ['fun' => array($this, 'formatDropdown')], //
      ];
      $table = \ADV\App\Pager\Pager::newPager('bank_rec', $cols);
      $table->setData($known_trans);
      $table->class       = 'recgrid';
      $table->rowFunction = [$this, 'formatRow'];
      $table->display();
      return true;
    }
    /**

     */
    protected function displaySummary() {
      $this->getTotal();
      $this->Ajax->start_div('summary');
      Table::start();
      Table::sectionTitle(_("Reconcile Date"), 1);
      echo '<tr>';
      Forms::dateCells("", "reconcile_date", _('Date of bank statement to reconcile'), $this->bank_date == '', 0, 0, 0, null, true);
      echo '</tr>';
      Table::sectionTitle(_("Beginning Balance"), 1);
      echo '<tr>';
      Forms::amountCellsEx("", "beg_balance", 15);
      echo '</tr>';
      Table::sectionTitle(_("Ending Balance"), 1);
      echo '<tr>';
      Forms::amountCellsEx("", "end_balance", 15);
      $reconciled = Validation::input_num('reconciled');
      $difference = Validation::input_num("end_balance") - Validation::input_num("beg_balance") - $reconciled;
      echo '</tr>';
      Table::sectionTitle(_("Reconciled Amount"), 1);
      echo '<tr>';
      Cell::amount($reconciled, false, '', "reconciled");
      echo '</tr>';
      Table::sectionTitle(_("Difference"), 1);
      echo '<tr>';
      Cell::amount($difference, false, '', "difference");
      echo '</tr>';
      Table::end();
      $this->Ajax->end_div();
      $this->Ajax->activate('summary');
    }
    /**
     * @return int
     */
    protected function getTotal() {
      if ($this->accountHasStatements) {
        list($beg_balance, $end_balance) = Bank_Account::getBalances($this->bank_account, $this->begin_date, $this->end_date);
        $_POST["beg_balance"] = $this->Num->priceFormat($beg_balance);
        $_POST["end_balance"] = $this->Num->priceFormat($end_balance);
        $_POST["reconciled"]  = $this->Num->priceFormat($end_balance - $beg_balance);
      }
      $result = GL_Account::get_max_reconciled($this->reconcile_date, $this->bank_account);
      if ($row = static::$DB->fetch($result)) {
        $_POST["reconciled"] = $this->Num->priceFormat($row["end_balance"] - $row["beg_balance"]);
        if (!isset($_POST["beg_balance"])) {
          $_POST["last_date"]   = $this->Dates->sqlToDate($row["last_date"]);
          $_POST["beg_balance"] = $this->Num->priceFormat($row["beg_balance"]);
          $_POST["end_balance"] = $this->Num->priceFormat($row["end_balance"]);
          if ($this->bank_date) {
            // if it is the last updated bank statement retrieve ending balance
            $row = GL_Account::get_ending_reconciled($this->bank_account, $this->bank_date);
            if ($row) {
              $_POST["end_balance"] = $this->Num->priceFormat($row["ending_reconcile_balance"]);
            }
          }
        }
      }
      return;
    }
    /**

     */
    protected function changeDate() {
      $bank_trans_id = $this->Input->post('trans_id', Input::NUMERIC, -1);
      $newdate       = $this->Input->post('date');
      /** @noinspection PhpUndefinedVariableInspection */
      Bank_Trans::changeDate($bank_trans_id, $newdate, $status);
      $data['status'] = $status->get();
      $data['grid']   = $this->render();
      $this->JS->renderJSON($data);
    }
    /**
     * @internal param $row
     * @return string
     */
    protected function changeBank() {
      $newbank  = $this->Input->post('newbank', Input::NUMERIC);
      $trans_no = $this->Input->post('trans_no', Input::NUMERIC);
      $type     = $this->Input->post('type', Input::NUMERIC);
      if ($newbank && $type && $trans_no) {
        Bank_Trans::changeBankAccount($trans_no, $type, $this->bank_account, $newbank);
      }
      $data['grid'] = $this->render();
      $this->JS->renderJSON($data);
    }
    /**

     */
    protected function unGroup() {
      $groupid = $this->Input->post('groupid', Input::NUMERIC);
      if ($groupid > 0) {
        Bank_Undeposited::ungroup($groupid);
        $this->updateData();
      }
      $data['grid'] = $this->render();
      $this->JS->renderJSON($data);
    }
    /**
     * @return mixed
     */
    protected function deposit() {
      $trans1 = $this->Input->post('trans1', INPUT::NUMERIC);
      $trans2 = $this->Input->post('trans2', INPUT::NUMERIC);
      Bank_Undeposited::addToGroup($trans1, $this->bank_account, $trans2);
      $data['grid'] = $this->render();
      $this->JS->renderJSON($data);
    }
    /**
     * @internal param $prefix
     * @return bool|mixed
     */
    protected function runValidation() {
      Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
    }
    /**
     * @param $row
     *
     * @return string
     */
    public function formatCheckbox($row) {
      if (!$row['amount']) {
        return '';
      }
      $name     = "rec_" . $row['id'];
      $state_id = $row['state_id'];
      $hidden   = 'last[' . $row['id'] . ']';
      $value    = $row['reconciled'] != '';
      return Forms::checkbox(null, $name, $value, false, _('Reconcile this transaction')) . Forms::hidden($hidden, $value, false) . Forms::hidden(
        'state_' . $row['id'], $state_id, false
      );
    }
    /**
     * @param $row
     *
     * @return string
     */
    public function formatRow($row) {
      $tocheck = 'done';
      /* $comment = Bank_Trans::getInfo($row['trans_no'], $row['type']);
      foreach ($comment as $trans) {
        if (stripos($trans['memo_'], "casey") !== false) {
          $tocheck = 'overduebg';
          break;
        }
      }*/
      if (!$row['trans_date'] && !$row['reconciled'] && $row['state_date']) {
        $class  = "class='overduebg deny mark'";
        $amount = e($row['state_amount']);
        $date   = e($this->Dates->sqlToDate($row['state_date']));
        return "$class data-date='$date' data-amount='$amount' ";
      }
      $name     = $row['id'];
      $amount   = $row['amount'];
      $date     = $this->Dates->sqlToDate($row['trans_date']);
      $type     = $row['type'];
      $trans_no = $row['trans_no'];
      $class    = "class='cangroup'";
      if ($row['reconciled'] && $row['state_date']) {
        return "class='$tocheck deny'";
      } elseif (!isset($row['state_date'])) {
        $class = "class='cangroup'";
      } elseif (($row['trans_date'] && $row['reconciled'] && !$row['state_date']) || ($row['state_date'] && !$row['transdate'])
      ) {
        $class = "class='cangroup overduebg'";
      }
      // save also in hidden field for testing during 'Reconcile'
      return "$class data-id='$name' data-date='$date' data-type='$type' data-transno='$trans_no' data-amount='$amount'";
    }
    /**
     * @param $row
     *
     * @internal param $dummy
     * @internal param $type
     * @return mixed
     */
    public function formatType($row) {
      $type = $row['type'];
      if (!$type) {
        return '';
      }
      return SysTypes::$names[$type];
    }
    /**
     * @param $row
     *
     * @internal param $trans
     * @return null|string
     */
    public function formatTrans($row) {
      $content = '';
      if ($row['type'] != ST_GROUPDEPOSIT) {
        $content = GL_UI::viewTrans($row["type"], $row["trans_no"]);
      }
      return $content;
    }
    /**
     * @param $row
     *
     * @return string
     */
    public function formatDropdown($row) {
      if ($row['reconciled']) {
        return '';
      }
      $dd = new DropDown();
      if ($row['state_amount']) {
        if ($row['state_amount'] > 0) {
          $data = [];
          if (stripos($row['memo'], 'AMEX')) {
            preg_match('/([0-9]+\.[0-9]+)/', $row['memo'], $beforefee);
            $fee  = $beforefee[1] - $row['state_amount'];
            $data = ['fee' => $fee, 'amount' => $beforefee[1]];
          }
          $dd->addItem('Debtor Payment', '/sales/payment', $data, ['class' => 'createDP']);
          $dd->addItem('Bank Deposit', '/banking/banking?NewDeposit=Yes', $data, ['class' => 'createBD']);
        } else {
          $dd->addItem('Creditor Payment', '/purchases/payment', [], ['class' => 'createCP']);
          $dd->addItem('Bank Payment', '/banking/banking?NewPayment=Yes', [], ['class' => 'createBP']);
        }
        $dd->addItem('Funds Transfer', '/gl/bank_transfer', [], ['class' => 'createFT']);
        $dd->addDivider();
      }
      $dd->addItem('Change Date', '#', [], ['class' => 'changeDate']);
      switch ($row['type']) {
        case ST_GROUPDEPOSIT:
          $dd->addItem('unGroup', '#', [], ['class' => 'unGroup'])->setTitle('Group');
          break;
        case ST_BANKDEPOSIT:
        case ST_CUSTPAYMENT:
        default:
          $dd->addItem('Move Bank', '#', [], ['class' => 'changeBank']);
          $dd->addItem('Void Trans', '#', ['type' => $row['type'], 'trans_no' => $row['trans_no']], ['class' => 'voidTrans']);
          $dd->setTitle(substr($row['ref'], 0, 7));
      }
      $result = $dd->setAuto(true)->render(true);
      unset($dd);
      return $result;
    }
    /**
     * @param $row
     *
     * @return string
     */
    public function formatGL($row) {
      if (!$row['amount']) {
        return '';
      }
      return ($row['type'] != ST_GROUPDEPOSIT) ? GL_UI::view($row["type"], $row["trans_no"]) : '';
    }
    /**
     * @param $row
     *
     * @return int|string
     */
    public function formatDebit($row) {
      $value = $row["amount"];
      if ($value > 0) {
        return '<span class="bold">' . $this->Num->priceFormat($value) . '</span>';
      }
      return '';
    }
    /**
     * @param $row
     *
     * @return int|string
     */
    public function formatCredit($row) {
      $value = -$row["amount"];
      if ($value <= 0) {
        return '';
      }
      return '<span class="bold">' . $this->Num->priceFormat($value) . '</span>';
    }
    /**
     * @param $row
     *
     * @return string
     */
    public function formatInfo($row) {
      $content = '';
      if ($row['type'] == ST_BANKTRANSFER) {
        $content = DB_Comments::get_string(ST_BANKTRANSFER, $row['trans_no']);
      } elseif ($row['type'] == ST_GROUPDEPOSIT) {
        $result = Bank_Trans::getGroupDeposit($this->bank_account, $row['id']);
        foreach ($result as $trans) {
          $name = Bank::payment_person_name($trans["person_type_id"], $trans["person_id"], true, $trans["trans_no"]);
          $content .= $trans['ref'] . ' <span class="u">' . $name . ' ($' . $this->Num->priceFormat($trans['amount']) . ')</span>: ' . $trans['memo_'] . '<br>';
        }
      } else {
        $content = Bank::payment_person_name($row["person_type_id"], $row["person_id"], true, $row["trans_no"]);
      }
      if (!$row['reconciled'] && ($row['trans_no'] || $row['type'] == ST_GROUPDEPOSIT)) {
        return '<div class="drag row">' . $content . '</div>';
      }
      return '<div class="deny row">' . $content . '</div>';
    }
    /**
     * @param $a
     * @param $b
     *
     * @return int
     */
    public function sortByOrder($a, $b) {
      $date1 = $a['state_date'] ? : $a['trans_date'];
      $date2 = $b['state_date'] ? : $b['trans_date'];
      if ($date1 == $date2) {
        $amount1 = $a['state_amount'] ? : $a['amount'];
        $amount2 = $b['state_amount'] ? : $b['amount'];
        return $amount1 - $amount2;
      }
      return strcmp($date1, $date2);
    }
    /**

     */
    public function updateData() {
      \ADV\App\Pager\Pager::kill('bank_rec');
      unset($_POST["beg_balance"], $_POST["end_balance"]);
      $this->Ajax->activate('_page_body');
    }
    /**
     * @param $date
     *
     * @return bool
     */
    public function checkDate($date) {
      $date = $this->Dates->sqlToDate($date);
      return $this->Dates->isDate($date);
    }
    /**
     * @param $reconcile_id
     *
     * @return bool
     */
    public function updateCheckbox($reconcile_id) {
      if (!$this->Dates->isDate($this->reconcile_date) && $this->Input->hasPost("rec_" . $reconcile_id)) // temporary fix
      {
        Event::error(_("Invalid reconcile date format"));
        $this->JS->setFocus('reconcile_date');
        return false;
      }
      if ($this->bank_date == '') // new reconciliation
      {
        $this->bank_date = $this->reconcile_date;
        $this->Session->setGlobal('bank_date', $this->bank_date);
        $this->Ajax->activate('bank_date');
      }
      $reconcile_value = $this->Input->hasPost("rec_" . $reconcile_id) ? ("'" . $this->Dates->dateToSql($this->reconcile_date) . "'") : 'null';
      GL_Account::update_reconciled_values(
        $reconcile_id, $reconcile_value, $this->reconcile_date, Validation::input_num('end_balance'), $this->bank_account, $this->Input->post('state_' . $reconcile_id, Input::NUMERIC, -1)
      );
      $this->Ajax->activate('_page_body');
      return true;
    }
  }

