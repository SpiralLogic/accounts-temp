<?php
  namespace ADV\Controllers\Sales\Search;

  use ADV\Core\Input\Input;
  use ADV\App\Form\DropDown;
  use ADV\App\Reporting;
  use ADV\App\Display;
  use ADV\Core\Event;
  use ADV\App\Pager\Pager;
  use ADV\App\Dates;
  use Inv_Location;
  use ADV\App\Forms;
  use ADV\Core\DB\DB;
  use ADV\App\Item\Item;
  use ADV\App\Debtor\Debtor;
  use ADV\Core\Table;
  use ADV\Core\JS;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Deliveries extends \ADV\App\Controller\Action
  {
    public $debtor_id;
    public $stock_id;
    protected $security = SA_SALESINVOICE;
    protected function before() {
      JS::_openWindow(950, 600);
      if (isset($_GET['OutstandingOnly']) && ($_GET['OutstandingOnly'] == true)) {
        $_POST['OutstandingOnly'] = true;
        $this->setTitle("Search Not Invoiced Deliveries");
      } else {
        $_POST['OutstandingOnly'] = false;
        $this->setTitle("Search All Deliveries");
      }
      $this->debtor_id = $this->Input->getPost('debtor_id', Input::NUMERIC, -1);
      if ($this->Input->post('_DeliveryNumber_changed')) {
        $disable = $this->Input->post('DeliveryNumber') !== '';
        $this->Ajax->addDisable(true, 'DeliveryAfterDate', $disable);
        $this->Ajax->addDisable(true, 'DeliveryToDate', $disable);
        $this->Ajax->addDisable(true, 'StockLocation', $disable);
        $this->Ajax->addDisable(true, '_SelectStockFromList_edit', $disable);
        $this->Ajax->addDisable(true, 'SelectStockFromList', $disable);
        // if search is not empty rewrite table
        if ($disable) {
          $this->Ajax->addFocus(true, 'DeliveryNumber');
        } else {
          $this->Ajax->addFocus(true, 'DeliveryAfterDate');
        }
        $this->Ajax->activate('deliveries_tbl');
      }
      if ($this->Input->post('SelectStockFromList', Input::STRING, '')) {
        $this->stock_id = $_POST['SelectStockFromList'];
      }
    }
    protected function index() {
      if (isset($_POST[Orders::BATCH_INVOICE])) {
        $this->batchInvoice();
      }
      Forms::start(false, $_SERVER['DOCUMENT_URI'] . "?OutstandingOnly=" . $_POST['OutstandingOnly']);
      Table::start('noborder');
      echo '<tr>';
      Debtor::newselect(null, ['label' => false, 'row' => false]);
      Forms::refCellsSearch(_("#:"), 'DeliveryNumber', '', null, '', true);
      Forms::dateCells(_("from:"), 'DeliveryAfterDate', '', null, -30);
      Forms::dateCells(_("to:"), 'DeliveryToDate', '', null, 1);
      Inv_Location::cells(_("Location:"), 'StockLocation', null, true);
      Item::cells(_(""), 'SelectStockFromList', null, true, false, false, false, false);
      Forms::submitCells('SearchOrders', _("Search"), '', _('Select documents'), 'default');
      Forms::hidden('OutstandingOnly');
      echo '</tr>';
      Table::end();
      $this->displayTable();
      Forms::end();
    }
    protected function displayTable() {
      $sql
        = "SELECT trans.trans_no,
  		debtor.name,
  		branch.branch_id,
  		sorder.contact_name,
  		sorder.deliver_to,
  		trans.reference,
  		sorder.customer_ref,
  		trans.tran_date,
  		trans.due_date,
  		(ov_amount+ov_gst+ov_freight+ov_freight_tax) AS DeliveryValue,
  		debtor.curr_code,
  		Sum(line.quantity-line.qty_done) AS Outstanding,
  		Sum(line.qty_done) AS Done
  	FROM sales_orders as sorder, debtor_trans as trans, debtor_trans_details as line, debtors as debtor, branches as branch
  		WHERE
  		sorder.order_no = trans.order_ AND
  		trans.debtor_id = debtor.debtor_id
  			AND trans.type = " . ST_CUSTDELIVERY . "
  			AND line.debtor_trans_no = trans.trans_no
  			AND line.debtor_trans_type = trans.type
  			AND trans.branch_id = branch.branch_id
  			AND trans.debtor_id = branch.debtor_id ";
      if ($_POST['OutstandingOnly']) {
        $sql .= " AND line.qty_done < line.quantity ";
      }
      //figure out the sql required from the inputs available
      if (isset($_POST['DeliveryNumber']) && $_POST['DeliveryNumber'] != "") {
        $delivery = "%" . $_POST['DeliveryNumber'];
        $sql .= " AND trans.trans_no LIKE " . DB::_quote($delivery);
        $sql .= " GROUP BY trans.trans_no";
      } else {
        $sql .= " AND trans.tran_date >= '" . Dates::_dateToSql($_POST['DeliveryAfterDate']) . "'";
        $sql .= " AND trans.tran_date <= '" . Dates::_dateToSql($_POST['DeliveryToDate']) . "'";
        if ($this->debtor_id != -1) {
          $sql .= " AND trans.debtor_id=" . DB::_quote($this->debtor_id) . " ";
        }
        if (isset($this->stock_id)) {
          $sql .= " AND line.stock_id=" . DB::_quote($this->stock_id) . " ";
        }
        if (isset($_POST['StockLocation']) && $_POST['StockLocation'] != ALL_TEXT) {
          $sql .= " AND sorder.from_stk_loc = " . DB::_quote($_POST['StockLocation']);
        }
        $sql .= " GROUP BY trans.trans_no ";
      } //end no delivery number selected
      $cols = array(
        _("Delivery #")                                                               => array('fun' => [$this, 'formatTrans']), //
        _("Customer"), //
        _("branch_id")                                                                => 'skip', //
        _("Contact"), //
        _("Address"), //
        _("Reference"), //
        _("Cust Ref"), //
        _("Delivery Date")                                                            => array('type' => 'date', 'ord' => ''), //
        _("Due By")                                                                   => array('type' => 'date'),
        _("Delivery Total")                                                           => array('type' => 'amount', 'ord' => ''), //
        _("Currency")                                                                 => array('align' => 'center'),
        Forms::submit(Orders::BATCH_INVOICE, _("Batch"), false, _("Batch Invoicing")) => array(
          'insert' => true,
          'fun'    => [$this, 'formatBatch'],
          'align'  => 'center'
        ), //
        array('insert' => true, 'fun' => [$this, 'formatDropDown'])
      );
      if (isset($_SESSION['Batch'])) {
        foreach ($_SESSION['Batch'] as $trans => $del) {
          unset($_SESSION['Batch'][$trans]);
        }
        unset($_SESSION['Batch']);
      }
      $table = \ADV\App\Pager\Pager::newPager('sales_del_tbl', $cols);
      $table->setData($sql);
      $table->rowFunction = [$this, 'formatMarker'];
      Event::warning(_("Marked items are overdue."), false);
      $table->display($table);
    }
    /**
     * @param $row
     *
     * @return bool
     */
    public function formatMarker($row) {
      if (Dates::_isGreaterThan(Dates::_today(), Dates::_sqlToDate($row["due_date"])) && $row["Outstanding"] != 0) {
        return "class='overduebg'";
      }
    }
    /**
     * @param $row
     *
     * @return string
     */
    public function formatBatch($row) {
      $name = "Sel_" . $row['trans_no'];
      return $row['Done'] ? '' : "<input type='checkbox' name='$name' value='1' >" // add also trans_no => branch code for checking after 'Batch' submit
        . "<input name='Sel_[" . $row['trans_no'] . "]' type='hidden' value='" . $row['branch_id'] . "'>\n";
    }
    /**
     * @param $row
     *
     * @return null|string
     */
    public function formatTrans($row) {
      return Debtor::viewTrans(ST_CUSTDELIVERY, $row['trans_no']);
    }
    /**
     * @return array
     */
    protected function batchInvoice() { // checking batch integrity
      $del_branch = null;
      $del_count  = 0;
      $selected   = [];
      foreach ($_POST['Sel_'] as $delivery => $branch) {
        $checkbox = 'Sel_' . $delivery;
        if ($this->Input->hasPost($checkbox)) {
          if (!$del_count) {
            $del_branch = $branch;
          } else {
            if ($del_branch != $branch) {
              $del_count = 0;
              break;
            }
          }
          $selected[] = $delivery;
          $del_count++;
        }
      }
      if (!$del_count) {
        Event::error(_('For batch invoicing you should select at least one delivery. All items must be dispatched to the same customer branch.'));
      } else {
        $_SESSION['DeliveryBatch'] = $selected;
        Display::meta_forward('/sales/customer_invoice.php', 'BatchInvoice=Yes');
      }
      return $selected;
    }
    /**
     * @param $row
     *
     * @return string
     */
    public function formatDropDown($row) {
      $dd = new DropDown();
      if ($row["Outstanding"] > 0) {
        $dd->addItem('Edit', '/sales/customer_delivery.php?ModifyDelivery=' . $row['trans_no']);
      } elseif ($row["Outstanding"] > 0) {
        $dd->addItem('Invoice', '/sales/customer_invoice.php?DeliveryNumber=' . $row['trans_no']);
      }
      $href = Reporting::print_doc_link($row['trans_no'], _("Print"), true, ST_CUSTDELIVERY, ICON_PRINT, '', '', 0, 0, true);
      $dd->addItem('Print', $href, [], ['class' => 'printlink']);
      if ($this->User->hasAccess(SA_VOIDTRANSACTION)) {
        $href = '/system/void_transaction?type=' . ST_CUSTDELIVERY . '&trans_no=' . $row['trans_no'] . '&memo=Deleted%20during%20order%20search';
        $dd->addItem('Void Trans', $href, [], ['target' => '_blank']);
      }
      return $dd->setAuto(true)->setSplit(true)->render(true);
    }
  }

