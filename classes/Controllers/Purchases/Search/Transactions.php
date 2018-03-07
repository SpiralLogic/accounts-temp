<?php

  namespace ADV\Controllers\Purchases\Search;

  use ADV\Core\DB\DB;
  use ADV\App\Form\DropDown;
  use ADV\Core\Event;
  use ADV\App\Reporting;
  use DB_Company;
  use ADV\Core\Num;
  use GL_UI;
  use ADV\App\SysTypes;
  use ADV\App\Pager\Pager;
  use ADV\App\Dates;
  use Purch_Allocation;
  use ADV\App\Forms;
  use ADV\Core\Session;
  use ADV\Core\Cell;
  use ADV\App\Creditor\Creditor;
  use ADV\Core\Table;
  use ADV\Core\Input\Input;
  use ADV\Core\JS;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Transactions extends \ADV\App\Controller\Action
  {
    public $creditor_id;
    protected $security = SA_SUPPTRANSVIEW;
    protected function before() {
      JS::_openWindow(950, 500);
      if (isset($_GET['FromDate'])) {
        $_POST['TransAfterDate'] = $_GET['FromDate'];
      }
      if (isset($_GET['ToDate'])) {
        $_POST['TransToDate'] = $_GET['ToDate'];
      }
      $this->creditor_id = Input::_getPost('creditor_id', INPUT::NUMERIC, -1);
      if (!$this->creditor_id) {
        $_POST['creditor_id'] = $this->creditor_id = Session::_getGlobal('creditor_id', -1);
        Session::_setGlobal('creditor_id', $this->creditor_id);
      }
      if (Input::_post('RefreshInquiry')) {
        $this->Ajax->activate('totals_tbl');
      }
      $this->setTitle("Supplier Inquiry");
    }
    protected function index() {
      Forms::start();
      Table::start('noborder');
      echo '<tr>';
      Creditor::cells(_(''), 'creditor_id', null, true);
      Forms::dateCells(_("From:"), 'TransAfterDate', '', null, -90);
      Forms::dateCells(_("To:"), 'TransToDate');
      Purch_Allocation::row("filterType", null);
      Forms::submitCells('RefreshInquiry', _("Search"), '', _('Refresh Inquiry'), 'default');
      echo '</tr>';
      Table::end();
      $this->Ajax->start_div('totals_tbl');
      if ($this->creditor_id > 0) {
        $supplier_record = Creditor::get_to_trans($this->creditor_id);
        $this->displaySupplierSummary($supplier_record);
      }
      $this->Ajax->end_div();
      $this->displayTable();
      Creditor::addInfoDialog('.pagerclick');
      Forms::end();
    }
    protected function displayTable() {
      if (REQUEST_AJAX && !empty($_POST['q'])) {
        $searchArray = explode(' ', $_POST['q']);
        unset($_POST['creditor_id']);
      }
      $date_after = Dates::_dateToSql($_POST['TransAfterDate']);
      $date_to    = Dates::_dateToSql($_POST['TransToDate']);
      // Sherifoz 22.06.03 Also get the description
      $sql
        = "SELECT trans.type,
    		trans.trans_no,
    		trans.reference,
    		supplier.name,
    		supplier.creditor_id as id,
    		trans.supplier_reference,
     	trans.tran_date,
    		trans.due_date,
    		supplier.curr_code,
     	(trans.ov_amount + trans.ov_gst + trans.ov_discount) AS TotalAmount,
    		trans.alloc AS Allocated,
    		((trans.type = " . ST_SUPPINVOICE . " OR trans.type = " . ST_SUPPCREDIT . ") AND trans.due_date < '" . Dates::_today(true) . "') AS OverDue,
     	(ABS(trans.ov_amount + trans.ov_gst + trans.ov_discount - trans.alloc) <= 0.005) AS Settled
     	FROM creditor_trans as trans, suppliers as supplier
     	WHERE supplier.creditor_id = trans.creditor_id
     	AND trans.ov_amount != 0"; // exclude voided transactions
      if (REQUEST_AJAX && !empty($_POST['q'])) {
        foreach ($searchArray as $quicksearch) {
          if (empty($quicksearch)) {
            continue;
          }
          $quicksearch = "%" . $quicksearch . "%";
          $sql .= " AND (";
          $sql .= " supplier.name LIKE " . DB::_quote($quicksearch) . " OR trans.trans_no LIKE " . DB::_quote($quicksearch) . " OR trans.reference LIKE " . DB::_quote(
            $quicksearch
          ) . " OR trans.supplier_reference LIKE " . DB::_quote($quicksearch) . ")";
        }
      } else {
        $sql
          .= " AND trans . tran_date >= '$date_after'
    	 AND trans . tran_date <= '$date_to'";
      }
      if ($this->creditor_id > 0) {
        $sql .= " AND trans.creditor_id = " . DB::_quote($this->creditor_id);
      }
      if (isset($_POST['filterType']) && $_POST['filterType'] != ALL_TEXT) {
        if (($_POST['filterType'] == '1')) {
          $sql .= " AND (trans.type = " . ST_SUPPINVOICE . " OR trans.type = " . ST_BANKDEPOSIT . ")";
        } elseif (($_POST['filterType'] == '2')) {
          $sql .= " AND trans.type = " . ST_SUPPINVOICE . " ";
        } elseif (($_POST['filterType'] == '6')) {
          $sql .= " AND trans.type = " . ST_SUPPINVOICE . " ";
        } elseif ($_POST['filterType'] == '3') {
          $sql .= " AND (trans.type = " . ST_SUPPAYMENT . " OR trans.type = " . ST_BANKPAYMENT . ") ";
        } elseif (($_POST['filterType'] == '4') || ($_POST['filterType'] == '5')) {
          $sql .= " AND trans.type = " . ST_SUPPCREDIT . " ";
        }
        if (($_POST['filterType'] == '2') || ($_POST['filterType'] == '5')) {
          $today = Dates::_today(true);
          $sql .= " AND trans.due_date < '$today' ";
        }
      }
      $cols = array(
        _("Type")        => array('fun' => [$this, 'formatType'], 'ord' => ''), //
        _("#")           => array('fun' => [$this, 'formatView'], 'ord' => ''), //
        _("Reference"), //
        _("Supplier")    => array('type' => 'id'), //
        _("Supplier ID") => 'skip', //
        _("Supplier #"), //
        _("Date")        => array('name' => 'tran_date', 'type' => 'date', 'ord' => 'desc'), //
        _("Due Date")    => array('type' => 'date', 'fun' => [$this, 'formatDueDate']), //
        _("Currency")    => array('align' => 'center'), //
        _("Debit")       => array('align' => 'right', 'fun' => [$this, 'formatDebit']), //
        _("Credit")      => array('align' => 'right', 'insert' => true, 'fun' => [$this, 'formatCredit']), //
        array('insert' => true, 'fun' => [$this, 'fomatViewGl']), //
        array('insert' => true, 'fun' => [$this, 'formatDropdown']) //
      );
      if ($this->creditor_id > 0) {
        $cols[_("Supplier")] = 'skip';
        $cols[_("Currency")] = 'skip';
      }
      /*show a table of the transactions returned by the sql */
      $table = \ADV\App\Pager\Pager::newPager('purch_trans_tbl', $cols);
      $table->setData($sql);
      $table->rowFunction = [$this, 'formatMarker'];
      Event::warning(_("Marked items are overdue."), false);
      $table->width = "90";
      $table->display($table);
    }
    /**
     * @param $dummy
     * @param $type
     *
     * @return mixed
     */
    public function formatType($dummy, $type) {
      return SysTypes::$names[$type];
    }
    /**
     * @param $trans
     *
     * @return null|string
     */
    public function formatView($trans) {
      return GL_UI::viewTrans($trans["type"], $trans["trans_no"]);
    }
    /**
     * @param $row
     *
     * @return string
     */
    public function formatDueDate($row) {
      return ($row["type"] == ST_SUPPINVOICE) || ($row["type"] == ST_SUPPCREDIT) ? $row["due_date"] : '';
    }
    /**
     * @param $row
     *
     * @return string
     */
    public function fomatViewGl($row) {
      return GL_UI::view($row["type"], $row["trans_no"]);
    }
    /**
     * @param $row
     *
     * @return int|string
     */
    public function formatDebit($row) {
      $value = $row["TotalAmount"];
      return $value >= 0 ? Num::_priceFormat($value) : '';
    }
    /**
     * @param $row
     *
     * @return int|string
     */
    public function formatCredit($row) {
      $value = -$row["TotalAmount"];
      return $value > 0 ? Num::_priceFormat($value) : '';
    }
    /**
     * @param $row
     *
     * @return string
     */
    public function formatDropdown($row) {
      $dd = new DropDown();
      if ($row['type'] == ST_SUPPINVOICE && $row["TotalAmount"] - $row["Allocated"] > 0) {
        $dd->addItem('Credit', "/purchases/credit?New=1&invoice_no=" . $row['trans_no']);
      }
      if ($row['type'] == ST_SUPPAYMENT || $row['type'] == ST_BANKPAYMENT || $row['type'] == ST_SUPPCREDIT) {
        $href = Reporting::print_doc_link($row['trans_no'] . "-" . $row['type'], _("Remittance"), true, ST_SUPPAYMENT, ICON_PRINT, 'printlink', '', 0, 0, true);
        $dd->addItem('Print Remittance', $href, [], ['class' => 'printlink']);
      }
      if (empty($items)) {
        return '';
      }
      if ($this->User->hasAccess(SA_VOIDTRANSACTION)) {
        $href = '/system/void_transaction?type=' . $row['type'] . '&trans_no=' . $row['trans_no'] . '&memo=Deleted%20during%20order%20search';
        $dd->addItem('Void Trans', $href, [], ['target' => '_blank']);
      }
      return $dd->setTitle('Menu')->render(true);
    }
    /**
     * @param $row
     *
     * @return bool
     */
    public function formatMarker($row) {
      if ($row['OverDue'] == 1 && (abs($row["TotalAmount"]) - $row["Allocated"] != 0)) {
        return "class='overduebg'";
      }
    }
    /**
     * @param $supplier_record
     */
    function displaySupplierSummary($supplier_record) {
      $past_due1     = DB_Company::_get_pref('past_due_days');
      $past_due2     = 2 * $past_due1;
      $txt_now_due   = "1-" . $past_due1 . " " . _('Days');
      $txt_past_due1 = $past_due1 + 1 . "-" . $past_due2 . " " . _('Days');
      $txt_past_due2 = _('Over') . " " . $past_due2 . " " . _('Days');
      Table::start('padded width90');
      $th = array(
        _("Currency"),
        _("Terms"),
        _("Current"),
        $txt_now_due,
        $txt_past_due1,
        $txt_past_due2,
        _("Total Balance"),
        _("Total For Search Period")
      );
      Table::header($th);
      echo '<tr>';
      Cell::label($supplier_record["curr_code"]);
      Cell::label($supplier_record["terms"]);
      Cell::amount($supplier_record["Balance"] - $supplier_record["Due"]);
      Cell::amount($supplier_record["Due"] - $supplier_record["Overdue1"]);
      Cell::amount($supplier_record["Overdue1"] - $supplier_record["Overdue2"]);
      Cell::amount($supplier_record["Overdue2"]);
      Cell::amount($supplier_record["Balance"]);
      Cell::amount(Creditor::get_oweing($_POST['creditor_id'], $_POST['TransAfterDate'], $_POST['TransToDate']));
      echo '</tr>';
      Table::end(1);
    }
  }

