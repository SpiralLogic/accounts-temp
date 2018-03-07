<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Controllers\Gl\Search;

  use ADV\App\Display;
  use ADV\Core\Num;
  use ADV\App\Pager\Pager;
  use GL_Trans;
  use GL_Account;
  use GL_UI;
  use ADV\App\Bank\Bank;
  use ADV\App\SysTypes;
  use ADV\Core\Table;
  use ADV\App\Forms;
  use ADV\Core\Input\Input;

  /** **/
  class Account extends \ADV\App\Controller\Action
  {
    public $account = null;
    public $TransFromDate;
    public $trans_date_from;
    public $trans_to_date;
    public $amount_min = null;
    public $amount_max = null;
    public $acccount_name;
    public $account_name;
    protected function before() {
      $this->setTitle('GL Inquiry');
      if ($this->Input->post('Show')) {
        $this->Ajax->activate('trans_tbl');
        $this->account         = & $this->Input->getPost('account');
        $this->trans_date_from = & $this->Input->getPost('TransFromDate', Input::DATE);
        $this->trans_to_date   = & $this->Input->getPost('TransToDate', Input::DATE);
        $this->amount_max      = & $this->Input->getPost('amount_max', Input::NUMERIC);
        $this->amount_min      = & $this->Input->getPost('amount_min', Input::NUMERIC);
        $this->Ajax->_addDebug([$this->amount_max, $this->amount_min]);
      }
    }
    /**
     * @return string
     */
    protected function getSql() {
      if (!$this->Input->post('Show')) {
        return null;
      }
      return GL_Trans::getSQL($this->trans_date_from, $this->trans_to_date, -1, $this->account, null, $this->amount_min, $this->amount_max);
    }
    protected function index() {
      $this->Page->start($this->title, SA_GLTRANSVIEW);
      Forms::start();
      $this->Ajax->start_div('trans_tbl');
      Table::start('noborder');
      echo '<tr>';
      GL_UI::all_cells(_("Account:"), 'account', null, false, false, "All Accounts");
      Forms::dateCells(_("from:"), 'TransFromDate', '', null, -30);
      Forms::dateCells(_("to:"), 'TransToDate');
      echo '</tr>';
      Table::end();
      Table::start();
      echo '<tr>';
      Forms::amountCellsSmall(_("Amount min:"), 'amount_min', null);
      Forms::amountCellsSmall(_("Amount max:"), 'amount_max', null);
      Forms::submitCells('Show', _("Show"), '', '', 'default');
      echo '</tr>';
      Table::end();
      echo '<hr>';
      Forms::end();
      $this->Ajax->end_div();
      $this->renderTable();
      $this->Page->end();
    }
    protected function renderTable() {
      $this->account_name = $this->account ? GL_Account::get_name($this->account) : "";
      if ($this->account) {
        Display::heading($this->account . "&nbsp;&nbsp;&nbsp;" . $this->account_name);
      }
      // Only show balances if an account is specified AND we're not filtering by amounts
      $cols = [ //
        _("Type")        => ['ord' => '', 'fun' => [$this, 'formatType']], //
        _("#")           => ['fun' => [$this, 'formatView']], //
        _("Date")        => ['ord' => '', 'type' => 'date'],
        _("Account")     => ['ord' => '', 'fun' => [$this, 'formatAccount']],
        _("Person/Item") => ['fun' => [$this, 'formatPerson']], //
        _("Debit")       => ['fun' => [$this, 'formatDebit']], //
        _("Credit")      => ['insert' => true, 'fun' => [$this, 'formatCredit']], //
        _("Balance")     => ['insert' => true, 'fun' => [$this, 'formatBalance']],
        _("Memo"),
      ];
      /*    if ($_POST["account"] != null) {
      unset($cols[_("Account")]);
    }
    if (!$show_balances) {
      unset($cols[_("Balance")]);
    }
    if ($_POST["account"] != null && GL_Account::is_balancesheet($_POST["account"])) {
      $begin = "";
    } else {
      $begin = Dates::_beginFiscalYear();
      if (Dates::_isGreaterThan($begin, $_POST['TransFromDate'])) {
        $begin = $_POST['TransFromDate'];
      }
      $begin = Dates::_addDays($begin, -1);
    }
    $bfw = 0;
    if ($show_balances) {
      $bfw = GL_Trans::get_balance_from_to($begin, $_POST['TransFromDate'], $_POST["account"], $_POST['Dimension'], $_POST['Dimension2']);
      echo "<tr class='inquirybg'>";
      Cell::label("<span class='bold'>" . _("Opening Balance") . " - " . $_POST['TransFromDate'] . "</span>", "colspan=");
      Cell::debitOrCredit($bfw);
      Cell::label("");
      Cell::label("");
      echo '</tr>';
    }
    $running_total = $bfw;*/
      //  \ADV\App\Pager\Pager::kill('GL_Account');
      $sql   = $this->getSql();
      $table = \ADV\App\Pager\Pager::newPager('GL_Account', $cols);
      $table->setData($sql);
      $table->width = "90";
      $table->display();
      //end of while loop
      /*    if ($show_balances) {
        echo "<tr class='inquirybg'>";
        Cell::label("<span class='bold'>" . _("Ending Balance") . " - " . $_POST['TransToDate'] . "</span>", "colspan=");
        Cell::debitOrCredit($running_total);
        Cell::label("");
        Cell::label("");
        echo '</tr>';
      }
      Table::end(2);
      if (DB::_numRows() == 0) {
        Event::warning(_("No general ledger transactions have been created for the specified criteria."), 0, 1);
      }*/
    }
    /**
     * @param $row
     *
     * @return string
     */
    public function formatView($row) {
      return GL_UI::view($row["type"], $row["type_no"], $row["type_no"], true);
    }
    /**
     * @param $row
     *
     * @return string
     */
    public function formatAccount($row) {
      return $row["account"] . ' ' . GL_Account::get_name($row["account"]);
    }
    /**
     * @param $row
     *
     * @return string
     */
    public function formatPerson($row) {
      return Bank::payment_person_name($row["person_type_id"], $row["person_id"]);
    }
    /**
     * @param $row
     *
     * @return mixed
     */
    public function formatBalance($row) {
      static $running_total = 0;
      $running_total += $row["amount"];
      return Num::_priceFormat($running_total);
    }
    /**
     * @param $row
     *
     * @return mixed
     */
    public function formatType($row) {
      return SysTypes::$names[$row["type"]];
    }
    /**
     * @param $row
     *
     * @return int|string
     */
    public function formatDebit($row) {
      $value = $row["amount"];
      if ($value > 0) {
        return '<span class="bold">' . Num::_priceFormat($value) . '</span>';
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
      return '<span class="bold">' . Num::_priceFormat($value) . '</span>';
    }
  }
