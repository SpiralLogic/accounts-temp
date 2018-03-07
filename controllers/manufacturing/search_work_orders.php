<?php
  use ADV\Core\Input\Input;
  use ADV\Core\Event;
  use ADV\App\Dates;
  use ADV\Core\DB\DB;
  use ADV\Core\Table;
  use ADV\Core\Ajax;
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
  if (isset($_GET['outstanding_only']) && ($_GET['outstanding_only'] == true)) {
    // curently outstanding simply means not closed
    $outstanding_only = 1;
    Page::start(_($help_context = "Search Outstanding Work Orders"), SA_MANUFTRANSVIEW);
  } else {
    $outstanding_only = 0;
    Page::start(_($help_context = "Search Work Orders"), SA_MANUFTRANSVIEW);
  }
  // Ajax updates
  //
  if (Input::_post('SearchOrders')) {
    Ajax::_activate('orders_tbl');
  } elseif (Input::_post('_OrderNumber_changed')) {
    $disable = Input::_post('OrderNumber') !== '';
    Ajax::_addDisable(true, 'StockLocation', $disable);
    Ajax::_addDisable(true, 'OverdueOnly', $disable);
    Ajax::_addDisable(true, 'OpenOnly', $disable);
    Ajax::_addDisable(true, 'SelectedStockItem', $disable);
    if ($disable) {
      JS::_setFocus('OrderNumber');
    } else {
      JS::_setFocus('StockLocation');
    }
    Ajax::_activate('orders_tbl');
  }
  if (isset($_GET["stock_id"])) {
    $_POST['SelectedStockItem'] = $_GET["stock_id"];
  }
  Forms::start(false, $_SERVER['DOCUMENT_URI'] . "?outstanding_only=$outstanding_only");
  Table::start('noborder');
  echo '<tr>';
  Forms::refCells(_("Reference:"), 'OrderNumber', '', null, '', true);
  Inv_Location::cells(_("at Location:"), 'StockLocation', null, true);
  Forms::checkCells(_("Only Overdue:"), 'OverdueOnly', null);
  if ($outstanding_only == 0) {
    Forms::checkCells(_("Only Open:"), 'OpenOnly', null);
  }
  Item_UI::manufactured_cells(_("for item:"), 'SelectedStockItem', null, true);
  Forms::submitCells('SearchOrders', _("Search"), '', _('Select documents'), 'default');
  echo '</tr>';
  Table::end();
  /**
   * @param $row
   *
   * @return bool
   */
  /**
   * @param $dummy
   * @param $order_no
   *
   * @return null|string
   */
  function view_link($dummy, $order_no) {
    return GL_UI::viewTrans(ST_WORKORDER, $order_no);
  }

  /**
   * @param $row
   *
   * @return string
   */
  function view_stock($row) {
    return Item_UI::status($row["stock_id"], $row["description"], false);
  }

  /**
   * @param $dummy
   * @param $type
   *
   * @return mixed
   */
  function wo_type_name($dummy, $type) {
    return WO::$types[$type];
  }

  /**
   * @param $row
   *
   * @return string
   */
  function edit_link($row) {
    return $row['closed'] ? '<i>' . _('Closed') . '</i>' : Display::link_button(_("Edit"), "/manufacturing/work_order_entry.php?trans_no=" . $row["id"], ICON_EDIT);
  }

  /**
   * @param $row
   *
   * @return string
   */
  function release_link($row) {
    return $row["closed"] ? '' : ($row["released"] == 0 ? Display::link_button(_('Release'), "/manufacturing/work_order_release.php?trans_no=" . $row["id"]) : Display::link_button(_('Issue'), "/manufacturing/work_order_issue.php?trans_no=" . $row["id"]));
  }

  /**
   * @param $row
   *
   * @return string
   */
  function produce_link($row) {
    return $row["closed"] || !$row["released"] ? '' : Display::link_button(_('Produce'), "/manufacturing/work_order_add_finished.php?trans_no=" . $row["id"]);
  }

  /**
   * @param $row
   *
   * @return string
   */
  function costs_link($row) {
    /*

                           return $row["closed"] || !$row["released"] ? '' :
                             Display::link_button(_('Costs'),
                               "/banking/banking?NewPayment=1&PayType="
                               .PT_WORKORDER. "&PayPerson=" .$row["id"]);
                         */
    return $row["closed"] || !$row["released"] ? '' : Display::link_button(_('Costs'), "/manufacturing/work_order_costs.php?trans_no=" . $row["id"]);
  }

  /**
   * @param $row
   *
   * @return string
   */
  function view_gl_link($row) {
    if ($row['closed'] == 0) {
      return '';
    }
    return GL_UI::view(ST_WORKORDER, $row['id']);
  }

  /**
   * @param $row
   * @param $amount
   *
   * @return int|string
   */
  function dec_amount($row, $amount) {
    return Num::_format($amount, $row['decimals']);
  }

  $sql = "SELECT
    workorder.id,
    workorder.wo_ref,
    workorder.type,
    location.location_name,
    item.description,
    workorder.units_reqd,
    workorder.units_issued,
    workorder.date_,
    workorder.required_by,
    workorder.released_date,
    workorder.closed,
    workorder.released,
    workorder.stock_id,
    unit.decimals
    FROM workorders as workorder," . "stock_master as item," . "item_units as unit," . "locations as location
    WHERE workorder.stock_id=item.stock_id
        AND workorder.loc_code=location.loc_code
        AND item.units=unit.abbr";
  if (Input::_hasPost('OpenOnly') || $outstanding_only != 0) {
    $sql .= " AND workorder.closed=0";
  }
  if (isset($_POST['StockLocation']) && $_POST['StockLocation'] != ALL_TEXT) {
    $sql .= " AND workorder.loc_code=" . DB::_quote($_POST['StockLocation']);
  }
  if (isset($_POST['OrderNumber']) && $_POST['OrderNumber'] != "") {
    $sql .= " AND workorder.wo_ref LIKE " . DB::_quote('%' . $_POST['OrderNumber'] . '%');
  }
  if (isset($_POST['SelectedStockItem']) && $_POST['SelectedStockItem'] != ALL_TEXT) {
    $sql .= " AND workorder.stock_id=" . DB::_quote($_POST['SelectedStockItem']);
  }
  if (Input::_hasPost('OverdueOnly')) {
    $Today = Dates::_today(true);
    $sql .= " AND workorder.required_by < '$Today' ";
  }
  $cols  = array(
    _("#")            => array('fun' => 'view_link'),
    _("Reference"),
    // viewlink 2 ?
    _("Type")         => array('fun' => 'wo_type_name'),
    _("Location"),
    _("Item")         => array('fun' => 'view_stock'),
    _("Required")     => array(
      'fun'   => 'dec_amount',
      'align' => 'right'
    ),
    _("Manufactured") => array(
      'fun'   => 'dec_amount',
      'align' => 'right'
    ),
    _("Date")         => 'date',
    _("Required By")  => array(
      'type' => 'date',
      'ord'  => ''
    ),
    array(
      'insert' => true,
      'fun'    => 'edit_link'
    ),
    array(
      'insert' => true,
      'fun'    => 'release_link'
    ),
    array(
      'insert' => true,
      'fun'    => 'produce_link'
    ),
    array(
      'insert' => true,
      'fun'    => 'costs_link'
    ),
    array(
      'insert' => true,
      'fun'    => 'view_gl_link'
    )
  );
  $table = \ADV\App\Pager\Pager::newPager('orders_tbl', $cols);
  $table->setData($sql);
  $table->rowFunction = function ($row) {
    if (!$row["closed"] && Dates::_differenceBetween(Dates::_today(), Dates::_sqlToDate($row["required_by"]), "d") > 0) {
      return "class='overduebg''";
    }
  };
  Event::warning(_("Marked orders are overdue."), false);
  $table->width = "90%";
  $table->display($table);
  Forms::end();
  Page::end();

