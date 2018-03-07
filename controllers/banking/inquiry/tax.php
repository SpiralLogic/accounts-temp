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
  JS::_setFocus('account');
  JS::_openWindow(950, 500);
  Page::start(_($help_context = "Tax Inquiry"), SA_TAXREP);
  // Ajax updates
  //
  if (Input::_post('Show')) {
    Ajax::_activate('trans_tbl');
  }
  if (Input::_post('TransFromDate') == "" && Input::_post('TransToDate') == "") {
    $date                   = Dates::_today();
    $row                    = DB_Company::_get_prefs();
    $edate                  = Dates::_addMonths($date, -$row['tax_last']);
    $edate                  = Dates::_endMonth($edate);
    $bdate                  = Dates::_beginMonth($edate);
    $bdate                  = Dates::_addMonths($bdate, -$row['tax_prd'] + 1);
    $_POST["TransFromDate"] = $bdate;
    $_POST["TransToDate"]   = $edate;
  }
  tax_inquiry_controls();
  show_results();
  Page::end();
  /** **/
  function tax_inquiry_controls() {
    Forms::start();
    //Table::start('standard');
    Table::start('noborder');
    echo '<tr>';
    Forms::dateCells(_("from:"), 'TransFromDate', '', null, -30);
    Forms::dateCells(_("to:"), 'TransToDate');
    Forms::submitCells('Show', _("Show"), '', '', 'default');
    echo '</tr>';
    Table::end();
    Forms::end();
  }

  /** **/
  function show_results() {
    /*Now get the transactions */
    Ajax::_start_div('trans_tbl');
    Table::start('padded grid');
    $th = array(_("Type"), _("Description"), _("Amount"), _("Outputs") . "/" . _("Inputs"));
    Table::header($th);
    $k     = 0;
    $total = 0;
    $bdate = Dates::_dateToSql($_POST['TransFromDate']);
    $edate = Dates::_dateToSql($_POST['TransToDate']);
    $taxes = GL_Trans::get_tax_summary($_POST['TransFromDate'], $_POST['TransToDate']);
    while ($tx = DB::_fetch($taxes)) {
      $payable     = $tx['payable'];
      $collectible = $tx['collectible'];
      $net         = $collectible + $payable;
      $total += $net;
      Cell::label($tx['name'] . " " . $tx['rate'] . "%");
      Cell::label(_("Charged on sales") . " (" . _("Output Tax") . "):");
      Cell::amount($payable);
      Cell::amount($tx['net_output']);
      echo '</tr>';
      Cell::label($tx['name'] . " " . $tx['rate'] . "%");
      Cell::label(_("Paid on purchases") . " (" . _("Input Tax") . "):");
      Cell::amount($collectible);
      Cell::amount($tx['net_input']);
      echo '</tr>';
      Cell::label("<span class='bold'>" . $tx['name'] . " " . $tx['rate'] . "%</span>");
      Cell::label("<span class='bold'>" . _("Net payable or collectible") . ":</span>");
      Cell::amount($net, true);
      Cell::label("");
      echo '</tr>';
    }
    Cell::label("");
    Cell::label("<span class='bold'>" . _("Total payable or refund") . ":</span>");
    Cell::amount($total, true);
    Cell::label("");
    echo '</tr>';
    Table::end(2);
    Ajax::_end_div();
  }
