<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_($help_context = "Tax Groups"), SA_TAXGROUPS);
  list($Mode, $selected_id) = Page::simple_mode(true);
  Validation::check(Validation::TAX_TYPES, _("There are no tax types defined. Define tax types before defining tax groups."));
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    //initialise no input errors assumed initially before we test
    $input_error = 0;
    if (strlen($_POST['name']) == 0) {
      $input_error = 1;
      Event::error(_("The tax group name cannot be empty."));
      JS::_setFocus('name');
    }
    if ($input_error != 1) {
      // create an array of the taxes and array of rates
      $taxes = [];
      $rates = [];
      for ($i = 0; $i < 5; $i++) {
        if (isset($_POST['tax_type_id' . $i]) && $_POST['tax_type_id' . $i] != ANY_NUMERIC
        ) {
          $taxes[] = $_POST['tax_type_id' . $i];
          $rates[] = Tax_Type::get_default_rate($_POST['tax_type_id' . $i]);
          //Editable rate has been removed 090920 Joe Hunt
          //$rates[] = Validation::input_num('rate' . $i);
        }
      }
      if ($selected_id != -1) {
        Tax_Groups::update($selected_id, $_POST['name'], $_POST['tax_shipping'], $taxes, $rates);
        Event::success(_('Selected tax group has been updated'));
      } else {
        Tax_Groups::add($_POST['name'], $_POST['tax_shipping'], $taxes, $rates);
        Event::success(_('New tax group has been added'));
      }
      $Mode = MODE_RESET;
    }
  }
  if ($Mode == MODE_DELETE) {
    Tax_Groups::delete($selected_id);
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    $sav         = Input::_post('show_inactive');
    unset($_POST);
    $_POST['show_inactive'] = $sav;
  }
  $result = Tax_Groups::getAll(Input::_hasPost('show_inactive'));
  Forms::start();
  Table::start('padded grid');
  $th = array(_("Description"), _("Shipping Tax"), "", "");
  Forms::inactiveControlCol($th);
  Table::header($th);
  $k = 0;
  while ($myrow = DB::_fetch($result)) {
    Cell::label($myrow["name"]);
    if ($myrow["tax_shipping"]) {
      Cell::label(_("Yes"));
    } else {
      Cell::label(_("No"));
    }
    /*for ($i=0; $i< 5; $i++)
if ($myrow["type" . $i] != ALL_NUMERIC)
echo "<td>" . $myrow["type" . $i] . "</td>";*/
    Forms::inactiveControlCell($myrow["id"], $myrow["inactive"], 'tax_groups', 'id');
    Forms::buttonEditCell("Edit" . $myrow["id"], _("Edit"));
    Forms::buttonDeleteCell("Delete" . $myrow["id"], _("Delete"));
    echo '</tr>';
    ;
  }
  Forms::inactiveControlRow($th);
  Table::end(1);
  Table::start('standard');
  if ($selected_id != -1) {
    //editing an existing status code
    if ($Mode == MODE_EDIT) {
      $group                 = Tax_Groups::get($selected_id);
      $_POST['name']         = $group["name"];
      $_POST['tax_shipping'] = $group["tax_shipping"];
      $items                 = Tax_Groups::get_for_item($selected_id);
      $i                     = 0;
      while ($tax_item = DB::_fetch($items)) {
        $_POST['tax_type_id' . $i] = $tax_item["tax_type_id"];
        $_POST['rate' . $i]        = Num::_percentFormat($tax_item["rate"]);
        $i++;
      }
      while ($i < 5) {
        unset($_POST['tax_type_id' . $i++]);
      }
    }
    Forms::hidden('selected_id', $selected_id);
  }
  Forms::textRowEx(_("Description:"), 'name', 40);
  Forms::yesnoListRow(_("Tax applied to Shipping:"), 'tax_shipping', null, "", "", true);
  Table::end();
  Event::warning(_("Select the taxes that are included in this group."), 1);
  Table::start('standard');
  //$th = array(_("Tax"), _("Default Rate (%)"), _("Rate (%)"));
  //Editable rate has been removed 090920 Joe Hunt
  $th = array(_("Tax"), _("Rate (%)"));
  Table::header($th);
  for ($i = 0; $i < 5; $i++) {
    echo '<tr>';
    if (!isset($_POST['tax_type_id' . $i])) {
      $_POST['tax_type_id' . $i] = 0;
    }
    Tax_Type::cells(null, 'tax_type_id' . $i, $_POST['tax_type_id' . $i], _("None"), true);
    if ($_POST['tax_type_id' . $i] != 0 && $_POST['tax_type_id' . $i] != ALL_NUMERIC) {
      $default_rate = Tax_Type::get_default_rate($_POST['tax_type_id' . $i]);
      Cell::label(Num::_percentFormat($default_rate), ' class="alignright nowrap"');
      //Editable rate has been removed 090920 Joe Hunt
      //if (!isset($_POST['rate' . $i]) || $_POST['rate' . $i] == "")
      //	$_POST['rate' . $i] = Num::_percentFormat($default_rate);
      // Forms::amountCellsSmall(null, 'rate' . $i, $_POST['rate' . $i], null, null,
      // User::_percent_dec());
    }
    echo '</tr>';
  }
  Table::end(1);
  Forms::submitAddUpdateCenter($selected_id == -1, '', 'both');
  Forms::end();
  Page::end();


