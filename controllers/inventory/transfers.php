<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  use ADV\Core\JS;
  use ADV\Core\Table;
  use ADV\App\Forms;
  use ADV\App\Dates;
  use ADV\App\Ref;
  use ADV\App\Display;
  use ADV\Core\Event;
  use ADV\App\Validation;
  use ADV\App\Page;

  JS::_openWindow(950, 500);
  Page::start(_($help_context = "Inventory Location Transfers"), SA_LOCATIONTRANSFER);
  Validation::check(Validation::COST_ITEMS, _("There are no inventory items defined in the system (Purchased or manufactured items)."), STOCK_SERVICE);
  Validation::check(Validation::MOVEMENT_TYPES, _("There are no inventory movement types defined in the system. Please define at least one inventory adjustment type."));
  if (isset($_GET[ADDED_ID])) {
    $trans_no   = $_GET[ADDED_ID];
    $trans_type = ST_LOCTRANSFER;
    Event::success(_("Inventory transfer has been processed"));
    Display::note(GL_UI::viewTrans($trans_type, $trans_no, _("&View this transfer")));
    Display::link_params($_SERVER['DOCUMENT_URI'], _("Enter &Another Inventory Transfer"));
    Page::footer_exit();
  }
  if (isset($_POST['Process'])) {
    $tr          = & $_SESSION['transfer_items'];
    $input_error = 0;
    if (count($tr->line_items) == 0) {
      Event::error(_("You must enter at least one non empty item line."));
      JS::_setFocus('stock_id');
      return false;
    }
    if (!Ref::is_valid($_POST['ref'])) {
      Event::error(_("You must enter a reference."));
      JS::_setFocus('ref');
      $input_error = 1;
    } elseif (!Ref::is_new($_POST['ref'], ST_LOCTRANSFER)) {
      $_POST['ref'] = Ref::get_next(ST_LOCTRANSFER);
    } elseif (!Dates::_isDate($_POST['AdjDate'])) {
      Event::error(_("The entered date for the adjustment is invalid."));
      JS::_setFocus('AdjDate');
      $input_error = 1;
    } elseif (!Dates::_isDateInFiscalYear($_POST['AdjDate'])) {
      Event::error(_("The entered date is not in fiscal year."));
      JS::_setFocus('AdjDate');
      $input_error = 1;
    } elseif ($_POST['FromStockLocation'] == $_POST['ToStockLocation']) {
      Event::error(_("The locations to transfer from and to must be different."));
      JS::_setFocus('FromStockLocation');
      $input_error = 1;
    } else {
      $failed_item = $tr->check_qoh($_POST['FromStockLocation'], $_POST['AdjDate'], true);
      if ($failed_item >= 0) {
        $line = $tr->line_items[$failed_item];
        Event::error(_("The quantity entered is greater than the available quantity for this item at the source location :") . " " . $line->stock_id . " - " . $line->description);
        echo "<br>";
        $_POST[MODE_EDIT . $failed_item] = 1; // enter edit mode
        $input_error                     = 1;
      }
    }
    if ($input_error == 1) {
      unset($_POST['Process']);
    }
  }
  if (isset($_POST['Process'])) {
    $trans_no = Inv_Transfer::add(
      $_SESSION['transfer_items']->line_items, $_POST['FromStockLocation'], $_POST['ToStockLocation'], $_POST['AdjDate'], $_POST['type'], $_POST['ref'], $_POST['memo_']
    );
    Dates::_newDocDate($_POST['AdjDate']);
    $_SESSION['transfer_items']->clear_items();
    unset($_SESSION['transfer_items']);
    Display::meta_forward($_SERVER['DOCUMENT_URI'], "AddedID=$trans_no");
  } /*end of process credit note */
  $id = Forms::findPostPrefix(MODE_DELETE);
  if ($id != -1) {
    handle_delete_item($id);
  }
  if (isset($_POST['addLine'])) {
    handle_new_item();
  }
  if (isset($_POST['updateItem'])) {
    handle_update_item();
  }
  if (isset($_POST['cancelItem'])) {
    Item_Line::start_focus('stock_id');
  }
  if (isset($_GET['NewTransfer']) || !isset($_SESSION['transfer_items'])) {
    handle_new_order();
  }
  Forms::start();
  Inv_Transfer::header($_SESSION['transfer_items']);
  Table::start('tablesstyle width70 pad10');
  echo '<tr>';
  echo "<td>";
  Inv_Transfer::display_items(_("Items"), $_SESSION['transfer_items']);
  Inv_Transfer::option_controls();
  echo "</td>";
  echo '</tr>';
  Table::end(1);
  Forms::submitCenterBegin('Update', _("Update"), '', null);
  Forms::submitCenterEnd('Process', _("Process Transfer"), '', 'default');
  Forms::end();
  Page::end();
  /**
   * @return bool
   */
  function check_item_data() {
    if (!Validation::post_num('qty', 0)) {
      Event::error(_("The quantity entered must be a positive number."));
      JS::_setFocus('qty');
      return false;
    }
    return true;
  }

  function handle_update_item() {
    if ($_POST['updateItem'] != "" && check_item_data()) {
      $id = $_POST['LineNo'];
      if (!isset($_POST['std_cost'])) {
        $_POST['std_cost'] = $_SESSION['transfer_items']->line_items[$id]->standard_cost;
      }
      $_SESSION['transfer_items']->update_order_item($id, Validation::input_num('qty'), $_POST['std_cost']);
    }
    Item_Line::start_focus('stock_id');
  }

  /**
   * @param $id
   */
  function handle_delete_item($id) {
    $_SESSION['transfer_items']->remove_from_order($id);
    Item_Line::start_focus('stock_id');
  }

  function handle_new_item() {
    if (!check_item_data()) {
      return;
    }
    if (!isset($_POST['std_cost'])) {
      $_POST['std_cost'] = 0;
    }
    Item_Order::add_line($_SESSION['transfer_items'], $_POST['stock_id'], Validation::input_num('qty'), $_POST['std_cost']);
    Item_Line::start_focus('stock_id');
  }

  function handle_new_order() {
    if (isset($_SESSION['transfer_items'])) {
      $_SESSION['transfer_items']->clear_items();
      unset ($_SESSION['transfer_items']);
    }
    $_SESSION['transfer_items'] = new Item_Order(ST_LOCTRANSFER);
    $_POST['AdjDate']           = Dates::_newDocDate();
    if (!Dates::_isDateInFiscalYear($_POST['AdjDate'])) {
      $_POST['AdjDate'] = Dates::_endFiscalYear();
    }
    $_SESSION['transfer_items']->tran_date = $_POST['AdjDate'];
  }

