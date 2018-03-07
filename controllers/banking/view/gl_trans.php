<?php
  use ADV\App\Dimensions;
  use ADV\Core\Cell;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_($help_context = "General Ledger Transaction Details"), SA_GLTRANSVIEW, true);
  if (!isset($_GET['type_id']) || !isset($_GET['trans_no'])) { /*Script was not passed the correct parameters */
    echo "<p>" . _("The script must be called with a valid transaction type and transaction number to review the general ledger postings for.") . "</p>";
    exit;
  }
  $sql
          = "SELECT gl.*, cm.account_name, IF(ISNULL(refs.reference), '', refs.reference) AS reference FROM gl_trans as gl
	LEFT JOIN chart_master as cm ON gl.account = cm.account_code
	LEFT JOIN refs as refs ON (gl.type=refs.type AND gl.type_no=refs.id)" . " WHERE gl.type= " . DB::_quote($_GET['type_id']) . " AND gl.type_no = " . DB::_quote(
    $_GET['trans_no']
  ) . " ORDER BY counter";
  $result = DB::_query($sql, "could not get transactions");
  //alert("sql = ".$sql);
  if (DB::_numRows($result) == 0) {
    echo "<p><div class='center'>" . _("No general ledger transactions have been created for") . " " . SysTypes::$names[$_GET['type_id']] . " " . _(
      "number"
    ) . " " . $_GET['trans_no'] . "</div></p><br><br>";
    Page::end(true);
    exit;
  }
  /*show a table of the transactions returned by the sql */
  $dim = DB_Company::_get_pref('use_dimension');
  if ($dim == 2) {
    $th = array(
      _("Account Code"),
      _("Account Name"),
      _("Dimension") . " 1",
      _("Dimension") . " 2",
      _("Debit"),
      _("Credit"),
      _("Memo")
    );
  } else {
    if ($dim == 1) {
      $th = array(
        _("Account Code"),
        _("Account Name"),
        _("Dimension"),
        _("Debit"),
        _("Credit"),
        _("Memo")
      );
    } else {
      $th = array(
        _("Account Code"),
        _("Account Name"),
        _("Debit"),
        _("Credit"),
        _("Memo")
      );
    }
  }
  $k             = 0; //row colour counter
  $heading_shown = false;
  $total         = 0;
  while ($myrow = DB::_fetch($result)) {
    if ($myrow['amount'] == 0) {
      continue;
    }
    $total += $myrow['amount'];
    if (!$heading_shown) {
      display_gl_heading($myrow);
      Table::start('padded grid width95');
      Table::header($th);
      $heading_shown = true;
    }
    Cell::label($myrow['account']);
    Cell::label($myrow['account_name']);
    if ($dim >= 1) {
      Cell::label(Dimensions::get_string($myrow['dimension_id'], true));
    }
    if ($dim > 1) {
      Cell::label(Dimensions::get_string($myrow['dimension2_id'], true));
    }
    Cell::debitOrCredit($myrow['amount']);
    Cell::label($myrow['memo_']);
    echo '</tr>';
  }
  //end of while loop
  if ($heading_shown) {
    Table::end(1);
  }
  Voiding::is_voided($_GET['type_id'], $_GET['trans_no'], _("This transaction has been voided."));
  Page::end(true);
  /**
   * @param $myrow
   */
  function display_gl_heading($myrow) {
    $trans_name = SysTypes::$names[$_GET['type_id']];
    Table::start('padded width95');
    $th = array(
      _("General Ledger Transaction Details"),
      _("Reference"),
      _("Date"),
      _("Person/Item")
    );
    Table::header($th);
    echo '<tr>';
    Cell::label("$trans_name #" . $_GET['trans_no']);
    Cell::label($myrow["reference"]);
    Cell::label(Dates::_sqlToDate($myrow["tran_date"]));
    Cell::label(Bank::payment_person_name($myrow["person_type_id"], $myrow["person_id"]));
    echo '</tr>';
    DB_Comments::display_row($_GET['type_id'], $_GET['trans_no']);
    Table::end(1);
  }
