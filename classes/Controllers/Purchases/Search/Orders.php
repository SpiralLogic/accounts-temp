<?php
  namespace ADV\Controllers\Purchases\Search;

  use ADV\App\Forms;
  use ADV\App\Display;
  use ADV\Core\Event;
  use GL_UI;
  use ADV\App\Pager\Pager;
  use Inv_Location;
  use ADV\App\Dates;
  use ADV\Core\Input\Input;
  use ADV\App\Reporting;
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
  class Orders extends \ADV\App\Controller\Action
  {
    /** @var Dates */
    protected $Dates;
    protected $order_no;
    protected $creditor_id;
    protected $selected_stock_item;
    protected $stock_location;
    protected $security = SA_SUPPTRANSVIEW;
    /**

     */
    public function run($embed = false) {
      $this->Dates = Dates::i();
      parent::run();
    }
    protected function before() {
      $this->JS->openWindow(950, 500);
      $this->order_no            =& $this->Input->getPost('order_number', Input::NUMERIC);
      $this->creditor_id         = $this->Input->getPost('creditor_id', Input::NUMERIC, 0);
      $this->stock_location      = $this->Input->getPost('StockLocation', Input::STRING, '');
      $this->selected_stock_item = $this->Input->getPost('SelectStockFromList', Input::STRING, '');
      if ($this->Input->post('SearchOrders')) {
        $this->Ajax->activate('orders_tbl');
      }
      if ($this->order_no) {
        $this->Ajax->addFocus(true, 'order_number');
      }
      $this->Ajax->activate('orders_tbl');
      $this->setTitle("Search Outstanding Purchase Orders");
    }
    protected function index() {
      Forms::start();
      Table::start('noborder');
      echo '<tr>';
      Creditor::cells(_(""), 'creditor_id', $this->Input->post('creditor_id'), true);
      Forms::refCells(_("#:"), 'order_number');
      Forms::dateCells(_("From:"), 'OrdersAfterDate', '', null, -30);
      Forms::dateCells(_("To:"), 'OrdersToDate');
      Inv_Location::cells(_("Location:"), 'StockLocation', null, true);
      //Item::cells(_("Item:"), 'SelectStockFromList', null, true,false,false,false,true);
      Forms::submitCells('SearchOrders', _("Search"), '', _('Select documents'), 'default');
      echo '</tr>';
      Table::end();
      $this->makeTable();
      Creditor::addInfoDialog('.pagerclick');
      Forms::end();
    }
    protected function makeTable() { //figure out the sql required from the inputs available
      $sql
        = "SELECT
 porder.order_no,
 porder.reference,
 supplier.name,
 supplier.creditor_id as id,
 location.location_name,
 porder.requisition_no,
 porder.ord_date,
 supplier.curr_code,
 Sum(line.unit_price*line.quantity_ordered) AS OrderValue,
 Sum(line.delivery_date < '" . $this->Dates->today(true) . "'
 AND (line.quantity_ordered > line.quantity_received)) As OverDue
 FROM purch_orders as porder, purch_order_details as line, suppliers as supplier, locations as location
 WHERE porder.order_no = line.order_no
 AND porder.creditor_id = supplier.creditor_id
 AND location.loc_code = porder.into_stock_location
 AND (line.quantity_ordered > line.quantity_received) ";
      if ($this->creditor_id) {
        $sql .= " AND supplier.creditor_id = " . static::$DB->quote($this->creditor_id);
      }
      if ($this->order_no) {
        $sql .= " AND (porder.order_no LIKE " . static::$DB->quote('%' . $this->order_no . '%');
        $sql .= " OR porder.reference LIKE " . static::$DB->quote('%' . $this->order_no . '%') . ') ';
      } else {
        $data_after  = $this->Dates->dateToSql($_POST['OrdersAfterDate']);
        $data_before = $this->Dates->dateToSql($_POST['OrdersToDate']);
        $sql .= " AND porder.ord_date >= '$data_after'";
        $sql .= " AND porder.ord_date <= '$data_before'";
        if ($this->stock_location) {
          $sql .= " AND porder.into_stock_location = " . static::$DB->quote($this->stock_location);
        }
        if ($this->selected_stock_item) {
          $sql .= " AND line.item_code=" . static::$DB->quote($this->selected_stock_item);
        }
      } //end not order number selected
      $sql .= " GROUP BY porder.order_no";
      static::$DB->query($sql, "No orders were returned");
      /*show a table of the orders returned by the sql */
      $cols = array(
        _("#")           => ['fun' => [$this, 'formatTrans'], 'ord' => ''], //
        _("Reference"), //
        _("Supplier")    => ['ord' => '', 'type' => 'id'], //
        _("Supplier ID") => 'skip', //
        _("Location"), //
        _("Supplier's Reference"), //
        _("Order Date")  => ['name' => 'ord_date', 'type' => 'date', 'ord' => 'desc'], //
        _("Currency")    => ['align' => 'center'], //
        _("Order Total") => 'amount', //
        ['insert' => true, 'fun' => [$this, 'formatEditBtn']], //
        ['insert' => true, 'fun' => [$this, 'formatPrintBtn']], //
        ['insert' => true, 'fun' => [$this, 'formatProcessBtn']]
        //
      );
      if (!$this->stock_location) {
        $cols[_("Location")] = 'skip';
      }
      $table = \ADV\App\Pager\Pager::newPager('purch_orders_tbl', $cols);
      $table->setData($sql);
      Event::warning(_("Marked orders have overdue items."), false);
      $table->rowFunction = [$this, 'formatMarker'];
      $table->width       = "85%";
      $table->display($table);
    }
    /**
     * @param $row
     *
     * @return callable
     */
    public function formatMarker($row) {
      $mark = $row['OverDue'] == 1;
      if ($mark) {
        return "class='overduebg'";
      }
      return '';
    }
    /**
     * @param $row
     *
     * @return callable
     */
    public function formatProcessBtn($row) {
      return Display::link_button(_("Receive"), "/purchases/po_receive_items.php?PONumber=" . $row["order_no"], ICON_RECEIVE);
    }
    /**
     * @param $row
     *
     * @return callable
     */
    public function formatPrintBtn($row) {
      return Reporting::print_doc_link($row['order_no'], _("Print"), true, ST_PURCHORDER, ICON_PRINT, 'button printlink');
    }
    /**
     * @param $row
     *
     * @return callable
     */
    public function formatEditBtn($row) {
      return Display::link_button(_("Edit"), "/purchases/order?ModifyOrder=" . $row["order_no"], ICON_EDIT);
    }
    /**
     * @param $row
     *
     * @return callable
     */
    public function formatTrans($row) {
      return GL_UI::viewTrans(ST_PURCHORDER, $row["order_no"]);
    }
  }

