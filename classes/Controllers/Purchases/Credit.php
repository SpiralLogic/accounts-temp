<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Controllers\Purchases;

  use ADV\Core\Input\Input;
  use ADV\App\Dates;
  use ADV\App\Ref;
  use Tax_Type;
  use ADV\Core\Num;
  use GL_QuickEntry;
  use GL_UI;
  use ADV\Core\Event;
  use ADV\App\Display;
  use Purch_GLItem;
  use Purch_GRN;
  use Purch_Invoice;
  use ADV\App\Forms;
  use Creditor_Trans;
  use ADV\App\Validation;
  use ADV\Core\DB\DB;

  /** **/
  class Credit extends \ADV\App\Controller\Action
  {
    /** @var \Creditor_trans */
    protected $trans;
    protected $creditor_id;
    protected $security = SA_SUPPLIERCREDIT;
    protected function before() {
      $this->JS->openWindow(900, 500);
      Validation::check(Validation::SUPPLIERS, _("There are no suppliers defined in the system."));
      $this->trans             = Creditor_Trans::i();
      $this->trans->is_invoice = false;
      if (isset($_POST['ClearFields'])) {
        $this->clearFields();
      }
      if (isset($_POST['Cancel'])) {
        $this->cancelCredit();
      }
      if (isset($_GET['New']) && isset($_GET['invoice_no'])) {
        $this->trans->supplier_reference = $_POST['invoice_no'] = $_GET['invoice_no'];
      }
      $this->creditor_id = $this->trans->creditor_id ? : Input::_getPost('creditor_id', Input::NUMERIC, null);
      if (isset($_POST['AddGLCodeToTrans'])) {
        $this->addGlCodeToTrans();
      }
      //	GL postings are often entered in the same form to two accounts
      // so fileds are cleared only on user demand.
      //
      $id = Forms::findPostPrefix('grn_item_id');
      if ($id != -1) {
        $this->commitItemData($id);
        $this->Ajax->activate('grn_items');
        $this->Ajax->activate('inv_tot');
      }
      if (isset($_POST['InvGRNAll'])) {
        $this->invGrnAll();
      }
      if (Input::_post('PONumber')) {
        $this->Ajax->activate('grn_items');
        $this->Ajax->activate('inv_tot');
      }
      $this->checkDelete();
      if (isset($_POST['RefreshInquiry'])) {
        $this->Ajax->activate('grn_items');
        $this->Ajax->activate('inv_tot');
      }
      if (isset($_POST['go'])) {
        $this->go();
      }
      $this->setTitle("Supplier Credit Note");
    }
    protected function index() {
      if (isset($_POST['PostCreditNote'])) {
        $this->postCredit();
      }
      Forms::start();
      Purch_Invoice::header($this->trans);
      if ($this->creditor_id) {
        $total_grn_value = Purch_GRN::display_items($this->trans, 1);
        $total_gl_value  = Purch_GLItem::display_items($this->trans, 1);
        $this->Ajax->start_div('inv_tot');
        Purch_Invoice::totals($this->trans);
        $this->Ajax->end_div();
      }
      if (Input::_post('AddGLCodeToTrans')) {
        $this->Ajax->activate('inv_tot');
      }
      echo "<br>";
      Forms::submitCenterBegin('Cancel', _("Cancel Invoice"));
      Forms::submitCenterEnd('PostCreditNote', _("Enter Credit Note"), true, '');
      echo "<br>";
      Forms::end();
      $this->addJS();
    }
    protected function addJS() {
      $js
        = <<<JS
             $("#wrapper").delegate('.amount','change',function() {
         var field = $(this), ChgTax=$('[name="ChgTax"]'),ChgTotal=$('[name="ChgTotal"]'),invTotal=$('#invoiceTotal'), fields = $(this).parent().parent(), fv = {}, nodes = {
         qty: $('[name^="this_quantity"]',fields),
         price: $('[name^="ChgPrice"]',fields),
         discount: $('[name^="ChgDiscount"]',fields),
         total: $('[id^="ChgTotal"]',fields),
                            eachprice: $('[id^="Ea"]',fields)
         };
         if (fields.hasClass('grid')) {
         $.each(nodes,function(k,v) {
         if (v && v.val()) {fv[k] = Number(v.val().replace(',',''));}
         });
         if (field.attr('id') == nodes.total.attr('id')) {
         if (fv.price == 0 && fv.discount==0) {
         fv.price = fv.total / fv.qty;
         } else {
         fv.discount = 100*(1-(fv.total)/(fv.price*fv.qty));
                 fv.discount = Math.round(fv.discount*1)/1;
         }
         nodes.price.val(fv.price);
         nodes.discount.val(fv.discount);
         } else if (fv.qty > 0 && fv.price > 0) {
         fv.total = fv.qty*fv.price*((100-fv.discount)/100);
         nodes.total.val(Math.round(fv.total*100)/100 );
         }
         Adv.Forms.priceFormat(nodes.eachprice.attr('id'),(fv.total/fv.qty),2,true);
         } else {
            if (field.attr('name')=='ChgTotal' || field.attr('name')=='ChgTax') {
            var total = Number(invTotal.data('total'));
            ChgTax = Number(ChgTax.val().replace(',',''));
            ChgTotal = Number(ChgTotal.val().replace(',',''));
            Adv.Forms.priceFormat(invTotal.attr('id'),total+ChgTax+ChgTotal,2,true); }
        }});
JS;
      $this->JS->onload($js);
    }
    /**
     * @param $invoice_no
     */
    protected function pageComplete($invoice_no) {
      $trans_type = ST_SUPPCREDIT;
      echo "<div class='center'>";
      Event::success(_("Supplier credit note has been processed."));
      Display::note(GL_UI::viewTrans($trans_type, $invoice_no, _("View this Credit Note")));
      Display::note(GL_UI::view($trans_type, $invoice_no, _("View the GL Journal Entries for this Credit Note")), 1);
      Display::link_params($_SERVER['DOCUMENT_URI'], _("Enter Another Credit Note"), "New=1");
      Display::link_params("/system/attachments.php", _("Add an Attachment"), "filterType=$trans_type&trans_no=$invoice_no");
      $this->Ajax->activate('_page_body');
      $this->Page->endExit();
    }
    protected function go() {
      $this->Ajax->activate('gl_items');
      GL_QuickEntry::addEntry($this->trans, $_POST['qid'], Validation::input_num('total_amount'), QE_SUPPINV);
      $_POST['total_amount'] = Num::_priceFormat(0);
      $this->Ajax->activate('total_amount');
      $this->Ajax->activate('inv_tot');
    }
    protected function checkDelete() {
      $id3 = Forms::findPostPrefix(MODE_DELETE);
      if ($id3 != -1) {
        $this->trans->remove_grn_from_trans($id3);
        $this->Ajax->activate('grn_items');
        $this->Ajax->activate('inv_tot');
      }
      $id4 = Forms::findPostPrefix('Delete2');
      if ($id4 != -1) {
        $this->trans->remove_gl_codes_from_trans($id4);
        unset($_POST['gl_code'], $_POST['dimension_id'], $_POST['dimension2_id'], $_POST['amount'], $_POST['memo_'], $_POST['AddGLCodeToTrans']);
        $this->JS->setFocus('gl_code');
        $this->Ajax->activate('gl_items');
        $this->Ajax->activate('inv_tot');
      }
    }
    protected function invGrnAll() {
      foreach ($_POST as $postkey => $postval) {
        if (strpos($postkey, "qty_recd") === 0) {
          $id = substr($postkey, strlen("qty_recd"));
          $id = (int) $id;
          $this->commitItemData($id);
        }
      }
      $this->Ajax->activate('_page_body');
    }
    protected function postCredit() {
      Purch_Invoice::copy_to_trans($this->trans);
      if (!$this->checkData()) {
        return;
      }
      if (isset($_POST['invoice_no'])) {
        $invoice_no = Purch_Invoice::add($this->trans, $_POST['invoice_no']);
      } else {
        $invoice_no = Purch_Invoice::add($this->trans);
      }
      $this->trans->clear_items();
      Creditor_Trans::killInstance();
      $this->pageComplete($invoice_no);
    }
    protected function addGlCodeToTrans() {
      $this->Ajax->activate('gl_items');
      $input_error = false;
      $sql         = "SELECT account_code, account_name FROM chart_master WHERE account_code=" . DB::_escape($_POST['gl_code']);
      $result      = DB::_query($sql, "get account information");
      if (DB::_numRows($result) == 0) {
        Event::error(_("The account code entered is not a valid code, this line cannot be added to the transaction."));
        $this->JS->setFocus('gl_code');
        $input_error = true;
      } else {
        $myrow       = DB::_fetchRow($result);
        $gl_act_name = $myrow[1];
        if (!Validation::post_num('amount')) {
          Event::error(_("The amount entered is not numeric. This line cannot be added to the transaction."));
          $this->JS->setFocus('amount');
          $input_error = true;
        }
      }
      if (!Tax_Type::is_tax_gl_unique(Input::_post('gl_code'))) {
        Event::error(_("Cannot post to GL account used by more than one tax type."));
        $this->JS->setFocus('gl_code');
        $input_error = true;
      }
      if ($input_error == false) {
        $this->trans->add_gl_codes_to_trans($_POST['gl_code'], $gl_act_name, $_POST['dimension_id'], $_POST['dimension2_id'], Validation::input_num('amount'), $_POST['memo_']);
        $this->JS->setFocus('gl_code');
      }
    }
    protected function clearFields() {
      unset($_POST['gl_code'], $_POST['dimension_id'], $_POST['dimension2_id'], $_POST['amount'], $_POST['memo_'], $_POST['AddGLCodeToTrans']);
      $this->Ajax->activate('gl_items');
      $this->JS->setFocus('gl_code');
    }
    /**
     * @return bool
     */
    protected function checkData() {
      global $total_grn_value, $total_gl_value;
      if (!$this->trans->is_valid_trans_to_post()) {
        Event::error(_("The credit note cannot be processed because the there are no items or values on the invoice. Credit notes are expected to have a charge."));
        $this->JS->setFocus('');
        return false;
      }
      if (!Ref::is_valid($this->trans->reference)) {
        Event::error(_("You must enter an credit note reference."));
        $this->JS->setFocus('reference');
        return false;
      }
      if (!Ref::is_new($this->trans->reference, ST_SUPPCREDIT)) {
        $this->trans->reference = Ref::get_next(ST_SUPPCREDIT);
      }
      if (!Ref::is_valid($this->trans->supplier_reference)) {
        Event::error(_("You must enter a supplier's credit note reference."));
        $this->JS->setFocus('supplier_reference');
        return false;
      }
      if (!Dates::_isDate($this->trans->tran_date)) {
        Event::error(_("The credit note as entered cannot be processed because the date entered is not valid."));
        $this->JS->setFocus('tran_date');
        return false;
      } elseif (!Dates::_isDateInFiscalYear($this->trans->tran_date)) {
        Event::error(_("The entered date is not in fiscal year."));
        $this->JS->setFocus('tran_date');
        return false;
      }
      if (!Dates::_isDate($this->trans->due_date)) {
        Event::error(_("The invoice as entered cannot be processed because the due date is in an incorrect format."));
        $this->JS->setFocus('due_date');
        return false;
      }
      if ($this->trans->ov_amount < ($total_gl_value + $total_grn_value)) {
        Event::error(
          _(
            "The credit note total as entered is less than the sum of the the general ledger entires (if any) and the charges for goods received. There must be a mistake somewhere, the credit note as entered will not be processed."
          )
        );
        return false;
      }
      return true;
    }
    /**S
     *
     * @param $n
     *
     * @return bool
     */
    protected function checkItemData($n) {
      if (!Validation::post_num('this_quantityCredited' . $n, 0)) {
        Event::error(_("The quantity to credit must be numeric and greater than zero."));
        $this->JS->setFocus('this_quantityCredited' . $n);
        return false;
      }
      if (!Validation::post_num('ChgPrice' . $n, 0)) {
        Event::error(_("The price is either not numeric or negative."));
        $this->JS->setFocus('ChgPrice' . $n);
        return false;
      }
      return true;
    }
    protected function cancelCredit() {
      $this->trans->clear_items();
      unset($_SESSION['delivery_po']);
      unset($_POST['PONumber']);
      unset($_POST['creditor_id']);
      unset($_POST['supplier']);
      Creditor_Trans::killInstance();
      $this->trans = Creditor_Trans::i(true);
      $this->Ajax->activate('_page_body');
    }
    /**
     * @param $n
     */
    protected function commitItemData($n) {
      if ($this->checkItemData($n)) {
        $complete = false;
        $this->trans->add_grn_to_trans(
          $n, $_POST['po_detail_item' . $n], $_POST['item_code' . $n], $_POST['description' . $n], $_POST['qty_recd' . $n], $_POST['prev_quantity_inv' . $n], Validation::input_num('this_quantityCredited' . $n), $_POST['order_price' . $n], Validation::input_num('ChgPrice' . $n), $complete, $_POST['std_cost_unit' . $n], ""
        );
      }
    }
    protected function after() {
      // TODO: Implement after() method.
    }
    /**
     * @internal param $prefix
     * @return bool|mixed
     */
    protected function runValidation() {
      // TODO: Implement runValidation() method.
    }
  }


