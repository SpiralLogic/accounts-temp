<?php
  namespace ADV\Controllers\Sales\Search;

  use ADV\App\Debtor\Debtor;
  use ADV\App\Form\DropDown;
  use ADV\Core\Event;
  use DB_AuditTrail;
  use ADV\App\Voiding;
  use ADV\App\SysTypes;
  use GL_UI;
  use ADV\App\Pager\Pager;
  use ADV\App\Dates;
  use Debtor_Payment;
  use ADV\App\Forms;
  use ADV\App\Reporting;
  use ADV\Core\Num;
  use ADV\Core\HTML;
  use ADV\Core\DB\DB;
  use ADV\Core\Table;
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
  class Transactions extends \ADV\App\Controller\Action
  {
    public $isQuickSearch;
    public $filterType;
    public $debtor_id;
    const SEARCH_DELIVERY    = 'd';
    const SEARCH_INVOICE     = 'i';
    const SEARCH_PAYMENT     = 'p';
    const FILTER_INVOICES    = '1';
    const FILTER_OVERDUE     = '2';
    const FILTER_PAYMENTS    = '3';
    const FILTER_CREDITS     = '4';
    const FILTER_DELIVERIES  = '5';
    const FILTER_INVOICEONLY = '6';
    protected $frame;
    protected $security = SA_SALESTRANSVIEW;
    protected function before() {
      $this->JS->openWindow(950, 500);
      $this->frame = $this->Input->hasGet('debtor_id');
      if (isset($_GET['id'])) {
        $_GET['debtor_id'] = $this->Input->get('id');
      }
      if ($this->Input->post('customer', Input::STRING) === '') {
        $this->Session->removeGlobal('debtor_id');
        unset(Input::$post['debtor_id']);
      }
      $this->debtor_id     = Input::$post['debtor_id'] = $this->Input->postGetGlobal('debtor_id', INPUT::NUMERIC, null);
      $this->filterType    = Input::$post['filterType'] = $this->Input->post('filterType', Input::NUMERIC);
      $this->isQuickSearch = ($this->Input->postGet('q'));
      $this->setTitle("Customer Transactions");
    }
    protected function index() {
      Forms::start();
      Table::start('noborder');
      echo '<tr>';
      Debtor::newselect(null, ['label' => false, 'row' => false]);
      Forms::refCellsSearch(_("#"), 'reference', '', null, '', true);
      Forms::dateCells(_("From:"), 'TransAfterDate', '', null, -30);
      Forms::dateCells(_("To:"), 'TransToDate', '', null, 1);
      Debtor_Payment::allocations_select(null, 'filterType', $this->filterType, true);
      Forms::submitCells('RefreshInquiry', _("Search"), '', _('Refresh Inquiry'), 'default');
      echo '</tr>';
      Table::end();
      $this->Ajax->start_div('totals_tbl');
      $this->displaySummary();
      $this->Ajax->end_div();
      if ($this->Input->post('RefreshInquiry')) {
        $this->Ajax->activate('totals_tbl');
      }
      $sql = ($this->isQuickSearch) ? $this->prepareQuickSearch() : $this->prepareSearch();
      if ($this->Input->post('reference')) {
        $number_like = "%" . $_POST['reference'] . "%";
        $sql .= " AND trans.reference LIKE " . DB::_quote($number_like);
      }
      if ($this->debtor_id) {
        $sql .= " AND trans.debtor_id = " . DB::_quote($this->debtor_id);
      }
      if ($this->filterType) {
        switch ($this->filterType) {
          case self::FILTER_INVOICES:
            $sql .= " AND (trans.type = " . ST_SALESINVOICE . " OR trans.type = " . ST_BANKPAYMENT . ") ";
            break;
          case self::FILTER_OVERDUE:
            $sql .= " AND (trans.type = " . ST_SALESINVOICE . ") AND trans.due_date < '" . Dates::_today(true) . "'
 				AND (trans.ov_amount + trans.ov_gst + trans.ov_freight_tax +
 				trans.ov_freight + trans.ov_discount - trans.alloc > 0)";
            break;
          case self::FILTER_PAYMENTS:
            $sql .= " AND (trans.type = " . ST_CUSTPAYMENT . " OR trans.type = " . ST_CUSTREFUND . " OR trans.type = " . ST_BANKDEPOSIT . " OR trans.type = " . ST_BANKDEPOSIT . ") ";
            break;
          case self::FILTER_CREDITS:
            $sql .= " AND trans.type = " . ST_CUSTCREDIT . " ";
            break;
          case self::FILTER_DELIVERIES:
            $sql .= " AND trans.type = " . ST_CUSTDELIVERY . " ";
            break;
          case self::FILTER_INVOICEONLY:
            $sql .= " AND trans.type = " . ST_SALESINVOICE . " ";
            break;
        }
      }
      if (!$this->isQuickSearch) {
        $sql .= " GROUP BY trans.trans_no, trans.type";
      }
      DB::_query("set @bal:=0");
      $cols = array(
        _("Type")      => array('fun' => [$this, 'formatType'], 'ord' => ''), //
        _("#")         => array('fun' => [$this, 'viewTrans'], 'ord' => ''), //
        _("Order")     => array('fun' => [$this, 'formatOrder']), //
        _("Reference") => array('ord' => ''), //
        _("Date")      => array('name' => 'tran_date', 'type' => 'date', 'ord' => 'desc'), //
        _("Due Date")  => array('type' => 'date', 'fun' => [$this, 'formatDueDate']), //
        _("Customer")  => array('ord' => 'asc'), //
        array('type' => 'skip'), //
        _("Branch")    => array('ord' => ''), //
        _("Currency")  => array('align' => 'center', 'type' => 'skip'), //
        _("Debit")     => array('align' => 'right', 'fun' => [$this, 'formatDebit']), //
        _("Credit")    => array('align' => 'right', 'insert' => true, 'fun' => [$this, 'formatCredit']), //
        array('type' => 'skip'), //
        _("RB")        => array('align' => 'right', 'type' => 'amount'), //
        array('insert' => true, 'fun' => [$this, 'formatGLView']), //
        array('insert' => true, 'fun' => [$this, 'formatDropDown']) //
      );
      if ($this->debtor_id) {
        $cols[_("Customer")] = 'skip';
        $cols[_("Currency")] = 'skip';
      }
      if (!$this->filterType || !$this->isQuickSearch) {
        $cols[_("RB")] = 'skip';
      }
      $table = \ADV\App\Pager\Pager::newPager('sales_trans_tbl', $cols);
      $table->setData($sql);
      $table->rowFunction = [$this, 'formatMarker'];
      $table->width       = "85%";
      Event::warning(_("Marked items are overdue."), false);
      $table->display($table);
      Forms::hidden('q');
      UI::emailDialogue(CT_CUSTOMER);
      Forms::end();
    }
    /**
     * @param $trans
     *
     * @return null|string
     */
    public function viewTrans($trans) {
      return GL_UI::viewTrans($trans["type"], $trans["trans_no"]);
    }
    /**
     * @return string
     */
    protected function prepareSearch() {
      $date_to    = Dates::_dateToSql($_POST['TransToDate']);
      $date_after = Dates::_dateToSql($_POST['TransAfterDate']);
      $sql
                  = "SELECT
 		trans.type,
 		trans.trans_no,
 		trans.order_,
 		trans.reference,
 		trans.tran_date,
 		trans.due_date,
 		debtor.name,
 		debtor.debtor_id,
 		branch.br_name,
 		debtor.curr_code,
 		(trans.ov_amount + trans.ov_gst + trans.ov_freight
 			+ trans.ov_freight_tax + trans.ov_discount)	AS TotalAmount, ";
      if ($this->filterType) {
        $sql .= "@bal := @bal+(trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount), ";
      }
      $sql
        .= "trans.alloc AS Allocated,
 		((trans.type = " . ST_SALESINVOICE . ")
 			AND trans.due_date < '" . Dates::_today(true) . "') AS OverDue, SUM(details.quantity - qty_done) as
 			still_to_deliver
 		FROM debtors as debtor, branches as branch,debtor_trans as trans
 		LEFT JOIN debtor_trans_details as details ON (trans.trans_no = details.debtor_trans_no AND trans.type = details.debtor_trans_type) WHERE debtor.debtor_id =
 		trans.debtor_id AND trans.branch_id = branch.branch_id";
      $sql .= " AND trans.tran_date >= '$date_after' AND trans.tran_date <= '$date_to'";
      $this->Ajax->activate('_page_body');
      return $sql;
    }
    /**
     * @return string
     */
    protected function prepareQuickSearch() {
      $searchArray = trim($this->Input->postGet('q'));
      $searchArray = explode(' ', $searchArray);
      if ($searchArray[0] == self::SEARCH_DELIVERY) {
        $filter = " AND type = " . ST_CUSTDELIVERY . " ";
      } elseif ($searchArray[0] == self::SEARCH_INVOICE) {
        $filter = " AND (type = " . ST_SALESINVOICE . " OR type = " . ST_BANKPAYMENT . ") ";
      } elseif ($searchArray[0] == self::SEARCH_PAYMENT) {
        $filter = " AND (type = " . ST_CUSTPAYMENT . " OR type = " . ST_CUSTREFUND . " OR type = " . ST_BANKDEPOSIT . ") ";
      }
      $sql = "SELECT * FROM debtor_trans_view WHERE ";
      foreach ($searchArray as $key => $quicksearch) {
        if (empty($quicksearch)) {
          continue;
        }
        $sql .= ($key == 0) ? " (" : " AND (";
        if ($quicksearch[0] == "$") {
          if (substr($quicksearch, -1) == 0 && substr($quicksearch, -3, 1) == '.') {
            $quicksearch = (substr($quicksearch, 0, -1));
          }
          $sql .= "Round(TotalAmount," . $this->User->prefs->price_dec . ") LIKE " . DB::_quote('%' . substr($quicksearch, 1) . '%') . ") ";
          continue;
        }
        if ($quicksearch[0] == ">" && $quicksearch[1] == "$") {
          $quicksearch = ltrim($quicksearch, '>');
          if (substr($quicksearch, -1) == 0 && substr($quicksearch, -3, 1) == '.') {
            $quicksearch = (substr($quicksearch, 0, -1));
          }
          $sql .= "Round(TotalAmount," . $this->User->prefs->price_dec . ") > " . DB::_quote(substr($quicksearch, 1)) . ") ";
          continue;
        }
        if (stripos($quicksearch, $this->User->prefs->date_sep) > 0) {
          $sql .= " tran_date = '" . Dates::_dateToSql($quicksearch) . "') ";
          continue;
        }
        if (is_numeric($quicksearch)) {
          $sql .= " debtor_id = $quicksearch OR ";
        }
        $search_value = DB::_quote("%" . $quicksearch . "%");
        $sql .= " name LIKE $search_value ";
        if (is_numeric($quicksearch)) {
          $sql .= " OR trans_no LIKE $search_value OR order_ LIKE $search_value ";
        }
        $sql .= " OR reference LIKE $search_value OR br_name LIKE $search_value) ";
      }
      if (isset($filter) && $filter) {
        $sql .= $filter;
      }
      return $sql;
    }
    protected function displaySummary() {
      if ($this->debtor_id && !$this->isQuickSearch) {
        $customer_record = Debtor::get_details($this->debtor_id, $_POST['TransToDate']);
        Debtor::display_summary($customer_record);
        echo "<br>";
      }
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
     * @param $row
     *
     * @return null|string
     */
    public function formatOrder($row) {
      return $row['order_'] > 0 ? Debtor::viewTrans(ST_SALESORDER, $row['order_']) : "";
    }
    /**
     * @param $row
     *
     * @return string
     */
    public function formatDueDate($row) {
      return $row["type"] == ST_SALESINVOICE ? $row["due_date"] : '';
    }
    /**
     * @param $row
     *
     * @return string
     */
    public function formatDebit($row) {
      $value = $row['type'] == ST_CUSTCREDIT || $row['type'] == ST_CUSTPAYMENT || $row['type'] == ST_CUSTREFUND || $row['type'] == ST_BANKDEPOSIT ? -$row["TotalAmount"] :
        $row["TotalAmount"];
      return $value >= 0 ? Num::_priceFormat($value) : '';
    }
    /**
     * @param $row
     *
     * @return string
     */
    public function formatCredit($row) {
      $value = !($row['type'] == ST_CUSTCREDIT || $row['type'] == ST_CUSTREFUND || $row['type'] == ST_CUSTPAYMENT || $row['type'] == ST_BANKDEPOSIT) ? -$row["TotalAmount"] :
        $row["TotalAmount"];
      return $value > 0 ? Num::_priceFormat($value) : '';
    }
    /**
     * @param $row
     *
     * @return string
     */
    public function formatGLView($row) {
      return GL_UI::view($row["type"], $row["trans_no"]);
    }
    /**
     * @param $row
     *
     * @return bool|string
     */
    public function formatEditBtn($row) {
      $str = '';
      switch ($row['type']) {
        case ST_SALESINVOICE:
          if (Voiding::get(ST_SALESINVOICE, $row["trans_no"]) === false || REQUEST_AJAX) {
            if ($row['Allocated'] == 0) {
              $str = "/sales/customer_invoice.php?ModifyInvoice=" . $row['trans_no'];
            } else {
              $str = "/sales/customer_invoice.php?ViewInvoice=" . $row['trans_no'];
            }
          }
          break;
        case ST_CUSTCREDIT:
          if (Voiding::get(ST_CUSTCREDIT, $row["trans_no"]) === false && $row['Allocated'] == 0) {
            if ($row['order_'] == 0) {
              $str = "/sales/credit?ModifyCredit=" . $row['trans_no'];
            } else {
              $str = "/sales/customer_credit_invoice.php?ModifyCredit=" . $row['trans_no'];
            }
          }
          break;
        case ST_CUSTDELIVERY:
          if ($row['still_to_deliver'] == 0) {
            continue;
          }
          if (Voiding::get(ST_CUSTDELIVERY, $row["trans_no"]) === false) {
            $str = "/sales/customer_delivery.php?ModifyDelivery=" . $row['trans_no'];
          }
          break;
      }
      if ($str && (!DB_AuditTrail::is_closed_trans($row['type'], $row["trans_no"]) || $row['type'] == ST_SALESINVOICE)) {
        return $str;
      }
      return false;
    }
    /**
     * @param $row
     *
     * @return \ADV\Core\HTML|string
     */
    public function formatEmailBtn($row) {
      if ($row['type'] != ST_SALESINVOICE) {
        return '';
      }
      return (new HTML)->button(
        false, 'Email', array(
                             'class'        => 'button email-button',
                             'data-emailid' => $row['debtor_id'] . '-' . $row['type'] . '-' . $row['trans_no']
                        )
      )->__toString();
    }
    /**
     * @param $row
     */
    public function formatReceiptBtn($row) {
    }
    /**
     * @param $row
     *
     * @return bool
     */
    public function formatMarker($row) {
      if ((isset($row['OverDue']) && $row['OverDue'] == 1) && (Num::_round($row["TotalAmount"], 2) - Num::_round($row["Allocated"], 2) != 0)) {
        return "class='overduebg'";
      }
    }
    /**
     * @param $row
     *
     * @return string
     */
    public function formatDropdown($row) {
      $dd = new DropDown();
      $dd->setTitle("Print");
      $caption = _("Print");
      if ($row['type'] == ST_SALESINVOICE) {
        $edit_url = $this->formatEditBtn($row);
        $dd->addItem('Edit', $edit_url);
        $dd->setTitle("Edit");
      }
      if (in_array($row['type'], [ST_CUSTPAYMENT, ST_CUSTREFUND, ST_BANKDEPOSIT])) {
        $dd->setTitle("Receipt");
        $title = $caption = _("Receipt");
      }
      $href = Reporting::print_doc_link($row['trans_no'], $caption, true, $row['type'], ICON_PRINT, 'button printlink', '', 0, 0, true);
      $dd->addItem($caption, $href, [], ['class' => 'printlink']);
      if ($row['type'] == ST_SALESINVOICE && $row["TotalAmount"] - $row["Allocated"] > 0) {
        $dd->addItem('Credit Invoice', "/sales/customer_credit_invoice.php?InvoiceNumber=" . $row['trans_no']);
      }
      if ($row['type'] == ST_SALESINVOICE && $row["TotalAmount"] - $row["Allocated"] > 0) {
        $dd->addItem('Make Payment', "/sales/payment?debtor_id=" . $row['debtor_id']);
      }
      $dd->addItem('Email', "#", ['emailid' => $row['debtor_id'] . '-' . $row['type'] . '-' . $row['trans_no']], ['class' => 'email-button']);
      if ($this->User->hasAccess(SA_VOIDTRANSACTION)) {
        $href = '/system/void_transaction?type=' . $row['type'] . '&trans_no=' . $row['trans_no'] . '&memo=Deleted%20during%20order%20search';
        $dd->addItem('Void Trans', $href, [], ['target' => '_blank']);
      }
      return $dd->setAuto(true)->setSplit(true)->render(true);
    }
  }

