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
  use ADV\App\Page;
  use ADV\App\Display;
  use ADV\Core\Event;
  use ADV\App\Validation;
  use ADV\Core\JS;

  JS::_openWindow(950, 500);
  Page::start(_($help_context = "Item Adjustments Note"), SA_INVENTORYADJUSTMENT);
  Validation::check(Validation::COST_ITEMS, _("There are no inventory items defined in the system which can be adjusted (Purchased or Manufactured)."), STOCK_SERVICE);
  Validation::check(Validation::MOVEMENT_TYPES, _("There are no inventory movement types defined in the system. Please define at least one inventory adjustment type."));
  if (isset($_GET[ADDED_ID])) {
    $trans_no   = $_GET[ADDED_ID];
    $trans_type = ST_INVADJUST;
    Event::notice(_("Items adjustment has been processed"));
    Display::note(GL_UI::viewTrans($trans_type, $trans_no, _("&View this adjustment")));
    Display::note(GL_UI::view($trans_type, $trans_no, _("View the GL &Postings for this Adjustment")), 1, 0);
    Display::link_params($_SERVER['DOCUMENT_URI'], _("Enter &Another Adjustment"));
    Page::footer_exit();
  }
  if (isset($_POST['Process']) && can_process()) {
    $trans_no = Inv_Adjustment::add(
      $_SESSION['adj_items']->line_items,
      $_POST['StockLocation'],
      $_POST['AdjDate'],
      $_POST['type'],
      $_POST['Increase'],
      $_POST['ref'],
      $_POST['memo_']
    );
    Dates::_newDocDate($_POST['AdjDate']);
    $_SESSION['adj_items']->clear_items();
    unset($_SESSION['adj_items']);
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
  if (isset($_GET['NewAdjustment']) || !isset($_SESSION['adj_items'])) {
    handle_new_order();
  }
  Forms::start();
  Inv_Adjustment::header($_SESSION['adj_items']);
  Table::startOuter('padded width80 pad10');
  Inv_Adjustment::display_items(_("Adjustment Items"), $_SESSION['adj_items']);
  Inv_Adjustment::option_controls();
  Table::endOuter(1, false);
  Forms::submitCenterBegin('Update', _("Update"), '', null);
  Forms::submitCenterEnd('Process', _("Process Adjustment"), '', 'default');
  Forms::end();
  Page::end();
  /**
   * @return bool
   */
  function check_item_data() {
    if (!Validation::post_num('qty', 0)) {
      Event::error(_("The quantity entered is negative or invalid."));
      JS::_setFocus('qty');
      return false;
    }
    if (!Validation::post_num('std_cost', 0)) {
      Event::error(_("The entered standard cost is negative or invalid."));
      JS::_setFocus('std_cost');
      return false;
    }
    return true;
  }

  function handle_update_item() {
    if ($_POST['updateItem'] != "" && check_item_data()) {
      $id = $_POST['LineNo'];
      $_SESSION['adj_items']->update_order_item($id, Validation::input_num('qty'), Validation::input_num('std_cost'));
    }
    Item_Line::start_focus('stock_id');
  }

  /**
   * @param $id
   */
  function handle_delete_item($id) {
    $_SESSION['adj_items']->remove_from_order($id);
    Item_Line::start_focus('stock_id');
  }

  function handle_new_item() {
    if (!check_item_data()) {
      return;
    }
    Item_Order::add_line($_SESSION['adj_items'], $_POST['stock_id'], Validation::input_num('qty'), Validation::input_num('std_cost'));
    Item_Line::start_focus('stock_id');
  }

  function handle_new_order() {
    if (isset($_SESSION['adj_items'])) {
      $_SESSION['adj_items']->clear_items();
      unset ($_SESSION['adj_items']);
    }
    $_SESSION['adj_items'] = new Item_Order(ST_INVADJUST);
    $_POST['AdjDate']      = Dates::_newDocDate();
    if (!Dates::_isDateInFiscalYear($_POST['AdjDate'])) {
      $_POST['AdjDate'] = Dates::_endFiscalYear();
    }
    $_SESSION['adj_items']->tran_date = $_POST['AdjDate'];
  }

  /**
   * @return bool
   */
  function can_process() {
    $adj = & $_SESSION['adj_items'];
    if (count($adj->line_items) == 0) {
      Event::error(_("You must enter at least one non empty item line."));
      JS::_setFocus('stock_id');
      return false;
    }
    if (!Ref::is_valid($_POST['ref'])) {
      Event::error(_("You must enter a reference."));
      JS::_setFocus('ref');
      return false;
    }
    if (!Ref::is_new($_POST['ref'], ST_INVADJUST)) {
      $_POST['ref'] = Ref::get_next(ST_INVADJUST);
    }
    if (!Dates::_isDate($_POST['AdjDate'])) {
      Event::error(_("The entered date for the adjustment is invalid."));
      JS::_setFocus('AdjDate');
      return false;
    } elseif (!Dates::_isDateInFiscalYear($_POST['AdjDate'])) {
      Event::error(_("The entered date is not in fiscal year."));
      JS::_setFocus('AdjDate');
      return false;
    } else {
      $failed_item = $adj->check_qoh($_POST['StockLocation'], $_POST['AdjDate'], !$_POST['Increase']);
      if ($failed_item >= 0) {
        $line = $adj->line_items[$failed_item];
        Event::error(
          _("The adjustment cannot be processed because an adjustment item would cause a negative inventory balance :") . " " . $line->stock_id . " - " . $line->description
        );
        $_POST[MODE_EDIT . $failed_item] = 1; // enter edit mode
        unset($_POST['Process']);
        return false;
      }
    }
    return true;
  }


