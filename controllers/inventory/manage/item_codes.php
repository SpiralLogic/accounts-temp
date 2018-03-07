<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_($help_context = "Foreign Item Codes"), SA_FORITEMCODE);
  Validation::check(Validation::PURCHASE_ITEMS, _("There are no inventory items defined in the system."), STOCK_PURCHASED);
  list($Mode, $selected_id) = Page::simple_mode(true);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    $input_error = 0;
    if ($_POST['stock_id'] == "" || !isset($_POST['stock_id'])) {
      $input_error = 1;
      Event::error(_("There is no item selected."));
      JS::_setFocus('stock_id');
    } elseif (!Validation::input_num('quantity')) {
      $input_error = 1;
      Event::error(_("The price entered was not positive number."));
      JS::_setFocus('quantity');
    } elseif ($_POST['description'] == '') {
      $input_error = 1;
      Event::error(_("Item code description cannot be empty."));
      JS::_setFocus('description');
    } elseif ($selected_id == -1) {
      $kit = Item_Code::get_kit($_POST['item_code']);
      if (DB::_numRows($kit)) {
        $input_error = 1;
        Event::error(_("This item code is already assigned to stock item or sale kit."));
        JS::_setFocus('item_code');
      }
    }
    if ($input_error == 0) {
      if ($Mode == ADD_ITEM) {
        Item_Code::add($_POST['item_code'], $_POST['stock_id'], $_POST['description'], $_POST['category_id'], $_POST['quantity'], 1);
        Event::success(_("New item code has been added."));
      } else {
        Item_Code::update($selected_id, $_POST['item_code'], $_POST['stock_id'], $_POST['description'], $_POST['category_id'], $_POST['quantity'], 1);
        Event::success(_("Item code has been updated."));
      }
      $Mode = MODE_RESET;
    }
  }
  if ($Mode == MODE_DELETE) {
    Item_Code::delete($selected_id);
    Event::notice(_("Item code has been sucessfully deleted."));
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    unset($_POST);
  }
  if (Forms::isListUpdated('stock_id')) {
    Ajax::_activate('_page_body');
  }
  Forms::start();
  if (!Input::_post('stock_id')) {
    Session::_setGlobal('stock_id', $_POST['stock_id']);
  }
  echo "<div class='center'>" . _("Item:") . "&nbsp;";
  echo Item_Purchase::select('stock_id', $_POST['stock_id'], false, true, false, false);
  echo "<hr></div>";
  Session::_setGlobal('stock_id', $_POST['stock_id']);
  $result    = Item_Code::get_defaults($_POST['stock_id']);
  $dec       = $result['decimals'];
  $units     = $result['units'];
  $dflt_desc = $result['description'];
  $dflt_cat  = $result['category_id'];
  $result    = Item_Code::getAll($_POST['stock_id']);
  Ajax::_start_div('code_table');
  Table::start('padded grid width60');
  $th = array(
    _("EAN/UPC Code"),
    _("Quantity"),
    _("Units"),
    _("Description"),
    _("Category"),
    "",
    ""
  );
  Table::header($th);
  $k = $j = 0; //row colour counter
  while ($myrow = DB::_fetch($result)) {
    Cell::label($myrow["item_code"]);
    Cell::qty($myrow["quantity"], $dec);
    Cell::label($units);
    Cell::label($myrow["description"]);
    Cell::label($myrow["cat_name"]);
    Forms::buttonEditCell("Edit" . $myrow['id'], _("Edit"));
    Forms::buttonEditCell("Delete" . $myrow['id'], _("Delete"));
    echo '</tr>';
    $j++;
    If ($j == 12) {
      $j = 1;
      Table::header($th);
    } //end of page full new headings
  } //end of while loop
  Table::end();
  Ajax::_end_div();
  if ($selected_id != '') {
    if ($Mode == MODE_EDIT) {
      $myrow                = Item_Code::get($selected_id);
      $_POST['item_code']   = $myrow["item_code"];
      $_POST['quantity']    = $myrow["quantity"];
      $_POST['description'] = $myrow["description"];
      $_POST['category_id'] = $myrow["category_id"];
    }
    Forms::hidden('selected_id', $selected_id);
  } else {
    $_POST['quantity']    = 1;
    $_POST['description'] = $dflt_desc;
    $_POST['category_id'] = $dflt_cat;
  }
  echo "<br>";
  Table::start('standard');
  Forms::hidden('code_id', $selected_id);
  Forms::textRow(_("UPC/EAN code:"), 'item_code', null, 20, 21);
  Forms::qtyRow(_("Quantity:"), 'quantity', null, '', $units, $dec);
  Forms::textRow(_("Description:"), 'description', null, 50, 200);
  Item_Category::row(_("Category:"), 'category_id', null);
  Table::end(1);
  Forms::submitAddUpdateCenter($selected_id == -1, '', 'both');
  Forms::end();
  Page::end();


