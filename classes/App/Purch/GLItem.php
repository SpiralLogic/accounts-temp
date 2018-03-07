<?php
  use ADV\App\Dimensions;
  use ADV\Core\Cell;
  use ADV\Core\Table;
  use ADV\App\User;
  use ADV\App\Validation;
  use ADV\Core\Ajax;
  use ADV\Core\Input\Input;
  use ADV\App\Display;
  use ADV\App\Forms;
  use ADV\App\Creditor\Creditor;
  use ADV\App\Tax\Tax;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Purch_GLItem {
    /* Contains relavent information from the purch_order_details as well to provide in cached form,
all the info to do the necessary entries without looking up ie additional queries of the database again */
    /** @var */
    public $id;
    /** @var */
    public $po_detail_item;
    /** @var */
    public $item_code;
    /** @var */
    public $description;
    /** @var */
    public $qty_recd;
    /** @var */
    public $prev_quantity_inv;
    /** @var */
    public $this_quantity_inv;
    /** @var */
    public $order_price;
    /** @var */
    public $chg_price;
    /** @var null * */
    public $exp_price;
    /** @var int * */
    public $discount;
    /** @var */
    public $Complete;
    /** @var */
    public $std_cost_unit;
    /** @var */
    public $gl_code;
    /** @var */
    public $freight;
    /**
     * @param      $id
     * @param      $po_detail_item
     * @param      $item_code
     * @param      $description
     * @param      $qty_recd
     * @param      $prev_quantity_inv
     * @param      $this_quantity_inv
     * @param      $order_price
     * @param      $chg_price
     * @param      $Complete
     * @param      $std_cost_unit
     * @param      $gl_code
     * @param int  $discount
     * @param null $exp_price
     */
    public function __construct(
      $id,
      $po_detail_item,
      $item_code,
      $description,
      $qty_recd,
      $prev_quantity_inv,
      $this_quantity_inv,
      $order_price,
      $chg_price,
      $Complete,
      $std_cost_unit,
      $gl_code,
      $discount = 0,
      $exp_price = null
    ) {
      $this->id                = $id;
      $this->po_detail_item    = $po_detail_item;
      $this->item_code         = $item_code;
      $this->description       = $description;
      $this->qty_recd          = $qty_recd;
      $this->prev_quantity_inv = $prev_quantity_inv;
      $this->this_quantity_inv = $this_quantity_inv;
      $this->order_price       = $order_price;
      $this->chg_price         = $chg_price;
      $this->exp_price         = ($exp_price == null) ? $chg_price : $exp_price;
      $this->discount          = $discount;
      $this->Complete          = $Complete;
      $this->std_cost_unit     = $std_cost_unit;
      $this->gl_code           = $gl_code;
    }
    /**
     * @param $freight
     */
    public function setFreight($freight) {
      $this->freight = $freight;
    }
    /**
     * @param      $tax_group_id
     * @param null $tax_group
     *
     * @return int
     */
    public function full_charge_price($tax_group_id, $tax_group = null) {
      return Tax::full_price_for_item($this->item_code, $this->chg_price * (1 - $this->discount), $tax_group_id, 0, $tax_group);
    }
    /**
     * @param      $tax_group_id
     * @param null $tax_group
     *
     * @return float|int
     */
    public function taxfree_charge_price($tax_group_id, $tax_group = null) {
      //		if ($tax_group_id==null)
      //			return $this->chg_price;
      return Tax::tax_free_price($this->item_code, $this->chg_price * (1 - $this->discount / 100), $tax_group_id, 0, $tax_group);
    }
    /**
     * @static
     *
     * @param $creditor_trans
     *
     * @internal param $k
     */
    public static function display_controls($creditor_trans) {
      $accs             = Creditor::get_accounts_name($creditor_trans->creditor_id);
      $_POST['gl_code'] = $accs['purchase_account'];
      echo GL_UI::all('gl_code', null, true, true);
      $dim = DB_Company::_get_pref('use_dimension');
      if ($dim >= 1) {
        Dimensions::cells(null, 'dimension_id', null, true, " ", false, 1);
        Forms::hidden('dimension_id', 0);
      }
      if ($dim > 1) {
        Dimensions::cells(null, 'dimension2_id', null, true, " ", false, 2);
        Forms::hidden('dimension2_id', 0);
      }
      Forms::textareaCells(null, 'memo_', null, 50, 1);
      Forms::amountCells(null, 'amount');
      Forms::submitCells('AddGLCodeToTrans', _("Add"), "", _('Add GL Line'), true);
      Forms::submitCells('ClearFields', _("Reset"), "", _("Clear all GL entry fields"), true);
      echo '</tr>';
    }
    // $mode = 0 none at the moment
    //		 = 1 display on invoice/credit page
    //		 = 2 display on view invoice
    //		 = 3 display on view credit
    /**
     * @static
     *
     * @param     $creditor_trans
     * @param int $mode
     *
     * @return int
     */
    public static function display_items($creditor_trans, $mode = 0) {
      // if displaying in form, and no items, exit
      if (($mode == 2 || $mode == 3) && count($creditor_trans->gl_codes) == 0) {
        return 0;
      }
      if ($creditor_trans->is_invoice) {
        $heading = _("GL Items for this Invoice");
      } else {
        $heading = _("GL Items for this Credit Note");
      }
      Display::heading($heading);
      if ($mode == 1) {
        $qes = GL_QuickEntry::has(QE_SUPPINV);
        if ($qes !== false) {
          echo "<div class='center'>";
          echo _("Quick Entry:") . "&nbsp;";
          echo GL_QuickEntry::select('qid', null, QE_SUPPINV, true);
          $qid = GL_QuickEntry::get(Input::_post('qid'));
          if (Forms::isListUpdated('qid')) {
            unset($_POST['total_amount']); // enable default
            Ajax::_activate('total_amount');
          }
          echo "&nbsp;" . $qid['base_desc'] . ":&nbsp;";
          $amount = Validation::input_num('total_amount', $qid['base_amount']);
          $dec    = User::_price_dec();
          echo "<input class='amount' type='text' name='total_amount' data-dec='$dec' maxlength='12'  value='$amount'>&nbsp;";
          Forms::submit('go', _("Go"), true, false, true);
          echo "</div>";
        }
      }
      Ajax::_start_div('gl_items');
      Table::start('padded grid width80');
      $dim = DB_Company::_get_pref('use_dimension');
      if ($dim == 2) {
        $th = array(_("Account"), _("Name"), _("Dimension") . " 1", _("Dimension") . " 2", _("Memo"), _("Amount"));
      } else {
        if ($dim == 1) {
          $th = array(_("Account"), _("Name"), _("Dimension"), _("Memo"), _("Amount"));
        } else {
          $th = array(_("Account"), _("Name"), _("Memo"), _("Amount"));
        }
      }
      if ($mode == 1) {
        $th[] = "";
        $th[] = "";
      }
      Table::header($th);
      $total_gl_value = 0;
      $i              = $k = 0;
      if (count($creditor_trans->gl_codes) > 0) {
        foreach ($creditor_trans->gl_codes as $entered_gl_code) {
          if ($mode == 3) {
            $entered_gl_code->amount = -$entered_gl_code->amount;
          }
          Cell::label($entered_gl_code->gl_code);
          Cell::label($entered_gl_code->gl_act_name);
          if ($dim >= 1) {
            Cell::label(Dimensions::get_string($entered_gl_code->gl_dim, true));
          }
          if ($dim > 1) {
            Cell::label(Dimensions::get_string($entered_gl_code->gl_dim2, true));
          }
          Cell::label($entered_gl_code->memo_);
          Cell::amount($entered_gl_code->amount, true);
          if ($mode == 1) {
            Forms::buttonDeleteCell("Delete2" . $entered_gl_code->counter, _("Delete"), _('Remove line from document'));
            Cell::label("");
          }
          echo '</tr>';
          /////////// 2009-08-18 Joe Hunt
          if ($mode > 1 && !Tax::is_account($entered_gl_code->gl_code)) {
            $total_gl_value += $entered_gl_code->amount;
          } else {
            $total_gl_value += $entered_gl_code->amount;
          }
          $i++;
          if ($i > 15) {
            $i = 0;
            Table::header($th);
          }
        }
      }
      if ($mode == 1) {
        Purch_GLItem::display_controls($creditor_trans, $k);
      }
      $colspan = ($dim == 2 ? 5 : ($dim == 1 ? 4 : 3));
      Table::label(
        _("Total"),
        Num::_priceFormat($total_gl_value),
        "colspan=" . $colspan . " class='alignright bold'",
        "nowrap class='alignright bold'",
        ($mode == 1 ? 3 : 0)
      );
      Table::end(1);
      Ajax::_end_div();
      return $total_gl_value;
    }
  }

