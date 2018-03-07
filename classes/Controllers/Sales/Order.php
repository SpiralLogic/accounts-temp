<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Controllers\Sales;

  use ADV\App\Controller\Action;
  use ADV\App\Dimensions;
  use ADV\App\Form\Button;
  use ADV\App\Tax\Tax;
  use ADV\Core\Cell;
  use ADV\Core\HTML;
  use Bank_Currency;
  use Debtor_Branch;
  use Debtor_Payment;
  use GL_ExchangeRate;
  use Item_Price;
  use Modules\Jobsboard\Jobsboard;
  use Sales_Point;
  use Sales_Order;
  use DB_Company;
  use ADV\Core\Session;
  use GL_UI;
  use Item_Line;
  use Sales_Branch;
  use ADV\App\Ref;
  use ADV\App\Validation;
  use ADV\App\Reporting;
  use ADV\App\Display;
  use ADV\App\Forms;
  use ADV\App\SysTypes;
  use ADV\App\Orders;
  use ADV\App\Form\Form;
  use ADV\Core\Table;
  use ADV\App\Item\Item;
  use ADV\App\UI;
  use ADV\App\Debtor\Debtor;
  use ADV\Core\View;
  use ADV\Core\Errors;
  use ADV\Core\Num;
  use ADV\App\Dates;
  use ADV\Core\Event;
  use ADV\Core\Input\Input;
  use Sales_Type;
  use Sales_UI;

  /** **/
  class Order extends Action
  {
    protected $addTitles
      = [
        ST_SALESQUOTE   => "New Sales Quotation Entry", //
        ST_SALESINVOICE => "Direct Sales Invoice", //
        ST_CUSTDELIVERY => "Direct Sales Delivery", //
        ST_SALESORDER   => "New Sales Order Entry"
      ];
    protected $modifyTitles
      = [
        ST_SALESQUOTE => "Modifying Sales Quotation # ", //
        ST_SALESORDER => "Modifying Sales Order # "
      ];
    protected $typeSecurity
      = [
        ST_SALESORDER          => SA_SALESORDER, //
        ST_SALESQUOTE          => SA_SALESQUOTE, ///
        ST_CUSTDELIVERY        => SA_SALESDELIVERY, //
        Orders::QUOTE_TO_ORDER => SA_SALESORDER, //
        Orders::CLONE_ORDER    => SA_SALESORDER, //
        ST_SALESINVOICE        => SA_SALESINVOICE
      ];
    protected $security = SA_SALESORDER;
    public $type;
    /***
     * @var \Sales_Order;
     */
    public $order;
    protected function before() {
      $this->order = Orders::session_get() ? : null;
      $this->JS->openWindow(900, 500);
      if (REQUEST_GET) {
        if ($this->Input->get('debtor_id', Input::NUMERIC)) {
          $this->action       = Orders::CANCEL_CHANGES;
          $_POST['debtor_id'] = $_GET['debtor_id'];
          $this->Ajax->activate('debtor_id');
        }
        $this->type = $this->Input->get('type', Input::NUMERIC, ST_SALESORDER);
        $type_id    = 0;
        $this->setTitle("New Sales Order Entry");
        if ($this->Input->get(Orders::ADD, Input::NUMERIC, false) !== false) {
          $this->setTitle($this->addTitles[$this->type]);
        } elseif ($this->Input->get(Orders::UPDATE, Input::NUMERIC, -1) > 0) {
          $this->setTitle($this->modifyTitles[$this->type] . $_GET[Orders::UPDATE]);
          $type_id = $this->Input->get(Orders::UPDATE);
        } elseif ($this->Input->get(Orders::QUOTE_TO_ORDER)) {
          $this->type = Orders::QUOTE_TO_ORDER;
          $this->setTitle("New Order from Quote");
          $type_id = $_GET[Orders::QUOTE_TO_ORDER];
        } elseif ($this->Input->get(Orders::CLONE_ORDER)) {
          $this->type = Orders::CLONE_ORDER;
          $this->setTitle("New order from previous order");
          $type_id = $this->Input->get(Orders::CLONE_ORDER);
        }
        $this->order = $this->createOrder($this->type, $type_id);
      }
      $this->setSecurity();
    }
    protected function index() {
      $this->checkBranch();
      if (isset($_GET[REMOVED])) {
        $this->removed();
      }
      $this->checkRowDelete();
      $this->runAction();
      $this->runValidation();
      $type_name       = SysTypes::$names[$this->order->trans_type];
      $type_name_short = SysTypes::$short_names[$this->order->trans_type];
      $idate           = _("$type_name_short Date:");
      $orderitems      = _("$type_name Items");
      $deleteorder     = _("Delete $type_name_short");
      $corder          = _("Commit $type_name_short Changes");
      $porder          = _("Place $type_name_short");
      Forms::start();
      $this->header($idate);
      Forms::hidden('order_id');
      Table::start('tablesstyle center width90 pad10');
      echo "<tr><td>";
      $edit_line = $this->getActionId(Orders::EDIT_LINE);
      $this->summary($orderitems, $edit_line);
      echo "</td></tr><tr><td>";
      $this->order->display_delivery_details();
      echo "</td></tr>";
      Table::end(1);
      $this->Ajax->start_div('controls', 'items_table');
      $buttons = new Form();
      if ($this->order->trans_no > 0 && $this->User->hasAccess(SA_VOIDTRANSACTION) && !($this->order->trans_type == ST_SALESORDER && $this->order->has_deliveries())) {
        $buttons->submit(Orders::DELETE_ORDER, $deleteorder)->preIcon(ICON_DELETE)->type(Button::DANGER)
                ->setWarning('You are about to void this Document.\nDo you want to continue?');
      }
      $buttons->submit(Orders::CANCEL_CHANGES, _("Cancel Changes"))->preIcon(ICON_CANCEL)->type('warning');
      if (count($this->order->line_items)) {
        $type = ($this->order->trans_no > 0) ? $corder : $porder; //_('Check entered data and save document')
        $buttons->submit(Orders::PROCESS_ORDER, $type)->type(\ADV\App\Form\Button::SUCCESS)->preIcon(ICON_SUBMIT);
      }
      $view = new View('libraries/forms');
      $view->set('buttons', $buttons);
      $view->render();
      $this->Ajax->end_div();
      Forms::end();
      Debtor::addEditDialog();
      Item::addEditDialog();
      UI::emailDialogue(CT_CUSTOMER);
    }
    protected function checkBranch() {
      if (Forms::isListUpdated('branch_id')) {
        // when branch is selected via external editor also customer can change
        $br                 = Sales_Branch::get($this->Input->post('branch_id'));
        $_POST['debtor_id'] = $br['debtor_id'];
        $this->Ajax->activate('debtor_id');
      }
    }
    protected function cancelItem() {
      Item_Line::start_focus('stock_id');
    }
    /**
     * @param $error
     */
    protected function exitError($error) {
      Event::warning($error);
      $this->Session->setGlobal('debtor_id', null);
      $this->Page->endExit();
    }
    protected function Refresh() {
      $this->Ajax->activate('items_table');
    }
    protected function add() {
    }
    protected function after() {
      unset($this->Session['order_no']);
    }
    /**
     * @param      $order_no
     * @param      $trans_type
     * @param bool $edit
     * @param bool $update
     *
     * @return void
     * @internal param string $trans_name
     */
    protected function pageComplete($order_no, $trans_type, $edit = false, $update = false) {
      $edit_trans = '';
      switch ($trans_type) {
        case ST_SALESINVOICE:
          $trans_name = "Invoice";
          break;
        case ST_SALESQUOTE:
          $trans_name = "Quote";
          $edit_trans = "/sales/order?update=$order_no&type=" . $trans_type;
          break;
        case ST_CUSTDELIVERY:
          $trans_name = "Delivery";
          break;
        case ST_SALESORDER:
        default:
          $trans_name = "Order";
          $edit_trans = "/sales/order?update=$order_no&type=" . $trans_type;
      }
      $new_trans = "/sales/order?add=0&type=" . $trans_type;
      $customer  = new Debtor($this->Session->getGlobal('debtor_id', 0));
      Event::success(sprintf(_($trans_name . " # %d has been " . ($update ? "updated!" : "added!")), $order_no));
      GL_UI::viewTrans($trans_type, $order_no, _("&View This " . $trans_name), false, 'menu_option button');
      if ($edit) {
        Display::submenu_option(_("&Edit This " . $trans_name), $edit_trans);
      }
      echo "<br><div class='center'>" . Display::submenu_print(_("&Print This " . $trans_name), $trans_type, $order_no, 'prtopt') . '<br><br>';
      echo Reporting::emailDialogue($customer->id, $trans_type, $order_no) . '<br><br>';
      if ($trans_type == ST_SALESORDER || $trans_type == ST_SALESQUOTE) {
        echo Display::submenu_print(_("Print Proforma Invoice"), ($trans_type == ST_SALESORDER ? ST_PROFORMA : ST_PROFORMAQ), $order_no, 'prtopt'
          ) . '<br><br>';
      }
      echo "</div>";
      if ($trans_type == ST_SALESORDER) {
        Display::submenu_option(_("Create PO from this order"), "/purchases/order?NewOrder=Yes&UseOrder=" . $order_no . "'");
        Display::submenu_option(_("Dropship this order"), "/purchases/order?NewOrder=Yes&UseOrder=" . $order_no . "&DRP=1' ");
        Display::submenu_option(_("Make &Delivery Against This Order"), "/sales/customer_delivery.php?OrderNumber=$order_no");
        Display::submenu_option(_("Show outstanding &Orders"), "/sales/search/orders?OutstandingOnly=1");
        Display::submenu_option(_("Enter a New &Order"), $new_trans);
        Display::submenu_option(_("Select A Different Order to edit"), "/sales/search/orders?type=" . ST_SALESORDER);
      } elseif ($trans_type == ST_SALESQUOTE) {
        Display::submenu_option(_("Make &Sales Order Against This Quotation"), "/sales/order?" . Orders::QUOTE_TO_ORDER . "=$order_no");
        Display::submenu_option(_("Enter a New &Quotation"), $new_trans);
        Display::submenu_option(_("Select A Different &Quotation to edit"), "/sales/search/orders?type=" . ST_SALESQUOTE);
      } elseif ($trans_type == ST_CUSTDELIVERY) {
        Display::submenu_print(_("&Print Delivery Note"), ST_CUSTDELIVERY, $order_no, 'prtopt');
        Display::submenu_print(_("P&rint as Packing Slip"), ST_CUSTDELIVERY, $order_no, 'prtopt', null, 1);
        GL_UI::view(ST_CUSTDELIVERY, $order_no, _("View the GL Journal Entries for this Dispatch"));
        Display::submenu_option(_("Make &Invoice Against This Delivery"), "/sales/customer_invoice.php?DeliveryNumber=$order_no");
        ((isset($_GET['Type']) && $_GET['Type'] == 1)) ? Display::submenu_option(_("Enter a New Template &Delivery"), "/sales/search/orders?DeliveryTemplates=Yes") : Display::submenu_option(_("Enter a &New Delivery"), $new_trans);
      } elseif ($trans_type == ST_SALESINVOICE) {
        GL_UI::view(ST_SALESINVOICE, $order_no, _("View the GL &Journal Entries for this Invoice"));
        if ((isset($_GET['Type']) && $_GET['Type'] == 1)) {
          Display::submenu_option(_("Enter a &New Template Invoice"), "/sales/search/orders?InvoiceTemplates=Yes");
        } else {
          Display::submenu_option(_("Enter a &New Direct Invoice"), $new_trans);
        }
        Display::link_params("/sales/payment", _("Apply a customer payment"));
        if (isset($_GET[ADDED_DI]) && $this->Session->getGlobal('debtor_id')) {
          echo "<div style='text-align:center;'><iframe style='margin:0 auto; border-width:0;' src='" . '/sales/payment' . "?frame=1' width='80%' height='475' scrolling='auto' frameborder='0'></iframe> </div>";
        }
      }
      $this->JS->setFocus('prtopt');
      $this->Ajax->activate('_page_body', $new_trans, $edit_trans, $this->addTitles[$trans_type]);
      //	UploadHandler::insert($order_no);
      $this->Page->endExit();
    }
    /**
     * @internal param \Sales_Order $order
     * @return bool
     */
    protected function canProcess() {
      if (!$this->Input->post('debtor_id')) {
        Event::error(_("There is no customer selected."));
        $this->JS->setFocus('debtor_id');
        return false;
      }
      if (!$this->Input->post('branch_id')) {
        Event::error(_("This customer has no branch defined."));
        $this->JS->setFocus('branch_id');
        return false;
      }
      if (!Dates::_isDate($_POST['OrderDate'])) {
        Event::error(_("The entered date is invalid."));
        $this->JS->setFocus('OrderDate');
        return false;
      }
      if (!$this->order) {
        Event::error(_("You are not currently editing an order! (Using the browser back button after committing an order does not go back to editing)"));
        return false;
      }
      if ($this->order->trans_type != ST_SALESORDER && $this->order->trans_type != ST_SALESQUOTE && !Dates::_isDateInFiscalYear($_POST['OrderDate'])) {
        Event::error(_("The entered date is not in fiscal year"));
        $this->JS->setFocus('OrderDate');
        return false;
      }
      if (count($this->order->line_items) == 0) {
        if (!empty($_POST['stock_id']) && $this->checkItemData()) {
          $this->order->add_line($_POST['stock_id'], Validation::input_num('qty'), Validation::input_num('price'), Validation::input_num('Disc') / 100, $_POST['description']
          );
          $_POST['_stock_id_edit'] = $_POST['stock_id'] = "";
        } else {
          Event::error(_("You must enter at least one non empty item line."));
          Item_Line::start_focus('stock_id');
          return false;
        }
      }
      if ($this->order->trans_type == ST_SALESORDER && $this->order->trans_no == 0 && !empty($_POST['cust_ref']) && $this->order->check_cust_ref($_POST['cust_ref'])
      ) {
        Event::error(_("This customer already has a purchase order with this number."));
        $this->JS->setFocus('cust_ref');
        return false;
      }
      if (strlen($_POST['deliver_to']) <= 1) {
        Event::error(_("You must enter the person or company to whom delivery should be made to."));
        $this->JS->setFocus('deliver_to');
        return false;
      }
      if (strlen($_POST['delivery_address']) <= 1) {
        Event::error(_("You should enter the street address in the box provided. Orders cannot be accepted without a valid street address."));
        $this->JS->setFocus('delivery_address');
        return false;
      }
      if ($_POST['freight_cost'] == "") {
        $_POST['freight_cost'] = Num::_priceFormat(0);
      }
      if (!Validation::post_num('freight_cost', 0)) {
        Event::error(_("The shipping cost entered is expected to be numeric."));
        $this->JS->setFocus('freight_cost');
        return false;
      }
      if (!Dates::_isDate($_POST['delivery_date'])) {
        if ($this->order->trans_type == ST_SALESQUOTE) {
          Event::error(_("The Valid date is invalid."));
        } else {
          Event::error(_("The delivery date is invalid."));
        }
        $this->JS->setFocus('delivery_date');
        return false;
      }
      //if (Dates::_isGreaterThan($this->order->document_date, $_POST['delivery_date'])) {
      if (Dates::_differenceBetween($_POST['delivery_date'], $_POST['OrderDate']) < 0) {
        if ($this->order->trans_type == ST_SALESQUOTE) {
          Event::error(_("The requested valid date is before the date of the quotation."));
        } else {
          Event::error(_("The requested delivery date is before the date of the order."));
        }
        $this->JS->setFocus('delivery_date');
        return false;
      }
      if ($this->order->trans_type == ST_SALESORDER && strlen($_POST['name']) < 1) {
        Event::error(_("You must enter a Person Ordering name."));
        $this->JS->setFocus('name');
        return false;
      }
      $result = $this->order->trans_type == ST_SALESORDER && strlen($_POST['cust_ref']) < 1;
      if ($result && $this->order->order_id !== $this->Session->getFlash('SalesOrder')) {
        $this->Session->setFlash('SalesOrder', $this->order->order_id);
        Event::warning('Are you sure you want to commit this order without a purchase order number?');
        $this->JS->setFocus('cust_ref');
        return false;
      }
      if (!Ref::is_valid($_POST['ref'])) {
        Event::error(_("You must enter a reference."));
        $this->JS->setFocus('ref');
        return false;
      }
      if ($this->order->trans_no == 0 && !Ref::is_new($_POST['ref'], $this->order->trans_type)) {
        $_POST['ref'] = Ref::get_next($this->order->trans_type);
      }
      return true;
    }
    /**
     * @internal param $this ->order
     * @return bool
     */
    protected function checkItemData() {
      if (!$this->User->hasAccess(SA_SALESCREDIT) && (!Validation::post_num('qty', 0) || !Validation::post_num('Disc', 0, 100))) {
        Event::error(_("The item could not be updated because you are attempting to set the quantity ordered to less than 0, or the discount percent to more than 100."));
        $this->JS->setFocus('qty');
        return false;
      } elseif (!Validation::post_num('price', 0)) {
        Event::error(_("Price for item must be entered and can not be less than 0"));
        $this->JS->setFocus('price');
        return false;
      } elseif (!$this->User->hasAccess(SA_SALESCREDIT) && isset($_POST['LineNo']) && isset($this->order->line_items[$_POST['LineNo']]) && !Validation::post_num('qty', $this->order->line_items[$_POST['LineNo']]->qty_done
        )
      ) {
        $this->JS->setFocus('qty');
        Event::error(_("You attempting to make the quantity ordered a quantity less than has already been delivered. The quantity delivered cannot be modified retrospectively.")
        );
        return false;
      } // Joe Hunt added 2008-09-22 -------------------------
      elseif ($this->order->trans_type != ST_SALESORDER && $this->order->trans_type != ST_SALESQUOTE && !DB_Company::_get_pref('allow_negative_stock'
        ) && Item::is_inventory_item($_POST['stock_id'])
      ) {
        $qoh = Item::get_qoh_on_date($_POST['stock_id'], $_POST['location'], $_POST['OrderDate']);
        if (Validation::input_num('qty') > $qoh) {
          $stock = Item::get($_POST['stock_id']);
          Event::error(_("The delivery cannot be processed because there is an insufficient quantity for item:"
            ) . " " . $stock['stock_id'] . " - " . $stock['description'] . " - " . _("Quantity On Hand") . " = " . Num::_format($qoh, Item::qty_dec($_POST['stock_id']))
          );
          return false;
        }
        return true;
      }
      return true;
    }
    /**
     * @param $type
     * @param $trans_no
     *
     * @return \Purch_Order|\Sales_Order
     */
    protected function createOrder($type, $trans_no) {
      if (isset($_GET[Orders::QUOTE_TO_ORDER])) {
        $this->order    = new Sales_Order(ST_SALESQUOTE, [$trans_no]);
        $doc            = clone($this->order);
        $doc->source_no = $trans_no;
        $this->order->finish();
        $doc->convertToOrder();
      } elseif (isset($_GET[Orders::CLONE_ORDER])) {
        $trans_no           = $_GET[Orders::CLONE_ORDER];
        $doc                = new Sales_Order(ST_SALESORDER, [$trans_no]);
        $doc->trans_no      = 0;
        $doc->trans_type    = ST_SALESORDER;
        $doc->reference     = Ref::get_next($doc->trans_type);
        $doc->document_date = $doc->due_date = Dates::_newDocDate();
        foreach ($doc->line_items as $line) {
          $line->qty_done = $line->qty_dispatched = 0;
        }
      } elseif ($type != ST_SALESORDER && $type != ST_SALESQUOTE && $trans_no != 0) { // this is template
        $doc                = new Sales_Order(ST_SALESORDER, [$trans_no]);
        $doc->trans_type    = $type;
        $doc->trans_no      = 0;
        $doc->document_date = Dates::_newDocDate();
        if ($type == ST_SALESINVOICE) {
          $doc->due_date = Sales_Order::get_invoice_duedate($doc->debtor_id, $doc->document_date);
          $doc->pos      = $this->User->pos();
          $pos           = Sales_Point::get($doc->pos);
          $doc->pos      = -1;
        } else {
          $doc->due_date = $doc->document_date;
        }
        $doc->reference = Ref::get_next($doc->trans_type);
        /** @var $line  int */
        foreach ($doc->line_items as $line) {
          $doc->line_items[$line]->qty_done = 0;
        }
      } else {
        $doc = new Sales_Order($type, [$trans_no]);
      }
      $this->type = $type;
      return Sales_Order::copyToPost($doc);
    }
    protected function removed() {
      if ($_GET['type'] == ST_SALESQUOTE) {
        Event::notice(_("This sales quotation has been deleted as requested."), 1);
        Display::submenu_option(_("Enter a New Sales Quotation"), "/sales/order?add=0type=" . ST_SALESQUOTE);
        Display::submenu_option(_("Select A Different &Quotation to edit"), "/sales/search/orders?type=" . ST_SALESQUOTE);
      } else {
        Event::notice(_("This sales order has been deleted as requested."), 1);
        Display::submenu_option(_("Enter a New Sales Order"), "/sales/order?add=0&type=" . $_GET['type']);
        Display::submenu_option(_("Select A Different Order to edit"), "/sales/search/orders.vphp?type=" . ST_SALESORDER);
      }
      $this->Page->endExit();
    }
    /**
     * @return mixed
     */
    protected function processOrder() {
      if (!$this->canProcess($this->order)) {
        return;
      }
      Sales_Order::copyFromPost($this->order);
      $modified   = ($this->order->trans_no != 0);
      $trans_type = $this->order->trans_type;
      Dates::_newDocDate($this->order->document_date);
      $this->Session->setGlobal('debtor_id', $this->order->debtor_id);
      $this->order->write(1);
      $jobsboard_order = clone ($this->order);
      $trans_no        = $jobsboard_order->trans_no = key($this->order->trans_no);
      if (Errors::getSeverity() == -1) { // abort on failure or error messages are lost
        $this->Ajax->activate('_page_body');
        $this->Page->endExit();
      }
      $this->order->finish();
      if ($trans_type == ST_SALESORDER) {
        $jb = new Jobsboard([]);
        $jb->addjob($jobsboard_order);
      }
      $this->pageComplete($trans_no, $trans_type, true, $modified);
    }
    protected function cancelChanges() {
      $type     = $this->order->trans_type;
      $order_no = (is_array($this->order->trans_no)) ? key($this->order->trans_no) : $this->order->trans_no;
      Orders::Session_delete($_POST['order_id']);
      $this->order = $this->createOrder($type, $order_no);
      $this->JS->setfocus('customer');
    }
    /**
     * @return mixed
     */
    protected function deleteOrder() {
      if (!$this->User->hasAccess(SA_VOIDTRANSACTION)) {
        Event::error('You don\'t have access to delete orders');
        return;
      }
      if ($this->order->trans_type == ST_CUSTDELIVERY) {
        Event::notice(_("Direct delivery has been cancelled as requested."), 1);
        Display::submenu_option(_("Enter a New Sales Delivery"), "/sales/order?NewDelivery=1");
      } elseif ($this->order->trans_type == ST_SALESINVOICE) {
        Event::notice(_("Direct invoice has been cancelled as requested."), 1);
        Display::submenu_option(_("Enter a New Sales Invoice"), "/sales/order?NewInvoice=1");
      } else {
        if ($this->order->trans_no != 0) {
          if ($this->order->trans_type == ST_SALESORDER && $this->order->has_deliveries()) {
            Event::error(_("This order cannot be cancelled because some of it has already been invoiced or dispatched. However, the line item quantities may be modified.")
            );
          } else {
            $trans_no   = key($this->order->trans_no);
            $trans_type = $this->order->trans_type;
            $this->order->delete($trans_no, $trans_type);
            if ($trans_type == ST_SALESORDER) {
              $jb = new Jobsboard([]);
              $jb->removejob($trans_no);
              Event::notice(_("Sales order has been cancelled."), 1);
            } else {
              Event::notice(_("Sales quote has been cancelled."), 1);
            }
          }
        } else {
          return;
        }
      }
      $this->Ajax->activate('_page_body');
      $this->order->finish();
      Display::submenu_option(_("Show outstanding &Orders"), "/sales/search/orders?OutstandingOnly=1");
      Display::submenu_option(_("Enter a New &Order"), "/sales/order?add=0&type=" . ST_SALESORDER);
      Display::submenu_option(_("Select A Different Order to edit"), "/sales/search/orders?type=" . ST_SALESORDER);
      $this->Page->endExit();
    }
    protected function updateItem() {
      if ($this->checkItemData($this->order)) {
        $this->order->update_order_item($_POST['LineNo'], Validation::input_num('qty'), Validation::input_num('price'), Validation::input_num('Disc') / 100, $_POST['description']
        );
      }
      Item_Line::start_focus('stock_id');
    }
    protected function discountAll() {
      if (!is_numeric($_POST['_discountAll'])) {
        Event::error(_("Discount must be a number"));
      } elseif ($_POST['_discountAll'] < 0 || $_POST['_discountAll'] > 100) {
        Event::error(_("Discount percentage must be between 0-100"));
      } else {
        $this->order->discount_all($_POST['_discountAll'] / 100);
      }
      $this->Ajax->activate('_page_body');
    }
    /**
     * @return mixed
     */
    protected function addLine() {
      if (!$this->checkItemData($this->order)) {
        return;
      }
      $this->order->add_line($_POST['stock_id'], Validation::input_num('qty'), Validation::input_num('price'), Validation::input_num('Disc') / 100, $_POST['description']);
      $_POST['_stock_id_edit'] = $_POST['stock_id'] = "";
      Item_Line::start_focus('stock_id');
    }
    /**
     * @return mixed
     */
    protected function checkRowDelete() {
      $line_id = $this->getActionID(Orders::DELETE_LINE);
      if ($line_id === -1) {
        return;
      }
      if ($this->order->some_already_delivered($line_id) == 0) {
        $this->order->remove_from_order($line_id);
      } else {
        Event::error(_("This item cannot be deleted because some of it has already been delivered."));
      }
      Item_Line::start_focus('stock_id');
    }
    /**
     * @return bool|mixed|void
     */
    protected function runValidation() {
      if (!is_object($this->order)) {
        $this->exitError('No current order to edit.');
      }
      Validation::check(Validation::STOCK_ITEMS, _("There are no inventory items defined in the system."));
      Validation::check(Validation::BRANCHES_ACTIVE, _("There are no customers, or there are no customers with branches. Please define customers and customer branches."));
    }
    protected function setLineOrder() {
      $line_map = $this->Input->getPost('lineMap', []);
      $this->order->setLineOrder($line_map);
      $data = ['lineMap' => $this->order, 'status' => true];
      $this->JS->renderJSON($data);
    }
    protected function setSecurity() {
      if ($this->order->trans_type) {
        $this->type = $this->order->trans_type;
      }
      // first check is this is not start page call
      $this->security = $this->typeSecurity[$this->type];
      $value          = (!$this->order) ? : $this->order->trans_type;
      // then check Session value
      if (isset($this->typeSecurity[$value])) {
        $this->security = $this->typeSecurity[$value];
      }
      $this->checkSecurity();
    }
    /**
     * @param      $title
     * @param bool $editable_items
     */
    public function summary($title, $editable_items = false) {
      Display::heading($title);
      if (count($this->order->line_items) > 0) {
        $label  = _("Create PO from this order");
        $target = "/purchases/order?NewOrder=Yes&UseOrder=" . $this->order->order_id . "' class='button'";
        $pars   = Display::access_string($label);
        echo "<div class='center'><a target='_blank' href='$target' $pars[1]>$pars[0]</a></div>";
        $label  = _("Dropship this order");
        $target = "/purchases/order?NewOrder=Yes&UseOrder=" . $this->order->order_id . "&DRP=1' class='button   '";
        $pars   = Display::access_string($label);
        echo "<div class='center'><a target='_blank' href='$target' $pars[1]>$pars[0]</a></div>";
      }
      $this->Ajax->start_div('items_table');
      Table::start('padded width90 grid');
      $th = array(
        _("Item Code"),
        _("Item Description"),
        _("Quantity"),
        _("Delivered"),
        _("Unit"),
        _("Price"),
        _("Discount %"),
        _("Total"),
        "",
        ""
      );
      if ($this->order->trans_no == 0) {
        unset($th[3]);
      }
      if (count($this->order->line_items)) {
        $th[] = '';
      }
      Table::header($th);
      $total_discount = $total = 0;
      $id             = $editable_items;
      $editable_items = ($editable_items === false) ? false : true;
      $has_marked     = false;
      foreach ($this->order->line_items as $line_no => $stock_item) {
        $line_total    = round($stock_item->qty_dispatched * $stock_item->price * (1 - $stock_item->discount_percent), $this->User->price_dec());
        $line_discount = round($stock_item->qty_dispatched * $stock_item->price, $this->User->price_dec()) - $line_total;
        $qoh_msg       = '';
        if (!$editable_items || $id != $line_no) {
          $row_class = '';
          if (!DB_Company::_get_pref('allow_negative_stock') && Item::is_inventory_item($stock_item->stock_id)) {
            $qoh = Item::get_qoh_on_date($stock_item->stock_id, $_POST['location'], $_POST['OrderDate']);
            if ($stock_item->qty_dispatched > $qoh) {
              // oops, we don't have enough of one of the component items
              $row_class = "class='stockmankobg'";
              $qoh_msg .= $stock_item->stock_id . " - " . $stock_item->description . ": " . _("Quantity On Hand") . " = " . Num::_format($qoh, Item::qty_dec($stock_item->stock_id)
                ) . '<br>';
              $has_marked = true;
            }
          }
          echo '<tr' . $row_class . 'data-line=' . $line_no . '>';
          Cell::label($stock_item->stock_id, "class='stock pointer' data-stock_id='{$stock_item->stock_id}'");
          //Cell::label($stock_item->description, ' class="nowrap"' );
          Cell::description($stock_item->description);
          $dec = Item::qty_dec($stock_item->stock_id);
          Cell::qty($stock_item->qty_dispatched, false, $dec);
          if ($this->order->trans_no != 0) {
            Cell::qty($stock_item->qty_done, false, $dec);
          }
          Cell::label($stock_item->units);
          Cell::amount($stock_item->price);
          Cell::percent($stock_item->discount_percent * 100);
          Cell::amount($line_total);
          if ($editable_items) {
            Forms::buttonEditCell($line_no, _("Edit"), _('Edit document line'));
            Forms::buttonDeleteCell($line_no, _("Delete"), _('Remove line from document'));
          }
          echo '</tr>';
        } else {
          $this->item_controls($id, $line_no);
        }
        $total += $line_total;
        $total_discount += $line_discount;
      }
      if ($id == -1 && $editable_items) {
        $this->item_controls($id);
        UI::lineSortable();
      }
      $colspan = 6;
      if ($this->order->trans_no != 0) {
        ++$colspan;
      }
      Table::foot();
      echo '<tr>';
      Cell::label(_("Shipping Charge"), "colspan=$colspan class='alignright'");
      Forms::amountCellsSmall(null, 'freight_cost', Num::_priceFormat(Input::_post('freight_cost', null, 0)), null, ['$']);
      Cell::label('', 'colspan=2');
      echo '</tr>';
      $display_sub_total = Num::_priceFormat($total + Validation::input_num('freight_cost'));
      echo '<tr>';
      Cell::label(_("Total Discount"), "colspan=$colspan class='alignright'");
      Forms::amountCellsSmall(null, 'totalDiscount', $total_discount, null, ['$']);
      echo (new HTML)->td(null, array('colspan' => 2, 'class' => 'center'))->button('discountAll', 'Discount All', array('name' => FORM_ACTION, 'value' => 'discountAll'), false
      );
      Forms::hidden('_discountAll', '0', true);
      echo HTML::td();
      $action = "var discount = prompt('Discount Percent?',''); if (!discount) return false; $(\"[name='_discountAll']\").val(Number(discount));e=$(this);Adv.Forms.saveFocus(e);JsHttpRequest.request(this);return false;";
      $this->JS->addLiveEvent('#discountAll', 'click', $action);
      echo '</tr>';
      Table::label(_("Sub-total"), $display_sub_total, "colspan=$colspan  class='alignright'", "class='alignright'", 2);
      $taxes         = $this->order->get_taxes(Validation::input_num('freight_cost'));
      $tax_total     = Tax::edit_items($taxes, $colspan, $this->order->tax_included, 2);
      $display_total = Num::_priceFormat(($total + Validation::input_num('freight_cost') + $tax_total));
      echo '<tr>';
      Cell::labelled(_("Total"), $display_total, "colspan=$colspan class='alignright'", "class='alignright'");
      Forms::submitCells(FORM_ACTION, Orders::REFRESH, "colspan=2", _("Refresh"), true);
      echo '</tr>';
      Table::footEnd();
      Table::end();
      if ($has_marked) {
        Event::notice(_("Marked items have insufficient quantities in stock as on day of delivery."), 0, 1, "class='stockmankofg'");
      }
      if ($this->order->trans_type != 30 && !DB_Company::_get_pref('allow_negative_stock')) {
        Event::error(_("The delivery cannot be processed because there is an insufficient quantity for item:") . '<br>' . $qoh_msg);
      }
      $this->Ajax->end_div();
    }
    /**
     * @param $id
     * @param $line_no
     *
     * @internal param $rowcounter
     */
    public function item_controls($id, $line_no = -1) {
      if ($line_no != -1 && $line_no == $id) // edit old line
      {
        echo '<tr' . 'class="editline"' . '>';
        $_POST['stock_id']    = $this->order->line_items[$id]->stock_id;
        $unit_dec             = Item::qty_dec($_POST['stock_id']);
        $_POST['qty']         = Num::_format($this->order->line_items[$id]->qty_dispatched, $dec);
        $_POST['price']       = Num::_priceFormat($this->order->line_items[$id]->price);
        $_POST['Disc']        = Num::_percentFormat($this->order->line_items[$id]->discount_percent * 100);
        $_POST['description'] = $this->order->line_items[$id]->description;
        $units                = $this->order->line_items[$id]->units;
        Forms::hidden('stock_id', $_POST['stock_id']);
        Cell::label($_POST['stock_id'], 'class="stock"');
        Forms::textareaCells(null, 'description', null, 50, 5);
        $this->Ajax->activate('items_table');
      } else // prepare new line
      {
        echo '<tr ' . 'class="newline"' . '>';
        Sales_UI::items_cells(null, 'stock_id', null, false, false, array('description' => '', 'sales_type' => $this->order->sales_type));
        if (Forms::isListUpdated('stock_id')) {
          $this->Ajax->activate('price');
          $this->Ajax->activate('description');
          $this->Ajax->activate('units');
          $this->Ajax->activate('qty');
          $this->Ajax->activate('line_total');
        }
        $item_info      = Item::get_edit_info(Input::_post('stock_id'));
        $units          = $item_info["units"];
        $unit_dec       = false;
        $_POST['qty']   = Num::_qtyFormat(1, $unit_dec);
        $price          = Item_Price::get_kit(Input::_post('stock_id'), $this->order->customer_currency, $this->order->sales_type, $this->order->price_factor, Input::_post('OrderDate'));
        $_POST['price'] = Num::_priceFormat($price);
        $_POST['Disc']  = Num::_percentFormat($this->order->default_discount * 100);
      }
      Forms::qtyCells(null, 'qty', $_POST['qty'], null, null, $unit_dec);
      if ($this->order->trans_no != 0) {
        Cell::qty($line_no == -1 ? 0 : $this->order->line_items[$line_no]->qty_done, false, $dec);
      }
      Cell::label($units, '', 'units');
      Forms::amountCellsEx(null, 'price', 'small', null, null, null, ['$']);
      Forms::percentCells(null, 'Disc', Num::_percentFormat($_POST['Disc']));
      $line_total = Validation::input_num('qty') * Validation::input_num('price') * (1 - Validation::input_num('Disc') / 100);
      Cell::amount($line_total, false, '', 'line_total');
      if ($id != -1) {
        Forms::buttonCell(FORM_ACTION, Orders::UPDATE_ITEM, _("Update"), ICON_UPDATE); //_('Confirm changes'),
        Forms::buttonCell(FORM_ACTION, Orders::CANCEL_ITEM_CHANGES, _("Cancel"), ICON_CANCEL); //, _('Cancel changes')
        Forms::hidden('LineNo', $line_no);
        $this->JS->setFocus('qty');
      } else {
        Forms::submitCells(FORM_ACTION, Orders::ADD_LINE, 'colspan=2 class="center"', _("Add Item"), true); //_('Add new item to document'),
      }
      echo '</tr>';
    }
    /**
     * @param      $date_text
     * @param bool $display_tax_group
     *
     * @return mixed|string
     */
    public function header($date_text, $display_tax_group = false) {
      $editable = ($this->order->any_already_delivered() == 0);
      Table::startOuter('standard width90');
      Table::section(1);
      $customer_error = "";
      $change_prices  = 0;
      if (!$editable) {
        if (isset($this)) {
          // can't change the customer/branch if items already received on this order
          //echo $this->order->customer_name . " - " . $this->order->deliver_to;
          Table::label(_('Customer:'), $this->order->customer_name . " - " . $this->order->deliver_to, "id='debtor_id_label' class='label pointer'");
          Forms::hidden('debtor_id', $this->order->debtor_id);
          Forms::hidden('branch_id', $this->order->Branch);
          Forms::hidden('sales_type', $this->order->sales_type);
          //		if ($this->order->trans_type != ST_SALESORDER && $this->order->trans_type != ST_SALESQUOTE) {
          Forms::hidden('dimension_id', $this->order->dimension_id); // 2008-11-12 Joe Hunt
          Forms::hidden('dimension2_id', $this->order->dimension2_id);
          //		}
        }
      } else {
        //Debtor::row(_("Customer:"), 'debtor_id', null, false, true, false, true);
        Debtor::newselect();
        if (Input::_post(FORM_CONTROL) == 'customer') {
          // customer has changed
          $this->JS->setFocus('stock_id');
          $this->Ajax->activate('_page_body');
        }
        Debtor_Branch::row(_("Branch:"), $_POST['debtor_id'], 'branch_id', null, false, true, true, true);
        if (($this->order->Branch != Input::_post('branch_id', null, -1))) {
          if (!isset($_POST['branch_id']) || !$_POST['branch_id']) {
            // ignore errors on customer search box call
            if (!$_POST['debtor_id']) {
              Event::warning("No customer found for entered text.", false);
            } else {
              Event::warning("The selected customer does not have any branches. Please create at least one branch.", false);
            }
            unset($_POST['branch_id']);
            $this->order->Branch = 0;
          } else {
            $old_order                 = clone($this);
            $customer_error            = $this->order->customer_to_order($_POST['debtor_id'], $_POST['branch_id']);
            $_POST['location']         = $this->order->location;
            $_POST['deliver_to']       = $this->order->deliver_to;
            $_POST['delivery_address'] = $this->order->delivery_address;
            $_POST['name']             = $this->order->name;
            $_POST['phone']            = $this->order->phone;
            if (Input::_post('cash') !== $this->order->cash) {
              $_POST['cash'] = $this->order->cash;
              $this->Ajax->activate('delivery');
              $this->Ajax->activate('cash');
            } else {
              if ($this->order->trans_type == ST_SALESINVOICE) {
                $_POST['delivery_date'] = $this->order->due_date;
                $this->Ajax->activate('delivery_date');
              }
              $this->Ajax->activate('location');
              $this->Ajax->activate('deliver_to');
              $this->Ajax->activate('name');
              $this->Ajax->activate('phone');
              $this->Ajax->activate('delivery_address');
            }
            // change prices if necessary
            // what about discount in template case?
            /** @var Sales_Order $old_order */
            if ($old_order->customer_currency != $this->order->customer_currency) {
              $change_prices = 1;
            }
            if ($old_order->sales_type != $this->order->sales_type) {
              // || $old_order->default_discount!=$this->order->default_discount
              $_POST['sales_type'] = $this->order->sales_type;
              $this->Ajax->activate('sales_type');
              $change_prices = 1;
            }
            if ($old_order->dimension_id != $this->order->dimension_id) {
              $_POST['dimension_id'] = $this->order->dimension_id;
              $this->Ajax->activate('dimension_id');
            }
            if ($old_order->dimension2_id != $this->order->dimension2_id) {
              $_POST['dimension2_id'] = $this->order->dimension2_id;
              $this->Ajax->activate('dimension2_id');
            }
            unset($old_order);
          }
          Session::_setGlobal('debtor_id', $_POST['debtor_id']);
        } // changed branch
        else {
          $row = Sales_Order::get_customer($_POST['debtor_id']);
          if ($row['dissallow_invoices'] == 1) {
            $customer_error = _("The selected customer account is currently on hold. Please contact the credit control personnel to discuss.");
          }
        }
      }
      if ($editable) {
        Forms::refRow(_("Reference") . ':', 'ref', _('Reference number unique for this document type'), $this->order->reference ? : Ref::get_next($this->order->trans_type), '');
      } else {
        Forms::hidden('ref', $this->order->reference);
        Table::label(_("Reference:"), $this->order->reference);
      }
      if (!Bank_Currency::is_company($this->order->customer_currency)) {
        Table::section(2);
        Table::label(_("Customer Currency:"), $this->order->customer_currency);
        GL_ExchangeRate::display($this->order->customer_currency, Bank_Currency::for_company(), ($editable && Input::_post('OrderDate') ? $_POST['OrderDate'] : $this->order->document_date)
        );
      }
      Table::section(3);
      if ($_POST['debtor_id']) {
        Debtor_Payment::credit_row($_POST['debtor_id'], $this->order->credit);
      }
      if ($editable) {
        Sales_Type::row(_("Price List"), 'sales_type', null, true);
      } else {
        Table::label(_("Price List:"), $this->order->sales_type_name);
      }
      if ($this->order->sales_type != $_POST['sales_type']) {
        $myrow = Sales_Type::get($_POST['sales_type']);
        $this->order->set_sales_type($myrow['id'], $myrow['sales_type'], $myrow['tax_included'], $myrow['factor']);
        $this->Ajax->activate('sales_type');
        $change_prices = 1;
      }
      Table::label(_("Customer Discount:"), ($this->order->default_discount * 100) . "%");
      Table::section(4);
      if ($editable) {
        if (!isset($_POST['OrderDate']) || !$_POST['OrderDate']) {
          $_POST['OrderDate'] = $this->order->document_date;
        }
        Forms::dateRow($date_text, 'OrderDate', null, $this->order->trans_no == 0, 0, 0, 0, null, true);
        if (isset($_POST['_OrderDate_changed'])) {
          if (!Bank_Currency::is_company($this->order->customer_currency) && (DB_Company::_get_base_sales_type() > 0)) {
            $change_prices = 1;
          }
          $this->Ajax->activate('_ex_rate');
          if ($this->order->trans_type == ST_SALESINVOICE) {
            $_POST['delivery_date'] = Sales_Order::get_invoice_duedate(Input::_post('debtor_id'), Input::_post('OrderDate'));
          } else {
            $_POST['delivery_date'] = Dates::_addDays(Input::_post('OrderDate'), DB_Company::_get_pref('default_delivery_required'));
          }
          $this->Ajax->activate('items_table');
          $this->Ajax->activate('delivery_date');
        }
        if ($this->order->trans_type != ST_SALESORDER && $this->order->trans_type != ST_SALESQUOTE) { // 2008-11-12 Joe Hunt added dimensions
          $dim = DB_Company::_get_pref('use_dimension');
          if ($dim > 0) {
            Dimensions::select_row(_("Dimension") . ":", 'dimension_id', null, true, ' ', false, 1, false);
          } else {
            Forms::hidden('dimension_id', 0);
          }
          if ($dim > 1) {
            Dimensions::select_row(_("Dimension") . " 2:", 'dimension2_id', null, true, ' ', false, 2, false);
          } else {
            Forms::hidden('dimension2_id', 0);
          }
        }
      } else {
        Table::label($date_text, $this->order->document_date);
        Forms::hidden('OrderDate', $this->order->document_date);
      }
      if ($display_tax_group) {
        Table::label(_("Tax Group:"), $this->order->tax_group_name);
        Forms::hidden('tax_group_id', $this->order->tax_group_id);
      }
      Sales_UI::persons_row(_("Sales Person:"), 'salesman', (isset($this->order->salesman)) ? $this->order->salesman : $this->User->i()->salesmanid);
      Table::endOuter(1); // outer table
      if ($change_prices != 0) {
        foreach ($this->order->line_items as $line) {
          $line->price = Item_Price::get_kit($line->stock_id, $this->order->customer_currency, $this->order->sales_type, $this->order->price_factor, Input::_post('OrderDate'));
        }
        $this->Ajax->activate('items_table');
      }
      return $customer_error;
    }
  }

