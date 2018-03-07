<?php
  namespace ADV\Controllers\Purchases\Search;

  use ADV\Core\DB\DB;
  use ADV\App\Display;
  use ADV\Core\Event;
  use ADV\Core\Num;
  use GL_UI;
  use ADV\App\SysTypes;
  use ADV\App\Pager\Pager;
  use ADV\App\Dates;
  use Purch_Allocation;
  use ADV\App\Forms;
  use ADV\App\Creditor\Creditor;
  use ADV\Core\Table;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Allocations extends \ADV\App\Controller\Action
  {
    protected $security = SA_SUPPLIERALLOC;
    protected function before() {
      $this->JS->openWindow(950, 500);
      if (isset($_GET['creditor_id']) || isset($_GET['id'])) {
        $_POST['creditor_id'] = isset($_GET['id']) ? $_GET['id'] : $_GET['creditor_id'];
      }
      if (isset($_GET['creditor_id'])) {
        $_POST['creditor_id'] = $_GET['creditor_id'];
      }
      if (isset($_GET['FromDate'])) {
        $_POST['TransAfterDate'] = $_GET['FromDate'];
      }
      if (isset($_GET['ToDate'])) {
        $_POST['TransToDate'] = $_GET['ToDate'];
      }
      if (isset($_GET['frame'])) {
        foreach ($_GET as $k => $v) {
          $_POST[$k] = $v;
        }
      }
      if (!isset($_POST['creditor_id'])) {
        $_POST['creditor_id'] = $this->Session->getGlobal('creditor_id');
      }
      if (!isset($_POST['TransAfterDate']) && $this->Session->getGlobal('TransAfterDate')) {
        $_POST['TransAfterDate'] = $this->Session->getGlobal('TransAfterDate');
      } elseif (isset($_POST['TransAfterDate'])) {
        $this->Session->setGlobal('TransAfterDate', $_POST['TransAfterDate']);
      }
      if (!isset($_POST['TransToDate']) && $this->Session->getGlobal('TransToDate')) {
        $_POST['TransToDate'] = $this->Session->getGlobal('TransToDate');
      } elseif (isset($_POST['TransToDate'])) {
        $this->Session->setGlobal('TransToDate', $_POST['TransToDate']);
      }
      $this->setTitle("Supplier Allocation Inquiry");
    }
    protected function index() {
      Forms::start(false, '', 'invoiceForm');
      Table::start('noborder');
      echo '<tr>';
      if (!$this->Input->get('frame')) {
        Creditor::cells(_("Supplier: "), 'creditor_id', null, true);
      }
      Forms::dateCells(_("From:"), 'TransAfterDate', '', null, -90);
      Forms::dateCells(_("To:"), 'TransToDate', '', null, 1);
      Purch_Allocation::row("filterType", null);
      Forms::checkCells(_("Show settled:"), 'showSettled', null);
      Forms::submitCells('RefreshInquiry', _("Search"), '', _('Refresh Inquiry'), 'default');
      $this->Session->setGlobal('creditor_id', $_POST['creditor_id']);
      echo '</tr>';
      Table::end();
      $this->displayTable();
      Creditor::addInfoDialog('.pagerclick');
      Forms::end();
    }
    protected function displayTable() {
      $date_after = Dates::_dateToSql($_POST['TransAfterDate']);
      $date_to    = Dates::_dateToSql($_POST['TransToDate']);
      // Sherifoz 22.06.03 Also get the description
      $sql
        = "SELECT
            trans.type,
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
            ((trans.type = " . ST_SUPPINVOICE . " OR trans.type = " . ST_SUPPCREDIT . ") AND trans.due_date < '" . Dates::_today(true) . "') AS OverDue
         FROM creditor_trans as trans, suppliers as supplier
         WHERE supplier.creditor_id = trans.creditor_id
         AND trans.tran_date >= '$date_after'
         AND trans.tran_date <= '$date_to'";
      if ($_POST['creditor_id'] != ALL_TEXT) {
        $sql .= " AND trans.creditor_id = " . DB::_quote($_POST['creditor_id']);
      }
      if (isset($_POST['filterType']) && $_POST['filterType'] != ALL_TEXT) {
        if (($_POST['filterType'] == '1') || ($_POST['filterType'] == '2')) {
          $sql .= " AND trans.type = " . ST_SUPPINVOICE . " ";
        } elseif ($_POST['filterType'] == '3') {
          $sql .= " AND trans.type = " . ST_SUPPAYMENT . " ";
        } elseif (($_POST['filterType'] == '4') || ($_POST['filterType'] == '5')) {
          $sql .= " AND trans.type = " . ST_SUPPCREDIT . " ";
        }
        if (($_POST['filterType'] == '2') || ($_POST['filterType'] == '5')) {
          $today = Dates::_today(true);
          $sql .= " AND trans.due_date < '$today' ";
        }
      }
      if (!$this->Input->hasPost('showSettled')) {
        $sql .= " AND (round(abs(ov_amount + ov_gst + ov_discount) - alloc,6) != 0) ";
      }
      $cols = [
        _("Type")        => ['fun' => [$this, 'formatType']],
        _("#")           => ['fun' => [$this, 'formatViewLink'], 'ord' => ''],
        _("Reference"),
        _("Supplier")    => ['ord' => '', 'type' => 'id'],
        _("Supplier ID") => ['type' => 'skip'],
        _("Supp Reference"),
        _("Date")        => ['name' => 'tran_date', 'type' => 'date', 'ord' => 'desc'],
        _("Due Date")    => ['fun' => [$this, 'formatDueDate'], 'type' => 'date'],
        _("Currency")    => ['align' => 'center'],
        _("Debit")       => ['align' => 'right', 'fun' => [$this, 'formatDebit']],
        _("Credit")      => ['align' => 'right', 'insert' => true, 'fun' => [$this, 'formatCredit']],
        _("Allocated")   => ['type' => 'amount'],
        _("Balance")     => ['type' => 'amount', 'fun' => [$this, 'formatBalance']],
        ['insert' => true, 'fun' => [$this, 'formatAllocLink']]
      ];
      if ($_POST['creditor_id'] != ALL_TEXT) {
        $cols[_("Supplier ID")] = 'skip';
        $cols[_("Supplier")]    = 'skip';
        $cols[_("Currency")]    = 'skip';
      }
      $table = \ADV\App\Pager\Pager::newPager('purch_alloc_tbl', $cols);
      $table->setData($sql);
      $table->rowFunction = [$this, 'formatMarker'];
      Event::warning(_("Marked items are overdue."), false);
      $table->width = "90";
      $table->display($table);
    }
    /**
     * @param $row
     *
     * @return bool
     */
    public function formatMarker($row) {
      if ($row['OverDue'] == 1 && $row['TotalAmount'] > $row['Allocated']) {
        return "class='settledbg'";
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
     * @param $trans
     *
     * @return null|string
     */
    public function formatViewLink($trans) {
      return GL_UI::viewTrans($trans["type"], $trans["trans_no"]);
    }
    /**
     * @param $row
     *
     * @return string
     */
    public function formatDueDate($row) {
      return (($row["type"] == ST_SUPPINVOICE) || ($row["type"] == ST_SUPPCREDIT)) ? $row["due_date"] : "";
    }
    /**
     * @param $row
     *
     * @return mixed
     */
    public function formatBalance($row) {
      $value = ($row["type"] == ST_BANKPAYMENT || $row["type"] == ST_SUPPCREDIT || $row["type"] == ST_SUPPAYMENT) ? -$row["TotalAmount"] - $row["Allocated"] :
        $row["TotalAmount"] - $row["Allocated"];
      return $value;
    }
    /**
     * @param $row
     *
     * @return string
     */
    public function formatAllocLink($row) {
      $link = Display::link_button(_("Allocations"), "/purchases/allocations/supplier_allocate.php?trans_no=" . $row["trans_no"] . "&trans_type=" . $row["type"], ICON_MONEY);
      return (($row["type"] == ST_BANKPAYMENT || $row["type"] == ST_SUPPCREDIT || $row["type"] == ST_SUPPAYMENT) && (-$row["TotalAmount"] - $row["Allocated"]) > 0) ? $link : '';
    }
    /**
     * @param $row
     *
     * @return int|string
     */
    public function formatDebit($row) {
      $value = -$row["TotalAmount"];
      return $value >= 0 ? Num::_priceFormat($value) : '';
    }
    /**
     * @param $row
     *
     * @return int|string
     */
    public function formatCredit($row) {
      $value = $row["TotalAmount"];
      return $value > 0 ? Num::_priceFormat($value) : '';
    }
  }

