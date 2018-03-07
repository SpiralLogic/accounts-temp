<?php
  use ADV\Core\Input\Input;
  use ADV\Core\DB\DB;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  JS::_openWindow(950, 500);
  Page::start(_($help_context = "Customer Allocation Inquiry"), SA_SALESALLOC);
  if (Input::_get('id')) {
    $_POST['debtor_id'] = Input::_get('id', Input::NUMERIC);
  }
  if (isset($_GET['frame'])) {
    foreach ($_GET as $k => $v) {
      $_POST[$k] = $v;
    }
  }
  Forms::start(false, '', 'invoiceForm');
  Table::start('noborder');
  echo '<tr>';
  if (!Input::_get('frame')) {
    Debtor::newselect(null, ['label' => 'Customer:', 'row' => false]);
  }
  Session::_setGlobal('debtor_id', $_POST['debtor_id']);
  if (!isset($_POST['TransAfterDate']) && Session::_getGlobal('TransAfterDate')) {
    $_POST['TransAfterDate'] = Session::_getGlobal('TransAfterDate');
  } elseif (isset($_POST['TransAfterDate'])) {
    Session::_setGlobal('TransAfterDate', $_POST['TransAfterDate']);
  }
  if (!isset($_POST['TransToDate']) && Session::_getGlobal('TransToDate')) {
    $_POST['TransToDate'] = Session::_getGlobal('TransToDate');
  } elseif (isset($_POST['TransToDate'])) {
    Session::_setGlobal('TransToDate', $_POST['TransToDate']);
  }
  Forms::dateCells(_("from:"), 'TransAfterDate', '', null, -31, -12);
  Forms::dateCells(_("to:"), 'TransToDate', '', null, 1);
  Debtor_Payment::allocations_select(_("Type:"), 'filterType', null);
  Forms::checkCells(" " . _("show settled:"), 'showSettled', null);
  Forms::submitCells('RefreshInquiry', _("Search"), '', _('Refresh Inquiry'), 'default');
  echo '</tr>';
  Table::end();
  $data_after = Dates::_dateToSql($_POST['TransAfterDate']);
  $date_to    = Dates::_dateToSql($_POST['TransToDate']);
  $sql        = "SELECT ";
  if (Input::_get('frame')) {
    $sql .= " IF(trans.type=" . ST_SALESINVOICE . ",0,1), ";
  }
  $sql .= " trans.type,
		trans.trans_no,
		trans.reference,
		trans.order_,
		trans.tran_date,
		trans.due_date,
		debtor.name,
		debtor.curr_code,
 	(trans.ov_amount + trans.ov_gst + trans.ov_freight			+ trans.ov_freight_tax + trans.ov_discount)	AS TotalAmount,
	trans.alloc AS credit,
	trans.alloc AS Allocated,
		((trans.type = " . ST_SALESINVOICE . ") AND trans.due_date < '" . Dates::_today(true) . "') AS OverDue
 	FROM debtor_trans as trans, debtors as debtor
 	WHERE debtor.debtor_id = trans.debtor_id
			AND round(trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount,2) != 0
 		AND trans.tran_date >= '$data_after'
 		AND trans.tran_date <= '$date_to'";
  if (Input::_post('debtor_id', Input::NUMERIC)) {
    $sql .= " AND trans.debtor_id = " . DB::_quote($_POST['debtor_id']);
  }
  if (isset($_POST['filterType']) && $_POST['filterType'] != ALL_TEXT) {
    if ($_POST['filterType'] == '1' || $_POST['filterType'] == '2') {
      $sql .= " AND trans.type = " . ST_SALESINVOICE . " ";
    } elseif ($_POST['filterType'] == '3') {
      $sql .= " AND (trans.type = " . ST_CUSTPAYMENT . " OR trans.type = " . ST_CUSTREFUND . ")";
    } elseif ($_POST['filterType'] == '4') {
      $sql .= " AND trans.type = " . ST_CUSTCREDIT . " ";
    }
    if ($_POST['filterType'] == '2') {
      $today = Dates::_today(true);
      $sql .= " AND trans.due_date < '$today'
				AND (round(abs(trans.ov_amount + " . "trans.ov_gst + trans.ov_freight + " . "trans.ov_freight_tax + trans.ov_discount) - trans.alloc,2) > 0) ";
    }
  } else {
    $sql .= " AND trans.type <> " . ST_CUSTDELIVERY . " ";
  }
  if (!Input::_hasPost('showSettled')) {
    $sql .= " AND (round(abs(trans.ov_amount + trans.ov_gst + " . "trans.ov_freight + trans.ov_freight_tax + " . "trans.ov_discount) - trans.alloc,2) > 0) ";
  }
  $cols = array(
    "<button id='emailInvoices'>Email</button> " => array(
      'fun'   => function ($row) {
        return ($row['type'] == ST_SALESINVOICE) ? Forms::checkbox(null, 'emailChk') : '';
      },
      'align' => 'center'
    ),
    _("Type")                                    => array(
      'fun' => function ($dummy, $type) {
        return SysTypes::$names[$type];
      }
    ),
    _("#")                                       => array(
      'fun' => function ($trans) {
        return GL_UI::viewTrans($trans["type"], $trans["trans_no"]);
      }
    ),
    _("Reference"),
    _("Order")                                   => array(
      'fun' => function ($row) {
        return $row['order_'] > 0 ? Debtor::viewTrans(ST_SALESORDER, $row['order_']) : "";
      }
    ),
    _("Date")                                    => array(
      'name' => 'tran_date',
      'type' => 'date',
      'ord'  => 'asc'
    ),
    _("Due Date")                                => array(
      'type' => 'date',
      'fun'  => function ($row) {
        return $row["type"] == 10 ? $row["due_date"] : '';
      }
    ),
    _("Customer")                                => [],
    _("Currency")                                => array('align' => 'center'),
    _("Debit")                                   => array(
      'align' => 'right',
      'fun'   => function ($row) {
        $value = $row['type'] == ST_CUSTCREDIT || $row['type'] == ST_CUSTPAYMENT || $row['type'] == ST_CUSTREFUND || $row['type'] == ST_BANKDEPOSIT ? -$row["TotalAmount"] : $row["TotalAmount"];
        return $value >= 0 ? Num::_priceFormat($value) : '';
      }
    ),
    _("Credit")                                  => array(
      'align' => 'right',
      'fun'   => function ($row) {
        $value = !($row['type'] == ST_CUSTCREDIT || $row['type'] == ST_CUSTPAYMENT || $row['type'] == ST_CUSTREFUND || $row['type'] == ST_BANKDEPOSIT) ? -$row["TotalAmount"] : $row["TotalAmount"];
        return $value > 0 ? Num::_priceFormat($value) : '';
      }
    ),
    _("Allocated")                               => 'amount',
    _("overdue")                                 => array('type' => 'skip'),
    _("Balance")                                 => array(
      'type'   => 'amount',
      'insert' => true,
      'fun'    => function ($row) {
        return $row["TotalAmount"] - $row["Allocated"];
      }
    ),
    array(
      'insert' => true,
      'fun'    => function ($row) {
        $link = Display::link_button(_("Allocation"), "/sales/allocations/customer_allocate.php?trans_no=" . $row["trans_no"] . "&trans_type=" . $row["type"], ICON_MONEY);
        if ($row["type"] == ST_CUSTCREDIT && Num::_priceFormat($row['TotalAmount'] - $row['Allocated']) > 0) {
          /*its a credit note which could have an allocation */
          return $link;
        } elseif (($row["type"] == ST_CUSTPAYMENT || $row["type"] == ST_CUSTREFUND || $row["type"] == ST_BANKDEPOSIT) && Num::_priceFormat(
          $row['TotalAmount'] - $row['Allocated']
        ) > 0
        ) {
          /*its a receipt which could have an allocation*/
          return $link;
        } elseif ($row["type"] == ST_CUSTPAYMENT || $row["type"] == ST_CUSTREFUND && Num::_priceFormat($row['TotalAmount']) < 0) {
          /*its a negative receipt */
          return '';
        }
      }
    )
  );
  if (Input::_post('debtor_id')) {
    $cols[_("Customer")] = 'skip';
  }
  if (!Input::_get('frame')) {
    array_shift($cols);
  }
  $table = \ADV\App\Pager\Pager::newPager('doc_tbl', $cols);
  $table->setData($sql);
  $table->rowFunction = function ($row) {
    if ($row['OverDue'] == 1 && \ADV\Core\Num::_priceFormat(abs($row["TotalAmount"]) - $row["Allocated"])) {
      return "class='settledbg'";
    }
  };
  \ADV\Core\Event::warning(_("Marked items are overdue."), false);
  $table->width = "85%";
  $table->display($table);
  Forms::end();
  $action = <<<JS

$('#invoiceForm').find(':checkbox').each(function(){\$this =\$(this);\$this.prop('checked',!\$this.prop('checked'))});
return false;
JS;
  JS::_addLiveEvent('#emailInvoices', 'dblclick', $action, 'wrapper', true);
  JS::_addLiveEvent('#emailInvoices', 'click', 'return false;', 'wrapper', true);
  Page::end();
