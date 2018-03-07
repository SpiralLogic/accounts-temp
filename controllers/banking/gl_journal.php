<?php
  use ADV\App\Dimensions;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  JS::_openWindow(950, 500);
  if (isset($_GET['ModifyGL'])) {
    $_SESSION['page_title'] = sprintf(_("Modifying Journal Transaction # %d."), $_GET['trans_no']);
    $help_context           = "Modifying Journal Entry";
  } else {
    $_SESSION['page_title'] = _($help_context = "Journal Entry");
  }
  Page::start($_SESSION['page_title'], SA_JOURNALENTRY);
  if (isset($_GET[ADDED_ID])) {
    $trans_no   = $_GET[ADDED_ID];
    $trans_type = ST_JOURNAL;
    Event::success(_("Journal entry has been entered") . " #$trans_no");
    Display::note(GL_UI::view($trans_type, $trans_no, _("&View this Journal Entry")));
    JS::_resetFocus();
    Display::link_params($_SERVER['DOCUMENT_URI'], _("Enter &New Journal Entry"), "NewJournal=Yes");
    Page::footer_exit();
  } elseif (isset($_GET[UPDATED_ID])) {
    $trans_no   = $_GET[UPDATED_ID];
    $trans_type = ST_JOURNAL;
    Event::success(_("Journal entry has been updated") . " #$trans_no");
    Display::note(GL_UI::view($trans_type, $trans_no, _("&View this Journal Entry")));
    Display::link_params(ROOT_DOC . "banking/inquiry/journal.php", _("Return to Journal &Inquiry"));
    Page::footer_exit();
  }
  if (isset($_GET['NewJournal'])) {
    create_order(ST_JOURNAL, 0);
  } elseif (isset($_GET['ModifyGL'])) {
    if (!isset($_GET['trans_type']) || $_GET['trans_type'] != 0) {
      Event::error(_("You can edit directly only journal entries created via Journal Entry page."));
      Display::link_params("/gl/gl_journal.php", _("Entry &New Journal Entry"), "NewJournal=Yes");
      Page::footer_exit();
    }
    create_order($_GET['trans_type'], $_GET['trans_no']);
  }
  if (isset($_POST['Process'])) {
    $input_error = 0;
    if ($_SESSION['journal_items']->count_gl_items() < 1) {
      Event::error(_("You must enter at least one journal line."));
      JS::_setFocus('code_id');
      $input_error = 1;
    }
    if (abs($_SESSION['journal_items']->gl_items_total()) > 0.0001) {
      Event::error(_("The journal must balance (debits equal to credits) before it can be processed."));
      JS::_setFocus('code_id');
      $input_error = 1;
    }
    if (!Dates::_isDate($_POST['date_'])) {
      Event::error(_("The entered date is invalid."));
      JS::_setFocus('date_');
      $input_error = 1;
    } elseif (!Dates::_isDateInFiscalYear($_POST['date_'])) {
      Event::error(_("The entered date is not in fiscal year."));
      JS::_setFocus('date_');
      $input_error = 1;
    }
    if (!Ref::is_valid($_POST['ref'])) {
      Event::error(_("You must enter a reference."));
      JS::_setFocus('ref');
      $input_error = 1;
    } elseif (Ref::exists(ST_JOURNAL, $_POST['ref'])) {
      // The reference can exist already so long as it's the same as the original (when modifying)
      if ($_POST['ref'] != $_POST['ref_original']) {
        Event::error(_("The entered reference is already in use."));
        JS::_setFocus('ref');
        $input_error = 1;
      }
    }
    if ($input_error == 1) {
      unset($_POST['Process']);
    }
  }
  if (isset($_POST['Process'])) {
    $order            = $_SESSION['journal_items'];
    $new              = $order->order_id == 0;
    $order->reference = $_POST['ref'];
    $order->memo_     = $_POST['memo_'];
    $order->tran_date = $_POST['date_'];
    $trans_no         = GL_Journal::write($order, Input::_hasPost('Reverse'));
    $order->clear_items();
    Dates::_newDocDate($_POST['date_']);
    unset($_SESSION['journal_items']);
    if ($new) {
      Display::meta_forward($_SERVER['DOCUMENT_URI'], "AddedID=$trans_no");
    } else {
      Display::meta_forward($_SERVER['DOCUMENT_URI'], "UpdatedID=$trans_no");
    }
  }
  $id = Forms::findPostPrefix(MODE_DELETE);
  if ($id != -1) {
    handle_delete_item($id);
  }
  if (isset($_POST['addLine'])) {
    handle_new_item();
  }
  if (isset($_POST['updateItem'])) {
    handle_update_item();
  }
  if (isset($_POST['cancelItem'])) {
    Item_Line::start_focus('_code_id_edit');
  }
  if (isset($_POST['go'])) {
    Display::quick_entries($_SESSION['journal_items'], $_POST['person_id'], Validation::input_num('total_amount'), QE_JOURNAL);
    $_POST['total_amount'] = Num::_priceFormat(0);
    Ajax::_activate('total_amount');
    Item_Line::start_focus('_code_id_edit');
  }
  Forms::start();
  GL_Journal::header($_SESSION['journal_items']);
  Table::start('tables_style2 width90 pad10');
  echo '<tr>';
  echo "<td>";
  GL_Journal::items(_("Rows"), $_SESSION['journal_items']);
  GL_Journal::option_controls();
  echo "</td>";
  echo '</tr>';
  Table::end(1);
  Forms::submitCenter('Process', _("Process Journal Entry"), true, _('Process journal entry only if debits equal to credits'), 'default');
  Forms::end();
  Page::end();
  /**
   * @return bool
   */
  function check_item_data() {
    if (isset($_POST['dimension_id']) && $_POST['dimension_id'] != 0 && Dimensions::is_closed($_POST['dimension_id'])) {
      Event::error(_("Dimension is closed."));
      JS::_setFocus('dimension_id');
      return false;
    }
    if (isset($_POST['dimension2_id']) && $_POST['dimension2_id'] != 0 && Dimensions::is_closed($_POST['dimension2_id'])
    ) {
      Event::error(_("Dimension is closed."));
      JS::_setFocus('dimension2_id');
      return false;
    }
    if (!(Validation::input_num('AmountDebit') != 0 ^ Validation::input_num('AmountCredit') != 0)) {
      Event::error(_("You must enter either a debit amount or a credit amount."));
      JS::_setFocus('AmountDebit');
      return false;
    }
    if (strlen($_POST['AmountDebit']) && !Validation::post_num('AmountDebit', 0)) {
      Event::error(_("The debit amount entered is not a valid number or is less than zero."));
      JS::_setFocus('AmountDebit');
      return false;
    } elseif (strlen($_POST['AmountCredit']) && !Validation::post_num('AmountCredit', 0)) {
      Event::error(_("The credit amount entered is not a valid number or is less than zero."));
      JS::_setFocus('AmountCredit');
      return false;
    }
    if (!Tax_Type::is_tax_gl_unique(Input::_post('code_id'))) {
      Event::error(_("Cannot post to GL account used by more than one tax type."));
      JS::_setFocus('code_id');
      return false;
    }
    if (!User::_i()->hasAccess(SA_BANKJOURNAL) && Bank_Account::is($_POST['code_id'])) {
      Event::error(_("You cannot make a journal entry for a bank account. Please use one of the banking functions for bank transactions."));
      JS::_setFocus('code_id');
      return false;
    }
    return true;
  }

  function handle_update_item() {
    if ($_POST['updateItem'] != "" && check_item_data()) {
      if (Validation::input_num('AmountDebit') > 0) {
        $amount = Validation::input_num('AmountDebit');
      } else {
        $amount = -Validation::input_num('AmountCredit');
      }
      $_SESSION['journal_items']->update_gl_item($_POST['Index'], $_POST['code_id'], $_POST['dimension_id'], $_POST['dimension2_id'], $amount, $_POST['LineMemo']);
    }
    Item_Line::start_focus('_code_id_edit');
  }

  /**
   * @param $id
   */
  function handle_delete_item($id) {
    $_SESSION['journal_items']->remove_gl_item($id);
    Item_Line::start_focus('_code_id_edit');
  }

  function handle_new_item() {
    if (!check_item_data()) {
      return;
    }
    if (Validation::input_num('AmountDebit') > 0) {
      $amount = Validation::input_num('AmountDebit');
    } else {
      $amount = -Validation::input_num('AmountCredit');
    }
    $_SESSION['journal_items']->add_gl_item($_POST['code_id'], $_POST['dimension_id'], $_POST['dimension2_id'], $amount, $_POST['LineMemo']);
    Item_Line::start_focus('_code_id_edit');
  }

  /**
   * @param int $type
   * @param int $trans_no
   */
  function create_order($type = ST_JOURNAL, $trans_no = 0) {
    if (isset($_SESSION['journal_items'])) {
      unset ($_SESSION['journal_items']);
    }
    $order           = new Item_Order($type);
    $order->order_id = $trans_no;
    if ($trans_no) {
      $result = GL_Trans::get_many($type, $trans_no);
      if ($result) {
        while ($row = DB::_fetch($result)) {
          if ($row['amount'] == 0) {
            continue;
          }
          $date = $row['tran_date'];
          $order->add_gl_item($row['account'], $row['dimension_id'], $row['dimension2_id'], $row['amount'], $row['memo_']);
        }
      }
      $order->memo_          = DB_Comments::get_string($type, $trans_no);
      $order->tran_date      = Dates::_sqlToDate($date);
      $order->reference      = Ref::get($type, $trans_no);
      $_POST['ref_original'] = $order->reference; // Store for comparison when updating
    } else {
      $order->reference = Ref::get_next(ST_JOURNAL);
      $order->tran_date = Dates::_newDocDate();
      if (!Dates::_isDateInFiscalYear($order->tran_date)) {
        $order->tran_date = Dates::_endFiscalYear();
      }
      $_POST['ref_original'] = -1;
    }
    $_POST['memo_']            = $order->memo_;
    $_POST['ref']              = $order->reference;
    $_POST['date_']            = $order->tran_date;
    $_SESSION['journal_items'] = & $order;
  }
