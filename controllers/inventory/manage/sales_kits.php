<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_($help_context = "Sales Kits & Alias Codes"), SA_SALESKIT);
  Validation::check(Validation::STOCK_ITEMS, _("There are no items defined in the system."));
  list($Mode, $selected_id) = Page::simple_mode(true);
  if (Input::_post('update_name')) {
    Item_Code::update_kit_props(Input::_post('item_code'), Input::_post('description'), Input::_post('category'));
    Event::success(_('Kit common properties has been updated'));
    Ajax::_activate('_page_body');
  }
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    update_component($Mode, $_POST['item_code'], $selected_id);
  }
  if ($Mode == MODE_DELETE) {
    // Before removing last component from selected kit check
    // if selected kit is not included in any other kit.
    //
    $other_kits = Item_Code::get_where_used($_POST['item_code']);
    $num_kits   = DB::_numRows($other_kits);
    $kit        = Item_Code::get_kit($_POST['item_code']);
    if ((DB::_numRows($kit) == 1) && $num_kits) {
      $msg = _("This item cannot be deleted because it is the last item in the kit used by following kits") . ':<br>';
      while ($num_kits--) {
        $kit = DB::_fetch($other_kits);
        $msg .= "'" . $kit[0] . "'";
        if ($num_kits) {
          $msg .= ',';
        }
      }
      Event::error($msg);
    } else {
      Item_Code::delete($selected_id);
      Event::success(_("The component item has been deleted from this bom"));
      $Mode = MODE_RESET;
    }
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    unset($_POST['quantity'], $_POST['component']);
  }
  Forms::start();
  echo "<div class='center'>" . _("Select a sale kit:") . "&nbsp;";
  echo Sales_UI::kits('item_code', null, _('New kit'), true);
  echo "</div><br>";
  $props = Item_Code::get_kit_props(Input::_post('item_code'));
  if (Forms::isListUpdated('item_code')) {
    if (Input::_post('item_code') == '') {
      $_POST['description'] = '';
    }
    Ajax::_activate('_page_body');
  }
  $selected_kit = $_POST['item_code'];
  if (Input::_post('item_code') == '') {
    // New sales kit entry
    Table::start('standard');
    Forms::textRow(_("Alias/kit code:"), 'kit_code', null, 20, 21);
  } else {
    // Kit selected so display bom or edit component
    $_POST['description'] = $props['description'];
    $_POST['category']    = $props['category_id'];
    Table::start('standard');
    Forms::textRow(_("Description:"), 'description', null, 50, 200);
    Item_Category::row(_("Category:"), 'category', null);
    Forms::submitRow('update_name', _("Update"), false, 'class=center colspan=2', _('Update kit/alias name'), true);
    echo '</tr>';
    Table::end(1);
    display_kit_items($selected_kit);
    echo '<br>';
    Table::start('standard');
  }
  if ($Mode == MODE_EDIT) {
    $myrow              = Item_Code::get($selected_id);
    $_POST['component'] = $myrow["stock_id"];
    $_POST['quantity']  = Num::_format($myrow["quantity"], Item::qty_dec($myrow["stock_id"]));
  }
  Forms::hidden("selected_id", $selected_id);
  Sales_UI::local_items_row(_("Component:"), 'component', null, false, true);
  //	if (Input::_post('description') == '')
  //		$_POST['description'] = get_kit_name($_POST['component']);
  if (Input::_post('item_code') == '') { // new kit/alias
    if ($Mode != ADD_ITEM && $Mode != UPDATE_ITEM) {
      $_POST['description'] = $props['description'];
      $_POST['category']    = $props['category_id'];
    }
    Forms::textRow(_("Description:"), 'description', null, 50, 200);
    Item_Category::row(_("Category:"), 'category', null);
  }
  $res   = Item::get_edit_info(Input::_post('component'));
  $dec   = $res["decimals"] == '' ? 0 : $res["decimals"];
  $units = $res["units"] == '' ? _('kits') : $res["units"];
  if (Forms::isListUpdated('component')) {
    $_POST['quantity'] = Num::_format(1, $dec);
    Ajax::_activate('quantity');
    Ajax::_activate('category');
  }
  Forms::qtyRow(_("Quantity:"), 'quantity', Num::_format(1, $dec), '', $units, $dec);
  Table::end(1);
  Forms::submitAddUpdateCenter($selected_id == -1, '', 'both');
  Forms::end();
  Page::end();
  /**
   * @param $selected_kit
   */
  function display_kit_items($selected_kit) {
    $result = Item_Code::get_kit($selected_kit);
    Ajax::_start_div('bom');
    Table::start('padded grid width60');
    $th = array(
      _("Stock Item"),
      _("Description"),
      _("Quantity"),
      _("Units"),
      '',
      ''
    );
    Table::header($th);
    $k = 0;
    while ($myrow = DB::_fetch($result)) {
      Cell::label($myrow["stock_id"]);
      Cell::label($myrow["comp_name"]);
      Cell::qty($myrow["quantity"], false, $myrow["units"] == '' ? 0 : Item::qty_dec($myrow["comp_name"]));
      Cell::label($myrow["units"] == '' ? _('kit') : $myrow["units"]);
      Forms::buttonEditCell("Edit" . $myrow['id'], _("Edit"));
      Forms::buttonDeleteCell("Delete" . $myrow['id'], _("Delete"));
      echo '</tr>';
    } //END WHILE LIST LOOP
    Table::end();
    Ajax::_end_div();
  }

  /**
   * @param $Mode
   * @param $kit_code
   * @param $selected_item
   *
   * @return mixed
   */
  function update_component(&$Mode, $kit_code, $selected_item) {
    global $selected_kit;
    if (!Validation::post_num('quantity', 0)) {
      Event::error(_("The quantity entered must be numeric and greater than zero."));
      JS::_setFocus('quantity');
      return;
    } elseif ($_POST['description'] == '') {
      Event::error(_("Item code description cannot be empty."));
      JS::_setFocus('description');
      return;
    } elseif ($selected_item == -1) // adding new item or new alias/kit
    {
      if (Input::_post('item_code') == '') { // New kit/alias definition
        $kit = Item_Code::get_kit($_POST['kit_code']);
        if (DB::_numRows($kit)) {
          Event::error(_("This item code is already assigned to stock item or sale kit."));
          JS::_setFocus('kit_code');
          return;
        }
        if (Input::_post('kit_code') == '') {
          Event::error(_("Kit/alias code cannot be empty."));
          JS::_setFocus('kit_code');
          return;
        }
      }
    }
    if (Item_Code::is_item_in_kit($selected_item, $kit_code, $_POST['component'], true)) {
      Event::error(_("The selected component contains directly or on any lower level the kit under edition. Recursive kits are not allowed."));
      JS::_setFocus('component');
      return;
    }
    /*Now check to see that the component is not already in the kit */
    if (Item_Code::is_item_in_kit($selected_item, $kit_code, $_POST['component'])) {
      Event::error(_("The selected component is already in this kit. You can modify it's quantity but it cannot appear more than once in the same kit."));
      JS::_setFocus('component');
      return;
    }
    if ($selected_item == -1) { // new item alias/kit
      if ($_POST['item_code'] == '') {
        $kit_code     = $_POST['kit_code'];
        $selected_kit = $_POST['item_code'] = $kit_code;
        $msg          = _("New alias code has been created.");
      } else {
        $msg = _("New component has been added to selected kit.");
      }
      Item_Code::add($kit_code, Input::_post('component'), Input::_post('description'), Input::_post('category'), Validation::input_num('quantity'), 0);
      Event::success($msg);
    } else {
      $props = Item_Code::get_kit_props($_POST['item_code']);
      Item_Code::update($selected_item, $kit_code, Input::_post('component'), $props['description'], $props['category_id'], Validation::input_num('quantity'), 0);
      Event::success(_("Component of selected kit has been updated."));
    }
    $Mode = MODE_RESET;
    Ajax::_activate('_page_body');
  }

