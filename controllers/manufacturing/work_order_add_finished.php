<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  JS::_openWindow(950, 500);
  Page::start(_($help_context = "Produce or Unassemble Finished Items From Work Order"), SA_MANUFRECEIVE);
  if (isset($_GET['trans_no']) && $_GET['trans_no'] != "") {
    $_POST['selected_id'] = $_GET['trans_no'];
  }
  if (isset($_GET[ADDED_ID])) {
    $id    = $_GET[ADDED_ID];
    $stype = ST_WORKORDER;
    Event::success(_("The manufacturing process has been entered."));
    Display::note(GL_UI::viewTrans($stype, $id, _("View this Work Order")));
    Display::note(GL_UI::view($stype, $id, _("View the GL Journal Entries for this Work Order")), 1);
    $ar = array(
      'PARAM_0' => $_GET['date'],
      'PARAM_1' => $_GET['date'],
      'PARAM_2' => $stype
    );
    Display::note(Reporting::print_link(_("Print the GL Journal Entries for this Work Order"), 702, $ar), 1);
    Display::link_params("search_work_orders.php", _("Select another &Work Order to Process"));
    Page::end();
    exit;
  }
  $wo_details = WO::get($_POST['selected_id']);
  if (strlen($wo_details[0]) == 0) {
    Event::error(_("The order number sent is not valid."));
    exit;
  }
  /**
   * @return bool
   */
  function can_process() {
    global $wo_details;
    if (!Ref::is_valid($_POST['ref'])) {
      Event::error(_("You must enter a reference."));
      JS::_setFocus('ref');
      return false;
    }
    if (!Ref::is_new($_POST['ref'], ST_MANURECEIVE)) {
      $_POST['ref'] = Ref::get_next(ST_MANURECEIVE);
    }
    if (!Validation::post_num('quantity', 0)) {
      Event::error(_("The quantity entered is not a valid number or less then zero."));
      JS::_setFocus('quantity');
      return false;
    }
    if (!Dates::_isDate($_POST['date_'])) {
      Event::error(_("The entered date is invalid."));
      JS::_setFocus('date_');
      return false;
    } elseif (!Dates::_isDateInFiscalYear($_POST['date_'])) {
      Event::error(_("The entered date is not in fiscal year."));
      JS::_setFocus('date_');
      return false;
    }
    if (Dates::_differenceBetween(Dates::_sqlToDate($wo_details["released_date"]), $_POST['date_'], "d") > 0) {
      Event::error(_("The production date cannot be before the release date of the work order."));
      JS::_setFocus('date_');
      return false;
    }
    // if unassembling we need to check the qoh
    if (($_POST['ProductionType'] == 0) && !DB_Company::_get_pref('allow_negative_stock')) {
      $wo_details = WO::get($_POST['selected_id']);
      $qoh        = Item::get_qoh_on_date($wo_details["stock_id"], $wo_details["loc_code"], $_POST['date_']);
      if (-Validation::input_num('quantity') + $qoh < 0) {
        Event::error(_("The unassembling cannot be processed because there is insufficient stock."));
        JS::_setFocus('quantity');
        return false;
      }
    }
    // if production we need to check the qoh of the wo requirements
    if (($_POST['ProductionType'] == 1) && !DB_Company::_get_pref('allow_negative_stock')) {
      $err    = false;
      $result = WO_Requirements::get($_POST['selected_id']);
      while ($row = DB::_fetch($result)) {
        if ($row['mb_flag'] == 'D') // service, non stock
        {
          continue;
        }
        $qoh = Item::get_qoh_on_date($row["stock_id"], $row["loc_code"], $_POST['date_']);
        if ($qoh - $row['units_req'] * Validation::input_num('quantity') < 0) {
          Event::error(
            _("The production cannot be processed because a required item would cause a negative inventory balance :") . " " . $row['stock_id'] . " - " . $row['description']
          );
          $err = true;
        }
      }
      if ($err) {
        JS::_setFocus('quantity');
        return false;
      }
    }
    return true;
  }

  if ((isset($_POST['Process']) || isset($_POST['ProcessAndClose'])) && can_process() == true) {
    $close_wo = 0;
    if (isset($_POST['ProcessAndClose']) && ($_POST['ProcessAndClose'] != "")) {
      $close_wo = 1;
    }
    // if unassembling, negate quantity
    if ($_POST['ProductionType'] == 0) {
      $_POST['quantity'] = -$_POST['quantity'];
    }
    $id = WO_Produce::add($_POST['selected_id'], $_POST['ref'], Validation::input_num('quantity'), $_POST['date_'], $_POST['memo_'], $close_wo);
    Display::meta_forward($_SERVER['DOCUMENT_URI'], "AddedID=" . $_POST['selected_id'] . "&date=" . $_POST['date_']);
  }
  WO_Cost::display($_POST['selected_id']);
  Forms::start();
  Forms::hidden('selected_id', $_POST['selected_id']);
  //Forms::hidden('WOReqQuantity', $_POST['WOReqQuantity']);
  $dec = Item::qty_dec($wo_details["stock_id"]);
  if (!isset($_POST['quantity']) || $_POST['quantity'] == '') {
    $_POST['quantity'] = Item::qty_format(max($wo_details["units_reqd"] - $wo_details["units_issued"], 0), $wo_details["stock_id"], $dec);
  }
  Table::start('standard');
  echo "<br>";
  Forms::refRow(_("Reference:"), 'ref', '', Ref::get_next(ST_MANURECEIVE));
  if (!isset($_POST['ProductionType'])) {
    $_POST['ProductionType'] = 1;
  }
  Forms::yesnoListRow(_("Type:"), 'ProductionType', $_POST['ProductionType'], _("Produce Finished Items"), _("Return Items to Work Order"));
  Forms::qtyRowSmall(_("Quantity:"), 'quantity', null, null, null, $dec);
  Forms::dateRow(_("Date:"), 'date_');
  Forms::textareaRow(_("Memo:"), 'memo_', null, 40, 3);
  Table::end(1);
  Forms::submitCenterBegin('Process', _("Process"), '', 'default');
  Forms::submitCenterEnd('ProcessAndClose', _("Process And Close Order"), '', true);
  Forms::end();
  Page::end();

