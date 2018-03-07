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
  namespace ADV\Controllers\Tax\Manage;

  use ADV\App\Form\Form;
  use ADV\App\Tax\Type;
  use ADV\App\Tax\ItemType;
  use ADV\Core\View;

  /** **/
  class Itemtypes extends \ADV\App\Controller\FormPager
  {
    protected $security = SA_ITEMTAXTYPE;
    protected function before() {
      $this->object = new ItemType();
      $this->runPost();
      $this->setTitle("Item Tax Types");
    }
    /**
     * @param $form
     * @param $view
     *
     * @return mixed|void
     */
    protected function formContents(Form $form, View $view) {
      $view['title'] = 'Item Tax Type';
      $form->hidden('id');
      $form->text('name')->label('Name:')->focus($this->action == EDIT);
      $form->text('exempt')->label('Fully Exempt:');
      $form->checkbox('inactive')->label('Inactive:');
      $tax_types  = Type::getAll();
      $exemptions = new Form();
      $form->heading('Exemptions');
      foreach ($tax_types as $type) {
        $exemptions->arraySelect($type['id'], ['No', 'Yes'])->label($type['name']);
      }
      $form->nest('exemptions', $exemptions);
    }
  }

/*
  Page::start(_($help_context = "Item Tax Types"), SA_ITEMTAXTYPE);
  list($Mode, $selected_id) = Page::simple_mode(true);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    $input_error = 0;
    if (strlen($_POST['name']) == 0) {
      $input_error = 1;
      Event::error(_("The item tax type description cannot be empty."));
      JS::_setFocus('name');
    }
    if ($input_error != 1) {
      // create an array of the exemptions
      $exempt_from = [];
      $tax_types   = Tax_Type::get_all_simple();
      $i           = 0;
      while ($myrow = DB::_fetch($tax_types)) {
        if (Input::_hasPost('ExemptTax' . $myrow["id"])) {
          $exempt_from[$i] = $myrow["id"];
          $i++;
        }
      }
      if ($selected_id != -1) {
        Tax_ItemType::update($selected_id, $_POST['name'], $_POST['exempt'], $exempt_from);
        Event::success(_('Selected item tax type has been updated'));
      } else {
        Tax_ItemType::add($_POST['name'], $_POST['exempt'], $exempt_from);
        Event::success(_('New item tax type has been added'));
      }
      $Mode = MODE_RESET;
    }
  }
  if ($Mode == MODE_DELETE) {
    Tax_ItemType::delete($selected_id);
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    $sav         = Input::_post('show_inactive');
    unset($_POST);
    $_POST['show_inactive'] = $sav;
  }
  $result2 = $result = Tax_ItemType::getAll(Input::_hasPost('show_inactive'));
  Forms::start();
  Table::start('padded grid width30');
  $th = array(_("Name"), _("Tax exempt"), '', '');
  Forms::inactiveControlCol($th);
  Table::header($th);
  $k = 0;
  while ($myrow = DB::_fetch($result2)) {
    if ($myrow["exempt"] == 0) {
      $disallow_text = _("No");
    } else {
      $disallow_text = _("Yes");
    }
    Cell::label($myrow["name"]);
    Cell::label($disallow_text);
    Forms::inactiveControlCell($myrow["id"], $myrow["inactive"], 'item_tax_types', 'id');
    Forms::buttonEditCell("Edit" . $myrow["id"], _("Edit"));
    Forms::buttonDeleteCell("Delete" . $myrow["id"], _("Delete"));
    echo '</tr>';
  }
  Forms::inactiveControlRow($th);
  Table::end(1);
  Table::start('standard');
  if ($selected_id != -1) {
    if ($Mode == MODE_EDIT) {
      $myrow = Tax_ItemType::get($selected_id);
      unset($_POST); // clear exemption checkboxes
      $_POST['name']   = $myrow["name"];
      $_POST['exempt'] = $myrow["exempt"];
      // read the exemptions and check the ones that are on
      $exemptions = Tax_ItemType::get_exemptions($selected_id);
      if (DB::_numRows($exemptions) > 0) {
        while ($exmp = DB::_fetch($exemptions)) {
          $_POST['ExemptTax' . $exmp["tax_type_id"]] = 1;
        }
      }
    }
    Forms::hidden('selected_id', $selected_id);
  }
  Forms::textRowEx(_("Description:"), 'name', 50);
  Forms::yesnoListRow(_("Is Fully Tax-exempt:"), 'exempt', null, "", "", true);
  Table::end(1);
  if (!isset($_POST['exempt']) || $_POST['exempt'] == 0) {
    Event::warning(_("Select which taxes this item tax type is exempt from."), 0, 1);
    Table::start('standard grid');
    $th = array(_("Tax Name"), _("Rate"), _("Is exempt"));
    Table::header($th);
    $tax_types = Tax_Type::get_all_simple();
    while ($myrow = DB::_fetch($tax_types)) {
      Cell::label($myrow["name"]);
      Cell::label(Num::_percentFormat($myrow["rate"]) . " %", ' class="alignright nowrap"');
      Forms::checkCells("", 'ExemptTax' . $myrow["id"], null);
      echo '</tr>';
    }
    Table::end(1);
  }
  Forms::submitAddUpdateCenter($selected_id == -1, '', 'both');
  Forms::end();
  Page::end();
  /**
   * @param $selected_id
   *
   * @return bool
   */


