<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Controllers\Purchases;

  use ADV\App\Item\Item;
  use Purch_UI;
  use ADV\Core\Num;
  use Sales_Order;
  use ADV\Core\View;
  use Purch_Order;
  use ADV\Core\Event;
  use Item_Line;
  use ADV\App\Form\Form;
  use ADV\App\Display;
  use ADV\App\Forms;
  use ADV\App\Ref;
  use ADV\App\Validation;
  use ADV\App\Dates;
  use ADV\App\Orders;
  use ADV\App\Reporting;
  use ADV\App\Creditor\Creditor;
  use ADV\App\UI;
  use ADV\Core\Table;
  use ADV\Core\DB\DB;
  use ADV\Core\Input\Input;

  /** **/
  class Order extends \ADV\App\Controller\Action
  {
    protected $iframe = '';
    /** @var \Purch_Order */
    protected $order = null;
    protected $creditor_id;
    protected $security = SA_OPEN;
    protected function before() {
      $this->JS->openWindow(950, 500);
      $this->order = Orders::session_get() ? : null;
      if ($this->Input->get('creditor_id', Input::NUMERIC)) {
        $this->action      = Orders::CANCEL_CHANGES;
        $this->creditor_id = $_POST['creditor_id'] = $_GET['creditor_id'];
        $this->Ajax->activate('creditor_id');
      }
      if ($this->action == Orders::CANCEL_CHANGES) {
        $this->cancelChanges();
      }
      $id = Forms::findPostPrefix(MODE_DELETE);
      if ($id != -1 && $this->order) {
        $this->deleteItem($id);
      }
      if (isset($_POST[UPDATE_ITEM]) && $this->checkData()) {
        $this->updateItem();
      }
      if (isset($_POST[ADD_ITEM])) {
        $this->addItem();
      }
      if (isset($_POST[CANCEL])) {
        $this->cancelItem();
      }
      $this->iframe = "<div class='center'><iframe src='" . e(
          '/purchases/search/completed?' . LOC_NOT_FAXED_YET . '=1&frame=1'
        ) . "' class='width70' style='height:300px' ></iframe></div>";
      if ($this->Input->get(Orders::MODIFY_ORDER)) {
        $this->order = $this->createOrder($_GET[Orders::MODIFY_ORDER]);
      } elseif (isset($_POST[CANCEL]) || isset($_POST[UPDATE_ITEM])) {
        Item_Line::start_focus('stock_id');
      } elseif (isset($_GET[Orders::NEW_ORDER]) || !isset($this->order)) {
        $this->order = $this->createOrder();
        if ($this->Input->get('UseOrder') && !count($this->order->line_items)) {
        }
      }
      if (isset($_GET[Orders::MODIFY_ORDER])) {
        $this->security = SA_PURCHASEORDER;
        $this->setTitle("Modify Purchase Order #" . $_GET[Orders::MODIFY_ORDER]);
      } else {
        $this->security = SA_PURCHASEORDER;
        $this->setTitle("Purchase Order Entry");
      }
      $this->checkSecurity();
    }
    protected function cancelChanges() {
      $order_no = $this->order->order_no;
      Orders::session_delete($_POST['order_id']);
      $this->order = $this->createOrder($order_no);
    }
    /**
     * @param $id
     */
    protected function deleteItem($id) {
      if ($this->order->some_already_received($id) == 0) {
        $this->order->remove_from_order($id);
        unset($_POST['stock_id'], $_POST['qty'], $_POST['price'], $_POST['req_del_date']);
      } else {
        Event::error(_("This item cannot be deleted because some of it has already been received."));
      }
      Item_Line::start_focus('stock_id');
    }
    protected function commitOrder() {
      Purch_Order::copyFromPost($this->order);
      $this->creditor_id = $this->order->creditor_id;
      if ($this->canCommit($this->order)) {
        $order_no = ($this->order->order_no == 0) ? $this->order->add() : $this->order->update();
        if ($order_no) {
          Dates::_newDocDate($this->order->orig_order_date);
          Orders::session_delete($_POST['order_id']);
          $this->pageComplete($order_no);
        }
      }
    }
    protected function cancelItem() {
      unset($_POST['stock_id'], $_POST['qty'], $_POST['price'], $_POST['req_del_date']);
    }
    protected function cancelOrder() {
      //need to check that not already dispatched or invoiced by the supplier
      if (($this->order->order_no != 0) && $this->order->any_already_received() == 1) {
        Event::error(
             _("This order cannot be cancelled because some of it has already been received.") . "<br>" . _(
               "The line item quantities may be modified to quantities more than already received. prices cannot be altered for lines that have already been received and quantities cannot be reduced below the quantity already received."
             )
        );
      } else {
        Orders::session_delete($this->order->order_id);
        if ($this->order->order_no != 0) {
          $this->order->delete();
        }
        Orders::session_delete($this->order->order_id);
        Event::notice(_("This purchase order has been cancelled."));
        Display::link_params("/purchases/order", _("Enter a new purchase order"), "NewOrder=Yes");
        $this->Page->endExit();
      }
    }
    protected function addItem() {
      $allow_update = $this->checkData();
      if ($allow_update == true) {
        if ($allow_update == true) {
          $sql
                  = "SELECT long_description as description , units, mb_flag
  				FROM stock_master WHERE stock_id = " . DB::_escape($_POST['stock_id']);
          $result = DB::_query($sql, "The stock details for " . $_POST['stock_id'] . " could not be retrieved");
          if (DB::_numRows($result) == 0) {
            $allow_update = false;
          }
          if ($allow_update) {
            $myrow = DB::_fetch($result);
            $this->order->add_to_order(
                        $_POST['line_no'], $_POST['stock_id'], Validation::input_num('qty'), $_POST['description'], Validation::input_num('price'), $myrow["units"], $_POST['req_del_date'], 0, 0, $_POST['discount'] / 100
            );
            unset($_POST['stock_id'], $_POST['qty'], $_POST['price'], $_POST['req_del_date']);
            $_POST['stock_id'] = "";
          } else {
            Event::error(_("The selected item does not exist or it is a kit part and therefore cannot be purchased."));
          }
        } /* end of if not already on the order and allow input was true*/
      }
      Item_Line::start_focus('stock_id');
    }
    protected function updateItem() {
      if ($this->order->line_items[$_POST['line_no']]->qty_inv > Validation::input_num(
                                                                           'qty'
        ) || $this->order->line_items[$_POST['line_no']]->qty_received > Validation::input_num('qty')
      ) {
        Event::error(
             _("You are attempting to make the quantity ordered a quantity less than has already been invoiced or received. This is prohibited.") . "<br>" . _(
               "The quantity received can only be modified by entering a negative receipt and the quantity invoiced can only be reduced by entering a credit note against this item."
             )
        );
        $this->JS->setFocus('qty');
      } else {
        $this->order->update_order_item(
                    $_POST['line_no'], Validation::input_num('qty'), Validation::input_num('price'), $_POST['req_del_date'], $_POST['description'], $_POST['discount'] / 100
        );
        unset($_POST['stock_id'], $_POST['qty'], $_POST['price'], $_POST['req_del_date']);
        Item_Line::start_focus('stock_id');
      }
    }
    protected function index() {
      if ($this->action == COMMIT) {
        $this->commitOrder();
      }
      if ($this->order && $this->action == Orders::CANCEL) {
        $this->cancelOrder();
      }
      Forms::start();
      echo "<br>";
      Forms::hidden('order_id');
      if ($this->order->creditor_id == 0 || isset($_GET[Orders::NEW_ORDER])) {
        echo $this->iframe;
      }
      $this->order->header();
      $this->order->display_items();
      Table::start('standard');
      Forms::textareaRow(_("Memo:"), 'Comments', null, 70, 4);
      Table::end(1);
      $this->Ajax->start_div('controls', 'items_table');
      $buttons = new Form();
      if ($this->order->order_has_items()) {
        if ($this->order->order_no > 0 && $this->User->hasAccess(SA_VOIDTRANSACTION)) {
          $buttons->submit(Orders::CANCEL, "Delete Order")->preIcon(ICON_DELETE)->setWarning(
                                                                                'You are about to void this Document.\nDo you want to continue?'
          )                                                                     ->type(\ADV\App\Form\Button::DANGER);
        }
        $buttons->submit(Orders::CANCEL_CHANGES, "Cancel Changes")->preIcon(ICON_CANCEL)->type('warning');
      }
      if ($this->order->order_no) {
        $buttons->submit(COMMIT, "Update Order")->preIcon(ICON_UPDATE)->type(\ADV\App\Form\Button::SUCCESS);
      } else {
        $buttons->submit(COMMIT, "Place Order")->preIcon(ICON_SUBMIT)->type(\ADV\App\Form\Button::SUCCESS);
      }
      $view = new View('libraries/forms');
      $view->set('buttons', $buttons);
      $view->render();
      $this->Ajax->end_div();
      Forms::end();
      Item::addEditDialog();
      UI::emailDialogue(CT_SUPPLIER);
      if (isset($this->order->creditor_id)) {
        Creditor::addInfoDialog("td[name=\"supplier_name\"]", $this->order->supplier_details['creditor_id']);
      }
    }
    protected function runValidation() {
      Validation::check(Validation::SUPPLIERS, _("There are no suppliers defined in the system."));
      Validation::check(Validation::PURCHASE_ITEMS, _("There are no purchasable inventory items defined in the system."), STOCK_PURCHASED);
    }
    /**
     * @param $order_no
     */
    protected function pageComplete($order_no) {
      Event::success('Purchase order entry successful');
      $trans_type = ST_PURCHORDER;
      $new_trans  = "/purchases/order?" . Orders::NEW_ORDER;
      $view       = new View('orders/complete');
      $view->set('viewtrans', Purch_UI::viewTrans($trans_type, $order_no, _("View this order"), false, 'button'));
      $href       = Reporting::print_doc_link($order_no, '', true, $trans_type, false, '', '', 0, 0, true);
      $buttons[]  = ['target' => '_new', 'label' => _("Print This Order"), 'href' => $href];
      $edit_trans = ROOT_URL . "purchases/order?ModifyOrder=$order_no";
      $buttons[]  = ['label' => _("Edit This Order"), 'href' => $edit_trans];
      $view->set('emailtrans', Reporting::emailDialogue($this->creditor_id, ST_PURCHORDER, $order_no));
      $buttons[] = ['label' => 'Receive this purchase order', 'accesskey' => 'R', 'href' => "/purchases/po_receive_items.php?PONumber=$order_no"];
      $buttons[] = ['label' => 'New purchase order', 'accesskey' => 'N', 'href' => $new_trans];
      $view->set('buttons', $buttons);
      $view->render();
      $this->Ajax->activate('_page_body', $new_trans, $edit_trans);
      $this->Page->endExit();
    }
    /**
     * @param int $order_no
     *
     * @return \Purch_Order|\Sales_Order
     */
    protected function createOrder($order_no = 0) {
      $getUuseOrder = $this->Input->get('UseOrder');
      if ($getUuseOrder) {
        if (isset(Orders::session_get($getUuseOrder)->line_items)) {
          $sales_order = Orders::session_get($_GET['UseOrder']);
        } else {
          $sales_order = new Sales_Order(ST_SALESORDER, array($_GET['UseOrder']));
        }
        $this->order = new Purch_Order($order_no);
        $stock       = $myrow = [];
        foreach ($sales_order->line_items as $line_item) {
          $stock[] = ' stock_id = ' . DB::_escape($line_item->stock_id);
        }
        $sql    = "SELECT AVG(price),creditor_id,COUNT(creditor_id) FROM purch_data WHERE " . implode(' OR ', $stock) . ' GROUP BY creditor_id ORDER BY AVG(price)';
        $result = DB::_query($sql);
        $row    = DB::_fetch($result);
        $this->order->supplier_to_order($row['creditor_id']);
        foreach ($sales_order->line_items as $line_no => $line_item) {
          $this->order->add_to_order(
                      $line_no, $line_item->stock_id, $line_item->quantity, $line_item->description, 0, $line_item->units, Dates::_addDays(Dates::_today(), 10), 0, 0, 0
          );
        }
        if (isset($_GET[LOC_DROP_SHIP])) {
          $item_info         = Item::get('DS');
          $_POST['location'] = $this->order->location = LOC_DROP_SHIP;
          $this->order->add_to_order(count($sales_order->line_items), 'DS', 1, $item_info['long_description'], 0, '', Dates::_addDays(Dates::_today(), 10), 0, 0, 0);
          $address = $sales_order->customer_name . "\n";
          if (!empty($sales_order->name) && $sales_order->deliver_to == $sales_order->customer_name) {
            $address .= $sales_order->name . "\n";
          } elseif ($sales_order->deliver_to != $sales_order->customer_name) {
            $address .= $sales_order->deliver_to . "\n";
          }
          if (!empty($sales_order->phone)) {
            $address .= 'Ph:' . $sales_order->phone . "\n";
          }
          $address .= $sales_order->delivery_address;
          $this->order->delivery_address = $address;
        }
        unset($_POST['order_id']);
      } else {
        $this->order = new Purch_Order($order_no);
      }
      $this->order = Purch_Order::copyToPost($this->order);
      return $this->order;
    }
    /**
     * @return bool
     */
    protected function checkData() {
      $dec = Item::qty_dec($_POST['stock_id']);
      $min = 1 / pow(10, $dec);
      if (!Validation::post_num('qty', $min)) {
        $min = Num::_format($min, $dec);
        Event::error(_("The quantity of the order item must be numeric and not less than ") . $min);
        $this->JS->setFocus('qty');
        return false;
      }
      if (!Validation::post_num('price', 0)) {
        Event::error(_("The price entered must be numeric and not less than zero."));
        $this->JS->setFocus('price');
        return false;
      }
      if (!Validation::post_num('discount', 0, 100)) {
        Event::error(_("Discount percent can not be less than 0 or more than 100."));
        $this->JS->setFocus('discount');
        return false;
      }
      if (!Dates::_isDate($_POST['req_del_date'])) {
        Event::error(_("The date entered is in an invalid format."));
        $this->JS->setFocus('req_del_date');
        return false;
      }
      return true;
    }
    /**
     * @internal param \Purch_Order $this ->order
     * @return bool
     */
    protected function canCommit() {
      if (!$this->order) {
        Event::error(_("You are not currently editing an order."));
        $this->Page->endExit();
      }
      if (!$this->Input->post('creditor_id')) {
        Event::error(_("There is no supplier selected."));
        $this->JS->setFocus('creditor_id');
        return false;
      }
      if (!Dates::_isDate($_POST['OrderDate'])) {
        Event::error(_("The entered order date is invalid."));
        $this->JS->setFocus('OrderDate');
        return false;
      }
      if ($this->Input->post('delivery_address') == '') {
        Event::error(_("There is no delivery address specified."));
        $this->JS->setFocus('delivery_address');
        return false;
      }
      if (!Validation::post_num('freight', 0)) {
        Event::error(_("The freight entered must be numeric and not less than zero."));
        $this->JS->setFocus('freight');
        return false;
      }
      if ($this->Input->post('location') == '') {
        Event::error(_("There is no location specified to move any items into."));
        $this->JS->setFocus('location');
        return false;
      }
      if ($this->order->order_has_items() == false) {
        Event::error(_("The order cannot be placed because there are no lines entered on this order."));
        return false;
      }
      if (!$this->order->order_no) {
        if (!Ref::is_valid($this->Input->post('ref'))) {
          Event::error(_("There is no reference entered for this purchase order."));
          $this->JS->setFocus('ref');
          return false;
        }
        if (!Ref::is_new($_POST['ref'], ST_PURCHORDER)) {
          $_POST['ref'] = Ref::get_next(ST_PURCHORDER);
        }
      }
      return true;
    }
  }

