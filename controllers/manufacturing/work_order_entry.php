<?php
  use ADV\App\WO\WO;
  use ADV\Core\Table;
  use ADV\App\Forms;
  use ADV\Core\Ajax;
  use ADV\App\Item\Item;
  use ADV\Core\DB\DB;
  use ADV\Core\Num;
  use ADV\Core\Input\Input;
  use ADV\App\Ref;
  use ADV\App\Dates;
  use ADV\App\Reporting;
  use ADV\App\Display;
  use ADV\Core\Event;
  use ADV\App\Validation;
  use ADV\App\Page;
  use ADV\Core\JS;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  JS::_openWindow(950, 500);
  Page::start(_($help_context = "Work Order Entry"), SA_WORKORDERENTRY);
  Validation::check(Validation::MANUFACTURE_ITEMS, _("There are no manufacturable items defined in the system."), STOCK_MANUFACTURE);
  Validation::check(Validation::LOCATIONS, ("There are no inventory locations defined in the system."));
  if (isset($_GET['trans_no'])) {
    $selected_id = $_GET['trans_no'];
  } elseif (isset($_POST['selected_id'])) {
    $selected_id = $_POST['selected_id'];
  }
  if (isset($_GET[ADDED_ID])) {
    $id    = $_GET[ADDED_ID];
    $stype = ST_WORKORDER;
    Event::success(_("The work order been added."));
    Display::note(GL_UI::viewTrans($stype, $id, _("View this Work Order")));
    if ($_GET['type'] != WO_ADVANCED) {
        $ar = [
            'PARAM_0' => $id,
        'PARAM_1' => $id,
        'PARAM_2' => 0
        ];
        Display::note(Reporting::print_link(_("Print this Work Order"), 409, $ar), 1);
      $ar['PARAM_2'] = 1;
      Display::note(Reporting::print_link(_("Email this Work Order"), 409, $ar), 1);
      Event::warning(GL_UI::view($stype, $id, _("View the GL Journal Entries for this Work Order")), 1);
        $ar = [
            'PARAM_0' => $_GET['date'],
        'PARAM_1' => $_GET['date'],
        'PARAM_2' => $stype
        ];
        Event::warning(Reporting::print_link(_("Print the GL Journal Entries for this Work Order"), 702, $ar), 1);
    }
    safe_exit();
  }
  if (isset($_GET[UPDATED_ID])) {
    $id = $_GET[UPDATED_ID];
    Event::success(_("The work order been updated."));
    safe_exit();
  }
  if (isset($_GET['DeletedID'])) {
    $id = $_GET['DeletedID'];
    Event::notice(_("Work order has been deleted."));
    safe_exit();
  }
  if (isset($_GET['ClosedID'])) {
    $id = $_GET['ClosedID'];
    Event::notice(_("This work order has been closed. There can be no more issues against it.") . " #$id");
    safe_exit();
  }
  function safe_exit() {
    Display::link_params("", _("Enter a new work order"));
    Display::link_params("search_work_orders.php", _("Select an existing work order"));
    Page::footer_exit();
  }

  if (!isset($_POST['date_'])) {
    $_POST['date_'] = Dates::_newDocDate();
    if (!Dates::_isDateInFiscalYear($_POST['date_'])) {
      $_POST['date_'] = Dates::_endFiscalYear();
    }
  }
  /**
   * @param null $selected_id
   *
   * @return bool
   */
  function can_process(&$selected_id = null) {
    if (!is_null($selected_id)) {
      if (!Ref::is_valid($_POST['wo_ref'])) {
        Event::error(_("You must enter a reference."));
        JS::_setFocus('wo_ref');
        return false;
      }
      if (!Ref::is_new($_POST['wo_ref'], ST_WORKORDER)) {
        $_POST['ref'] = Ref::get_next(ST_WORKORDER);
      }
    }
    if (!Validation::post_num('quantity', 0)) {
      Event::error(_("The quantity entered is invalid or less than zero."));
      JS::_setFocus('quantity');
      return false;
    }
    if (!Dates::_isDate($_POST['date_'])) {
      Event::error(_("The date entered is in an invalid format."));
      JS::_setFocus('date_');
      return false;
    } elseif (!Dates::_isDateInFiscalYear($_POST['date_'])) {
      Event::error(_("The entered date is not in fiscal year."));
      JS::_setFocus('date_');
      return false;
    }
    // only check bom and quantites if quick assembly
    if (!($_POST['type'] == WO_ADVANCED)) {
      if (!WO::has_bom(Input::_post('stock_id'))) {
        Event::error(_("The selected item to manufacture does not have a bom."));
        JS::_setFocus('stock_id');
        return false;
      }
      if ($_POST['Labour'] == "") {
        $_POST['Labour'] = Num::_priceFormat(0);
      }
      if (!Validation::post_num('Labour', 0)) {
        Event::error(_("The labour cost entered is invalid or less than zero."));
        JS::_setFocus('Labour');
        return false;
      }
      if ($_POST['Costs'] == "") {
        $_POST['Costs'] = Num::_priceFormat(0);
      }
      if (!Validation::post_num('Costs', 0)) {
        Event::error(_("The cost entered is invalid or less than zero."));
        JS::_setFocus('Costs');
        return false;
      }
      if (!DB_Company::_get_pref('allow_negative_stock')) {
        if ($_POST['type'] == WO_ASSEMBLY) {
          // check bom if assembling
          $result = WO::get_bom(Input::_post('stock_id'));
          while ($bom_item
            = DB::_fetch($result)) {
            if (WO::has_stock_holding($bom_item["ResourceType"])) {
              $quantity = $bom_item["quantity"] * Validation::input_num('quantity');
              $qoh      = Item::get_qoh_on_date($bom_item["component"], $bom_item["loc_code"], $_POST['date_']);
              if (-$quantity + $qoh < 0) {
                Event::error(
                  _(
                    "The work order cannot be processed because there is an insufficient quantity for component:"
                  ) . " " . $bom_item["component"] . " - " . $bom_item["description"] . ". " . _("Location:") . " " . $bom_item["location_name"]
                );
                JS::_setFocus('quantity');
                return false;
              }
            }
          }
        } elseif ($_POST['type'] == WO_UNASSEMBLY) {
          // if unassembling, check item to unassemble
          $qoh = Item::get_qoh_on_date(Input::_post('stock_id'), $_POST['StockLocation'], $_POST['date_']);
          if (-Validation::input_num('quantity') + $qoh < 0) {
            Event::error(_("The selected item cannot be unassembled because there is insufficient stock."));
            return false;
          }
        }
      }
    } else {
      if (!Dates::_isDate($_POST['RequDate'])) {
        JS::_setFocus('RequDate');
        Event::error(_("The date entered is in an invalid format."));
        return false;
      }
      //elseif (!Dates::_isDateInFiscalYear($_POST['RequDate']))
      //{
      //	Event::error(_("The entered date is not in fiscal year."));
      //	return false;
      //}
      if (isset($selected_id)) {
        $myrow = WO::get($selected_id, true);
        if ($_POST['units_issued'] > Validation::input_num('quantity')) {
          JS::_setFocus('quantity');
          Event::error(_("The quantity cannot be changed to be less than the quantity already manufactured for this order."));
          return false;
        }
      }
    }
    return true;
  }

  if (isset($_POST[ADD_ITEM]) && can_process($selected_id)) {
    if (!isset($_POST['cr_acc'])) {
      $_POST['cr_acc'] = "";
    }
    if (!isset($_POST['cr_lab_acc'])) {
      $_POST['cr_lab_acc'] = "";
    }
    $id = WO::add(
      $_POST['wo_ref'], $_POST['StockLocation'], Validation::input_num('quantity'), Input::_post('stock_id'), $_POST['type'], $_POST['date_'], $_POST['RequDate'], $_POST['memo_'], Validation::input_num('Costs'), $_POST['cr_acc'], Validation::input_num('Labour'), $_POST['cr_lab_acc']
    );
    Dates::_newDocDate($_POST['date_']);
    Display::meta_forward($_SERVER['DOCUMENT_URI'], "AddedID=$id&type=" . $_POST['type'] . "&date=" . $_POST['date_']);
  }
  if (isset($_POST[UPDATE_ITEM]) && can_process($selected_id)) {
    WO::update($selected_id, $_POST['StockLocation'], Validation::input_num('quantity'), Input::_post('stock_id'), $_POST['date_'], $_POST['RequDate'], $_POST['memo_']);
    Dates::_newDocDate($_POST['date_']);
    Display::meta_forward($_SERVER['DOCUMENT_URI'], "UpdatedID=$selected_id");
  }
  if (isset($_POST['delete'])) {
    //the link to delete a selected record was clicked instead of the submit button
    $cancel_delete = false;
    // can't delete it there are productions or issues
    if (WO::has_productions($selected_id) || WO::has_issues($selected_id) || WO::has_payments($selected_id)
    ) {
      Event::error(_("This work order cannot be deleted because it has already been processed."));
      $cancel_delete = true;
    }
    if ($cancel_delete == false) { //ie not cancelled the delete as a result of above tests
      // delete the actual work order
      WO::delete($selected_id);
      Display::meta_forward($_SERVER['DOCUMENT_URI'], "DeletedID=$selected_id");
    }
  }
  if (isset($_POST['close'])) {
    // update the closed flag in the work order
    WO::close($selected_id);
    Display::meta_forward($_SERVER['DOCUMENT_URI'], "ClosedID=$selected_id");
  }
  if (Input::_post('_type_update')) {
    Ajax::_activate('_page_body');
  }
  Forms::start();
  Table::start('standard');
  $existing_comments = "";
  $dec               = 0;
  if (isset($selected_id)) {
    $myrow = WO::get($selected_id);
    if (strlen($myrow[0]) == 0) {
      echo _("The order number sent is not valid.");
      safe_exit();
    }
    // if it's a closed work order can't edit it
    if ($myrow["closed"] == 1) {
      echo "<div class='center'>";
      Event::error(_("This work order is closed and cannot be edited."));
      safe_exit();
    }
    $_POST['wo_ref']        = $myrow["wo_ref"];
    $_POST['stock_id']      = $myrow["stock_id"];
    $_POST['quantity']      = Item::qty_format($myrow["units_reqd"], Input::_post('stock_id'), $dec);
    $_POST['StockLocation'] = $myrow["loc_code"];
    $_POST['released']      = $myrow["released"];
    $_POST['closed']        = $myrow["closed"];
    $_POST['type']          = $myrow["type"];
    $_POST['date_']         = Dates::_sqlToDate($myrow["date_"]);
    $_POST['RequDate']      = Dates::_sqlToDate($myrow["required_by"]);
    $_POST['released_date'] = Dates::_sqlToDate($myrow["released_date"]);
    $_POST['memo_']         = "";
    $_POST['units_issued']  = $myrow["units_issued"];
    $_POST['Costs']         = Num::_priceFormat($myrow["additional_costs"]);
    $_POST['memo_']         = DB_Comments::get_string(ST_WORKORDER, $selected_id);
    Forms::hidden('wo_ref', $_POST['wo_ref']);
    Forms::hidden('units_issued', $_POST['units_issued']);
    Forms::hidden('released', $_POST['released']);
    Forms::hidden('released_date', $_POST['released_date']);
    Forms::hidden('selected_id', $selected_id);
    Forms::hidden('old_qty', $myrow["units_reqd"]);
    Forms::hidden('old_stk_id', $myrow["stock_id"]);
    Table::label(_("Reference:"), $_POST['wo_ref']);
    Table::label(_("Type:"), WO::$types[$_POST['type']]);
    Forms::hidden('type', $myrow["type"]);
  } else {
    $_POST['units_issued'] = $_POST['released'] = 0;
    Forms::refRow(_("Reference:"), 'wo_ref', '', Ref::get_next(ST_WORKORDER));
    WO_Types::row(_("Type:"), 'type', null);
  }
  if (Input::_post('released')) {
    Forms::hidden('stock_id', Input::_post('stock_id'));
    Forms::hidden('StockLocation', $_POST['StockLocation']);
    Forms::hidden('type', $_POST['type']);
    Table::label(_("Item:"), $myrow["StockItemName"]);
    Table::label(_("Destination Location:"), $myrow["location_name"]);
  } else {
    Item_UI::manufactured_row(_("Item:"), 'stock_id', null, false, true);
    if (Forms::isListUpdated('stock_id')) {
      Ajax::_activate('quantity');
    }
    Inv_Location::row(_("Destination Location:"), 'StockLocation', null);
  }
  if (!isset($_POST['quantity'])) {
    $_POST['quantity'] = Item::qty_format(1, Input::_post('stock_id'), $dec);
  } else {
    $_POST['quantity'] = Item::qty_format($_POST['quantity'], Input::_post('stock_id'), $dec);
  }
  if (Input::_post('type') == WO_ADVANCED) {
    Forms::qtyRow(_("Quantity Required:"), 'quantity', null, null, null, $dec);
    if ($_POST['released']) {
      Table::label(_("Quantity Manufactured:"), number_format($_POST['units_issued'], Item::qty_dec(Input::_post('stock_id'))));
    }
    Forms::dateRow(_("Date") . ":", 'date_', '', true);
    Forms::dateRow(_("Date Required By") . ":", 'RequDate', '', null, DB_Company::_get_pref('default_workorder_required'));
  } else {
    Forms::qtyRow(_("Quantity:"), 'quantity', null, null, null, $dec);
    Forms::dateRow(_("Date") . ":", 'date_', '', true);
    Forms::hidden('RequDate', '');
    $sql = "SELECT DISTINCT account_code FROM bank_accounts";
    $rs  = DB::_query($sql, "could not get bank accounts");
    $r   = DB::_fetchRow($rs);
    if (!isset($_POST['Labour'])) {
      $_POST['Labour']     = Num::_priceFormat(0);
      $_POST['cr_lab_acc'] = $r[0];
    }
    Forms::AmountRow(WO_Cost::$types[WO_LABOUR], 'Labour');
    GL_UI::all_row(_("Credit Labour Account"), 'cr_lab_acc', null);
    if (!isset($_POST['Costs'])) {
      $_POST['Costs']  = Num::_priceFormat(0);
      $_POST['cr_acc'] = $r[0];
    }
    Forms::AmountRow(WO_Cost::$types[WO_OVERHEAD], 'Costs');
    GL_UI::all_row(_("Credit Overhead Account"), 'cr_acc', null);
  }
  if (Input::_post('released')) {
    Table::label(_("Released On:"), $_POST['released_date']);
  }
  Forms::textareaRow(_("Memo:"), 'memo_', null, 40, 5);
  Table::end(1);
  if (isset($selected_id)) {
    echo "<table class=center><tr>";
    Forms::submitCells(UPDATE_ITEM, _("Update"), '', _('Save changes to work order'), 'default');
    if (Input::_post('released')) {
      Forms::submitCells('close', _("Close This Work Order"), '', '', true);
    }
    Forms::submitCells('delete', _("Delete This Work Order"), '', '', true);
    echo "</tr></table>";
  } else {
    Forms::submitCenter(ADD_ITEM, _("Add Workorder"), true, '', 'default');
  }
  Forms::end();
  Page::end();


