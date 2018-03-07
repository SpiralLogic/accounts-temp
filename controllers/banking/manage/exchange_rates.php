<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  $js = "";
  Page::start(_($help_context = "Exchange Rates"), SA_EXCHANGERATE);
  list($Mode, $selected_id) = Page::simple_mode(false);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    handle_submit($selected_id);
  }
  if ($Mode == MODE_DELETE) {
    handle_delete($selected_id);
  }
  Forms::start();
  if (!isset($_POST['curr_abrev'])) {
    $_POST['curr_abrev'] = Session::_getGlobal('curr_abrev');
  }
  echo "<div class='center'>";
  echo _("Select a currency :") . " ";
  echo GL_Currency::select('curr_abrev', null, true);
  echo "</div>";
  // if currency sel has changed, clear the form
  if ($_POST['curr_abrev'] != Session::_getGlobal('curr_abrev')) {
    clear_data();
    $selected_id = "";
  }
  Session::_setGlobal('curr_abrev', $_POST['curr_abrev']);
  $sql   = "SELECT date_, rate_buy, id FROM exchange_rates " . "WHERE curr_code=" . DB::_quote($_POST['curr_abrev']) . "
     ORDER BY date_ DESC";
  $cols  = array(
    _("Date to Use From") => 'date',
    _("Exchange Rate")    => 'rate',
    array(
      'insert' => true,
      'fun'    => 'edit_link'
    ),
    array(
      'insert' => true,
      'fun'    => 'del_link'
    ),
  );
  $table = \ADV\App\Pager\Pager::newPager('orders_tbl', $cols);
  $table->setData($sql);
  if (Bank_Currency::is_company($_POST['curr_abrev'])) {
    Event::warning(_("The selected currency is the company currency."), 2);
    Event::warning(_("The company currency is the base currency so exchange rates cannot be set for it."), 1);
  } else {
    echo "<br>";
    $table->width = "40%";
    $table->display($table);
    echo "<br>";
    display_rate_edit($selected_id);
  }
  Forms::end();
  Page::end();
  /**
   * @return bool
   */
  function check_data() {
    if (!Dates::_isDate($_POST['date_'])) {
      Event::error(_("The entered date is invalid."));
      JS::_setFocus('date_');
      return false;
    }
    if (Validation::input_num('BuyRate') <= 0) {
      Event::error(_("The exchange rate cannot be zero or a negative number."));
      JS::_setFocus('BuyRate');
      return false;
    }
    if (GL_ExchangeRate::get_date($_POST['curr_abrev'], $_POST['date_'])) {
      Event::error(_("The exchange rate for the date is already there."));
      JS::_setFocus('date_');
      return false;
    }
    return true;
  }

  /**
   * @param $selected_id
   *
   * @return bool
   */
  function handle_submit(&$selected_id) {
    if (!check_data()) {
      return false;
    }
    if ($selected_id != "") {
      GL_ExchangeRate::update($_POST['curr_abrev'], $_POST['date_'], Validation::input_num('BuyRate'), Validation::input_num('BuyRate'));
    } else {
      GL_ExchangeRate::add($_POST['curr_abrev'], $_POST['date_'], Validation::input_num('BuyRate'), Validation::input_num('BuyRate'));
    }
    $selected_id = '';
    clear_data();
  }

  /**
   * @param $selected_id
   *
   * @return mixed
   */
  function handle_delete(&$selected_id) {
    if ($selected_id == "") {
      return;
    }
    GL_ExchangeRate::delete($selected_id);
    $selected_id = '';
    clear_data();
  }

  /**
   * @param $row
   *
   * @return string
   */
  function edit_link($row) {
    return Forms::button(MODE_EDIT . $row["id"], _("Edit"), true, ICON_EDIT);
  }

  /**
   * @param $row
   *
   * @return string
   */
  function del_link($row) {
    return Forms::button(MODE_DELETE . $row["id"], _("Delete"), true, ICON_DELETE);
  }

  /**
   * @param $curr_code
   */
  function display_rates($curr_code) {
  }

  /**
   * @param $selected_id
   */
  function display_rate_edit(&$selected_id) {
    Table::start('standard');
    if ($selected_id != "") {
      //editing an existing exchange rate
      $myrow            = GL_ExchangeRate::get($selected_id);
      $_POST['date_']   = Dates::_sqlToDate($myrow["date_"]);
      $_POST['BuyRate'] = Num::_exrateFormat($myrow["rate_buy"]);
      Forms::hidden('selected_id', $selected_id);
      Forms::hidden('date_', $_POST['date_']);
      Table::label(_("Date to Use From:"), $_POST['date_']);
    } else {
      $_POST['date_']   = Dates::_today();
      $_POST['BuyRate'] = '';
      Forms::dateRow(_("Date to Use From:"), 'date_');
    }
    if (isset($_POST['get_rate'])) {
      $_POST['BuyRate'] = Num::_exrateFormat(GL_ExchangeRate::retrieve($_POST['curr_abrev'], $_POST['date_']));
      Ajax::_activate('BuyRate');
    }
    Forms::SmallAmountRow(_("Exchange Rate:"), 'BuyRate', null, '', Forms::submit('get_rate', _("Get"), false, _('Get current ECB rate'), true), User::_exrate_dec());
    Table::end(1);
    Forms::submitAddUpdateCenter($selected_id == '', '', 'both');
    Event::warning(_("Exchange rates are entered against the company currency."), 1);
  }

  function clear_data() {
    unset($_POST['selected_id'], $_POST['date_'], $_POST['BuyRate']);
  }
