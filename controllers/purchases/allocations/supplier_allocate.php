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
  JS::_footerFile('/js/allocate.js');
  Page::start(_($help_context = "Allocate Supplier Payment or Credit Note"), SA_SUPPLIERALLOC);

  if (isset($_POST['Process'])) {
    if (GL_Allocation::check()) {
      $_SESSION['alloc']->write();
      clear_allocations();
      $_POST['Cancel'] = 1;
    }
  }
  if (isset($_POST['Cancel'])) {
    clear_allocations();
    $forward = (isset($_POST['inquiry'])) ? "/purchases/search/allocations" : "/purchases/allocations/supplier_allocation_main.php";
    Display::meta_forward($forward);
  }
  if (isset($_GET['trans_no']) && isset($_GET['trans_type'])) {
    $_SESSION['alloc'] = new GL_Allocation($_GET['trans_type'], $_GET['trans_no']);
  }
  if (Input::_post('UpdateDisplay')) {
    $_SESSION['alloc']->read();
    Ajax::_activate('alloc_tbl');
  }
  if (isset($_SESSION['alloc'])) {
    edit_allocations_for_transaction($_SESSION['alloc']->type, $_SESSION['alloc']->trans_no);
  }
  Page::end();
  function clear_allocations() {
    if (isset($_SESSION['alloc'])) {
      unset($_SESSION['alloc']->allocs);
      unset($_SESSION['alloc']);
    }
  }

  /**
   * @param $type
   * @param $trans_no
   */
  function edit_allocations_for_transaction($type, $trans_no) {

    Forms::start();
    if (isset($_POST['inquiry']) || stristr($_SERVER['HTTP_REFERER'], 'supplier_allocation_inquiry.php')) {
      Forms::hidden('inquiry', true);
    }
    Display::heading(_("Allocation of") . " " . SysTypes::$names[$_SESSION['alloc']->type] . " # " . $_SESSION['alloc']->trans_no);
    Display::heading($_SESSION['alloc']->person_name);
    Display::heading(_("Date:") . " <span class='bold'>" . $_SESSION['alloc']->date_ . "</span>");
    Display::heading(_("Total:") . " <span class='bold'>" . Num::_priceFormat(-$_SESSION['alloc']->amount) . "</span>");
    echo "<br>";
    Ajax::_start_div('alloc_tbl');
    if (count($_SESSION['alloc']->allocs) > 0) {
      GL_Allocation::show_allocatable(true);
      Forms::submitCenterBegin('UpdateDisplay', _("Refresh"), _('Start again allocation of selected amount'), true);
      Forms::submit('Process', _("Process"), true, _('Process allocations'), 'default');
      Forms::submitCenterEnd('Cancel', _("Back to Allocations"), _('Abandon allocations and return to selection of allocatable amounts'), 'cancel');
    } else {
      Event::warning(_("There are no unsettled transactions to allocate."), 0, 1);
      Forms::submitCenter('Cancel', _("Back to Allocations"), true, _('Abandon allocations and return to selection of allocatable amounts'), 'cancel');
    }
    Ajax::_end_div();
    Forms::end();
  }


