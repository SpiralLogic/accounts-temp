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
  Page::start(_($help_context = "Work Order Release to Manufacturing"), SA_MANUFRELEASE);
  if (isset($_GET["trans_no"])) {
    $selected_id = $_GET["trans_no"];
  } elseif (isset($_POST["selected_id"])) {
    $selected_id = $_POST["selected_id"];
  } else {
    Event::warning("This page must be called with a work order reference");
    exit;
  }
  /**
   * @param $myrow
   *
   * @return bool
   */
  function can_process($myrow) {
    if ($myrow['released']) {
      Event::error(_("This work order has already been released."));
      JS::_setFocus('released');
      return false;
    }
    // make sure item has components
    if (!WO::has_bom($myrow['stock_id'])) {
      Event::error(_("This Work Order cannot be released. The selected item to manufacture does not have a bom."));
      JS::_setFocus('stock_id');
      return false;
    }
    return true;
  }

  if (isset($_POST['release'])) {
    WO::release($selected_id, $_POST['released_date'], $_POST['memo_']);
    Event::notice(_("The work order has been released to manufacturing."));
    Display::note(GL_UI::viewTrans(ST_WORKORDER, $selected_id, _("View this Work Order")));
    Display::link_params("search_work_orders.php", _("Select another &work order"));
    Ajax::_activate('_page_body');
    Page::end();
    exit;
  }
  Forms::start();
  $myrow             = WO::get($selected_id);
  $_POST['released'] = $myrow["released"];
  $_POST['memo_']    = "";
  if (can_process($myrow)) {
    Table::start('standard');
    Table::label(_("Work Order #:"), $selected_id);
    Table::label(_("Work Order Reference:"), $myrow["wo_ref"]);
    Forms::dateRow(_("Released Date") . ":", 'released_date');
    Forms::textareaRow(_("Memo:"), 'memo_', $_POST['memo_'], 40, 5);
    Table::end(1);
    Forms::submitCenter('release', _("Release Work Order"), true, '', 'default');
    Forms::hidden('selected_id', $selected_id);
    Forms::hidden('stock_id', $myrow['stock_id']);
  }
  Forms::end();
  Page::end();

