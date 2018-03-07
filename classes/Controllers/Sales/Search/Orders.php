<?php
  namespace ADV\Controllers\Sales\Search;

  use ADV\App\Debtor\Debtor;
  use ADV\App\Form\DropDown;
  use ADV\App\Display;
  use ADV\Core\Event;
  use ADV\App\Pager\Pager;
  use ADV\Core\Arr;
  use ADV\App\Dates;
  use Inv_Location;
  use ADV\App\Reporting;
  use ADV\App\Forms;
  use ADV\App\UI;
  use ADV\Core\Table;
  use ADV\Core\DB\DB;
  use ADV\Core\Input\Input;
  use ADV\App\Item\Item;

  /** **/
  class Orders extends \ADV\App\Controller\Action
  {
    protected $security;
    protected $trans_type;
    protected $debtor_id;
    protected $stock_id;
    protected $searchArray = [];
    const BATCH_INVOICE     = 'BatchInvoice';
    const SEARCH_ORDER      = 'o';
    const SEARCH_QUOTE      = 'q';
    const MODE_OUTSTANDING  = 'OutstandingOnly';
    const MODE_INVTEMPLATES = 'InvoiceTemplates';
    const MODE_DELTEMPLATES = 'DeliveryTemplates';
    const MODE_QUOTES       = "Quotations";
    protected function before() {
      $this->setSecurity();
      // then check session value
      $this->JS->openWindow(950, 600);
      if (!empty($_REQUEST['q'])) {
        $this->searchArray = explode(' ', $_REQUEST['q']);
      }
      if ($this->searchArray && $this->searchArray[0] == self::SEARCH_ORDER) {
        $this->trans_type = ST_SALESORDER;
      } elseif ($this->searchArray && $this->searchArray[0] == self::SEARCH_QUOTE) {
        $this->trans_type = ST_SALESQUOTE;
      } elseif ($this->searchArray) {
        $this->trans_type = ST_SALESORDER;
      } elseif ($this->Input->post('type')) {
        $this->trans_type = $_POST['type'];
      } elseif ($this->Input->get('type') == ST_SALESQUOTE) {
        $this->trans_type = ST_SALESQUOTE;
      } else {
        $this->trans_type = ST_SALESORDER;
      }
      if ($this->trans_type == ST_SALESORDER) {
        if ($this->Input->get(self::MODE_OUTSTANDING)) {
          $_POST['order_view_mode'] = self::MODE_OUTSTANDING;
          $this->setTitle("Search Outstanding Sales Orders");
        } elseif ($this->Input->get(self::MODE_INVTEMPLATES)) {
          $_POST['order_view_mode'] = self::MODE_INVTEMPLATES;
          $this->setTitle("Search Template for Invoicing");
        } elseif ($this->Input->get(self::MODE_DELTEMPLATES)) {
          $_POST['order_view_mode'] = self::MODE_DELTEMPLATES;
          $this->setTitle("Select Template for Delivery");
        } elseif (!isset($_POST['order_view_mode'])) {
          $_POST['order_view_mode'] = false;
          $this->setTitle("Search All Sales Orders");
        }
      } else {
        $_POST['order_view_mode'] = self::MODE_QUOTES;
        $this->setTitle("Search All Sales Quotations");
      }
      $this->debtor_id = $this->Input->getPost('debtor_id', Input::NUMERIC);
      if (isset($_POST['SelectStockFromList']) && ($_POST['SelectStockFromList'] != "") && ($_POST['SelectStockFromList'] != ALL_TEXT)
      ) {
        $this->stock_id = $_POST['SelectStockFromList'];
      }
      $id = Forms::findPostPrefix('_chgtpl');
      if ($id != -1) {
        $sql = "UPDATE sales_orders SET type = !type WHERE order_no=$id";
        DB::_query($sql, "Can't change sales order type");
        $this->Ajax->activate('orders_tbl');
      }
      if (isset($_POST['Update']) && isset($_POST['last'])) {
        foreach ($_POST['last'] as $id => $value) {
          if ($value != $this->Input->hasPost('chgtpl' . $id)) {
            $sql = "UPDATE sales_orders SET type = !type WHERE order_no=$id";
            DB::_query($sql, "Can't change sales order type");
            $this->Ajax->activate('orders_tbl');
          }
        }
      }
      //	Order range form
      //
      if ($this->Input->post('_OrderNumber_changed')) { // enable/disable selection controls
        $disable = $this->Input->post('OrderNumber') !== '';
        if ($_POST['order_view_mode'] != self::MODE_DELTEMPLATES && $_POST['order_view_mode'] != self::MODE_INVTEMPLATES) {
          $this->Ajax->addDisable(true, 'OrdersAfterDate', $disable);
          $this->Ajax->addDisable(true, 'OrdersToDate', $disable);
        }
        $this->Ajax->addDisable(true, 'StockLocation', $disable);
        $this->Ajax->addDisable(true, '_SelectStockFromList_edit', $disable);
        $this->Ajax->addDisable(true, 'SelectStockFromList', $disable);
        if ($disable) {
          $this->Ajax->addFocus(true, 'OrderNumber');
        } else {
          $this->Ajax->addFocus(true, 'OrdersAfterDate');
        }
        $this->Ajax->activate('orders_tbl');
      }
    }
    protected function setSecurity() {
      if ($this->Input->get(self::MODE_OUTSTANDING) || $this->Input->post('order_view_mode') == self::MODE_OUTSTANDING) {
        $this->security = SA_SALESDELIVERY;
      } elseif ($this->Input->get(self::MODE_INVTEMPLATES) || $this->Input->post('order_view_mode') == self::MODE_INVTEMPLATES) {
        $this->security = SA_SALESINVOICE;
      } else {
        $this->security = SA_SALESAREA;
      }
    }
    protected function index() {
      Forms::start();
      Table::start('noborder');
      echo '<tr>';
      Debtor::newselect(null, ['label' => false, 'row' => false]);
      Forms::refCellsSearch(null, 'OrderNumber', '', null, '', true);
      if ($_POST['order_view_mode'] != self::MODE_DELTEMPLATES && $_POST['order_view_mode'] != self::MODE_INVTEMPLATES) {
        Forms::dateCells(_("From:"), 'OrdersAfterDate', '', null, -30);
        Forms::dateCells(_("To:"), 'OrdersToDate', '', null, 1);
      }
      Inv_Location::cells(_(""), 'StockLocation', null, true);
      Item::select(
        'SelectStockFromList', null, true, false, array(
                                                       'submitonselect' => false,
                                                       'cells'          => true,
                                                       'size'           => 10,
                                                       'purchase'       => false,
                                                       'show_inactive'  => true,
                                                       'placeholder'    => 'Item',
                                                  )
      );
      if ($this->trans_type == ST_SALESQUOTE) {
        Forms::checkCells(_("Show All:"), 'show_all');
      }
      Forms::submitCells('SearchOrders', _("Search"), '', _('Select documents'), 'default');
      echo '</tr>';
      Table::end(1);
      Forms::hidden('order_view_mode');
      Forms::hidden('type', $this->trans_type);
      $this->displayTable();
      UI::emailDialogue(CT_CUSTOMER);
      Forms::submitCenter('Update', _("Update"), true, '');
      Forms::end();
    }
    protected function displayTable() { //	Orders inquiry table
      //
      $sql
        = "SELECT
 		sorder.trans_type,
 		sorder.order_no,
 		sorder.reference," . ($_POST['order_view_mode'] == self::MODE_INVTEMPLATES || $_POST['order_view_mode'] == self::MODE_DELTEMPLATES ? "sorder.comments, " :
        "sorder.customer_ref, ") . "
 		sorder.ord_date,
 		sorder.delivery_date,
 		debtor.name,
 		debtor.debtor_id,
 		branch.br_name,
 		sorder.deliver_to,
 		Sum(line.unit_price*line.quantity*(1-line.discount_percent))+freight_cost AS OrderValue,
 		sorder.type,
 		debtor.curr_code,
 		Sum(line.qty_sent) AS TotDelivered,
 		Sum(line.quantity) AS TotQuantity
 	FROM sales_orders as sorder, sales_order_details as line, debtors as debtor, branches as branch
 		WHERE sorder.order_no = line.order_no
 		AND sorder.trans_type = line.trans_type";
      if ($this->searchArray[0] == self::SEARCH_ORDER) {
        $sql .= " AND sorder.trans_type = " . ST_SALESORDER . " ";
      } elseif ($this->searchArray[0] == self::SEARCH_QUOTE) {
        $sql .= " AND sorder.trans_type = " . ST_SALESQUOTE . " ";
      } elseif ($this->searchArray) {
        $sql .= " AND ( sorder.trans_type = " . ST_SALESORDER . " OR sorder.trans_type = " . ST_SALESQUOTE . ") ";
      } else {
        $sql .= " AND sorder.trans_type = " . DB::_quote($this->trans_type);
      }
      $sql
        .= " AND sorder.debtor_id = debtor.debtor_id
 		AND sorder.branch_id = branch.branch_id
 		AND debtor.debtor_id = branch.debtor_id";
      if ($this->debtor_id > 0) {
        $sql .= " AND sorder.debtor_id = " . DB::_quote($this->debtor_id);
      } elseif (REQUEST_AJAX && isset($this->searchArray) && !empty($_POST['q'])) {
        foreach ($this->searchArray as $quicksearch) {
          if (empty($quicksearch)) {
            continue;
          }
          $quicksearch = DB::_quoteWild($quicksearch);
          $sql
            .= " AND ( debtor.debtor_id = $quicksearch
          OR debtor.name LIKE $quicksearch
          OR sorder.order_no LIKE $quicksearch
          OR sorder.reference LIKE $quicksearch
          OR sorder.contact_name LIKE $quicksearch
          OR sorder.customer_ref LIKE $quicksearch
          OR branch.br_name LIKE $quicksearch)";
        }
        $sql
          .= " GROUP BY sorder.ord_date,
 				 sorder.order_no,
 				sorder.debtor_id,
 				sorder.branch_id,
 				sorder.customer_ref,
 				sorder.deliver_to";
      } else { // ... or select inquiry constraints
        if ($_POST['order_view_mode'] != self::MODE_DELTEMPLATES && $_POST['order_view_mode'] != self::MODE_INVTEMPLATES && !isset($_POST['q'])
        ) {
          $date_after  = Dates::_dateToSql($_POST['OrdersAfterDate']);
          $date_before = Dates::_dateToSql($_POST['OrdersToDate']);
          $sql .= " AND sorder.ord_date >= '$date_after' AND sorder.ord_date <= '$date_before'";
        }
        if ($this->trans_type == ST_SALESQUOTE && !$this->Input->hasPost('show_all')) {
          $sql .= " AND sorder.delivery_date >= '" . Dates::_today(true) . "'";
        }
        if ($this->debtor_id > 0) {
          $sql .= " AND sorder.debtor_id=" . DB::_quote($this->debtor_id);
        }
        if ($this->stock_id) {
          $sql .= " AND line.stk_code=" . DB::_quote($this->stock_id);
        }
        if (isset($_POST['StockLocation']) && $_POST['StockLocation'] != ALL_TEXT) {
          $sql .= " AND sorder.from_stk_loc = " . DB::_quote($_POST['StockLocation']);
        }
        if ($_POST['order_view_mode'] == self::MODE_OUTSTANDING) {
          $sql .= " AND line.qty_sent < line.quantity";
        } elseif ($_POST['order_view_mode'] == self::MODE_INVTEMPLATES || $_POST['order_view_mode'] == self::MODE_DELTEMPLATES
        ) {
          $sql .= " AND sorder.type=1";
        }
        $sql
          .= " GROUP BY sorder.ord_date,
 sorder.order_no,
 				sorder.debtor_id,
 				sorder.branch_id,
 				sorder.customer_ref,
 				sorder.deliver_to";
      }
      $ord = null;
      if ($this->trans_type == ST_SALESORDER) {
        $cols = array(
          array('type' => 'skip'),
          _("Order #")  => array('fun' => [$this, 'formatRef'], 'ord' => ''), //
          _("Ref")      => array('ord' => ''), //
          _("PO#")      => array('ord' => ''), //
          _("Date")     => array('type' => 'date', 'ord' => 'desc'), //
          _("Required") => array('type' => 'date', 'ord' => ''), //
          _("Customer") => array('ord' => 'asc'), //
          array('type' => 'skip'),
          _("Branch")   => array('ord' => ''), //
          _("Address"),
          _("Total")    => array('type' => 'amount', 'ord' => ''),
        );
      } else {
        $cols = array(
          array('type' => 'skip'), //
          _("Quote #")     => array('fun' => [$this, 'formatRef'], 'ord' => ''), //
          _("Ref")         => array('ord' => ''), //
          _("PO#")         => array('type' => 'skip'), //
          _("Date")        => array('type' => 'date', 'ord' => 'desc'), //
          _("Valid until") => array('type' => 'date', 'ord' => ''), //
          _("Customer")    => array('ord' => 'asc'),
          array('type' => 'skip'), //
          _("Branch")      => array('ord' => ''), //
          _("Delivery To"), //
          _("Total")       => array('type' => 'amount', 'ord' => ''), //
        );
      }
      if ($_POST['order_view_mode'] == self::MODE_INVTEMPLATES) {
        Arr::substitute($cols, 3, 1, _("Description"));
        Arr::append($cols, array(array('insert' => true, 'fun' => [$this, 'formatInvoiceBtn'])));
      } else {
        if ($_POST['order_view_mode'] == self::MODE_DELTEMPLATES) {
          Arr::substitute($cols, 3, 1, _("Description"));
          Arr::append($cols, array(array('insert' => true, 'fun' => [$this, 'formatDeliveryBtn2'])));
        }
      }
      Arr::append($cols, [['insert' => true, 'fun' => [$this, 'formatDropdown']]]);
      if (REQUEST_GET) {
        Pager::kill('orders_tbl');
      }
      $table = \ADV\App\Pager\Pager::newPager('sales_order_tbl', $cols);
      $table->setData($sql);
      $table->rowFunction = [$this, 'formatMarker'];
      $table->width       = "90%";
      Event::warning(_("Marked items are overdue."), false);
      $table->display($table);
    }
    /**
     * @param $row
     *
     * @return string
     */
    function formatMarker($row) {
      if ($this->trans_type == ST_SALESQUOTE) {
        $mark = (Dates::_isGreaterThan(Dates::_today(), Dates::_sqlToDate($row['delivery_date'])));
      } else {
        $mark = ($row['type'] == 0 && Dates::_sqlToDate($row['delivery_date']) && Dates::_isGreaterThan(
          Dates::_today(), Dates::_sqlToDate($row['delivery_date'])
        ) && ($row['TotDelivered'] < $row['TotQuantity']));
      }
      if ($mark) {
        return "class='overduebg'";
      }
      return '';
    }
    /**
     * @param $row
     * @param $order_no
     *
     * @return null|string
     */
    function formatRef($row, $order_no) {
      return Debtor::viewTrans($row['trans_type'], $order_no);
    }
    /**
     * @param                        $row
     * @param \ADV\App\Form\DropDown $dd
     *
     * @return string
     */
    function formatDeliveryBtn($row, DropDown $dd) {
      if ($row['trans_type'] == ST_SALESORDER) {
        $dd->addItem('Dispatch', '/sales/customer_delivery.php?OrderNumber=' . $row['order_no']);
      }
      $dd->addItem('Sales Order', '/sales/order?OrderNumber=' . $row['order_no']);
    }
    /**
     * @param                        $row
     * @param \ADV\App\Form\DropDown $dd
     *
     * @return string
     */
    function formatInvoiceTemplateBtn($row, DropDown $dd) {
      if ($row['trans_type'] == ST_SALESORDER) {
        $dd->addItem('Invoice', '/sales/order?NewInvoice=' . $row['order_no']);
      }
    }
    /**
     * @param                        $row
     * @param \ADV\App\Form\DropDown $dd
     *
     * @return string
     */
    function formatDeliveryTemplateBtn($row, DropDown $dd) {
      $dd->addItem('Delivery', '/sales/order?NewDelivery=' . $row['order_no']);
    }
    /**
     * @param $row
     *
     * @return string
     */
    function formatOrderBtn($row) {
      $name  = "chgtpl" . $row['order_no'];
      $value = $row['type'] ? 1 : 0;
      // save also in hidden field for testing during 'Update'
      return Forms::checkbox(null, $name, $value, true, _('Set this order as a template for direct deliveries/invoices')) . Forms::hidden(
        'last[' . $row
        ['order_no'] . ']', $value, false
      );
    }
    /**
     * @param $row
     *
     * @return string
     */
    function formatEditBtn($row) {
      /** @noinspection PhpUndefinedConstantInspection */
      return Display::link_button(_("Edit"), "/sales/order?update=" . $row['order_no'] . "&type=" . $row['trans_type'], ICON_EDIT);
    }
    /**
     * @param $row
     *
     * @return \ADV\Core\HTML|string
     */
    function formatEmailBtn($row) {
      return Reporting::emailDialogue($row['debtor_id'], $row['trans_type'], $row['order_no']);
    }
    /**
     * @param $row
     *
     * @return string
     */
    function formatPrintBtn($row) {
      return Reporting::print_doc_link($row['order_no'], _("Print"), true, $row['trans_type'], ICON_PRINT, 'button printlink');
    }
    /**
     * @param $row
     *
     * @return string
     */
    function formatDropdown($row) {
      $dd = new DropDown();
      switch ($_POST['order_view_mode']) {
        case self::MODE_OUTSTANDING:
          $items[] = $this->formatDeliveryBtn($row, $dd);
          break;
        case self::MODE_INVTEMPLATES:
          $items[] = $this->formatInvoiceTemplateBtn($row, $dd);
          break;
        case self::MODE_DELTEMPLATES:
          $items[] = $this->formatDeliveryTemplateBtn($row, $dd);
          break;
        default:
          $dd->addItem('Edit', '/sales/order?update=' . $row['order_no'] . "&type=" . $row['trans_type']);
          if ($row['trans_type'] == ST_SALESQUOTE) {
            $dd->addItem('Create Order', '/sales/order?QuoteToOrder=' . $row['order_no']);
          }
          $dd->addItem('Email', '#', ['emailid' => $row['debtor_id'] . '-' . $row['trans_type'] . '-' . $row['order_no']], ['class' => 'email-button']);
          $href = Reporting::print_doc_link(
            $row['order_no'], _("Proforma"), true, ($row['trans_type'] == ST_SALESORDER ? ST_PROFORMA : ST_PROFORMAQ), ICON_PRINT, 'button printlink', '', 0, 0, true
          );
          $dd->addItem('Print Proforma', $href, [], ['class' => 'printlink']);
          $href = Reporting::print_doc_link($row['order_no'], _("Print"), true, $row['trans_type'], ICON_PRINT, 'button printlink', '', 0, 0, true);
          $dd->addItem('Print', $href, [], ['class' => 'printlink']);
      }
      if ($this->User->hasAccess(SA_VOIDTRANSACTION)) {
        $href = '/system/void_transaction?type=' . $row['trans_type'] . '&trans_no=' . $row['order_no'] . '&memo=Deleted%20during%20order%20search';
        $dd->addItem('Void Trans', $href, [], [ 'target' => '_blank']);
      }
      return $dd->setAuto(true)->setSplit(true)->render(true);
    }
  }


