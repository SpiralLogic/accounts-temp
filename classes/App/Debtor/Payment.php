<?php
  use ADV\Core\DB\DB;
  use ADV\App\Debtor\Debtor;
  use ADV\App\Forms;
  use ADV\Core\Cell;
  use ADV\App\Ref;
  use ADV\App\Dates;

  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  /*
    Write/update customer payment.
  */
  /** **/
  class Debtor_Payment
  {
    /**
     * @static
     *
     * @param     $trans_no
     * @param     $debtor_id
     * @param     $branch_id
     * @param     $bank_account
     * @param     $date_
     * @param     $ref
     * @param     $amount
     * @param     $discount
     * @param     $memo_
     * @param int $rate
     * @param int $charge
     * @param int $tax
     *
     * @return int
     */
    public static function add($trans_no, $debtor_id, $branch_id, $bank_account, $date_, $ref, $amount, $discount, $memo_, $rate = 0, $charge = 0, $tax = 0) {
      $result = DB::_select('trans_no')->from('debtor_trans')->where('debtor_id=', $debtor_id)->andWhere('branch_id=', $branch_id)->andWhere(
        'tran_date=',
        Dates::_dateToSql($date_)
      )->andWhere('type=', ST_CUSTPAYMENT)->andWhere('ov_amount=', $amount)->fetch()->one();
      if ($result && $result['trans_no'] !== Session::_getFlash('customer_payment')) {
        Session::_setFlash('customer_payment', $result['trans_no']);
        Event::warning('A payment for same amount and date already exists for this customer, do you want to process anyway?');
        return false;
      }
      DB::_begin();
      $company_record  = DB_Company::_get_prefs();
      $payment_no      = Debtor_Trans::write(ST_CUSTPAYMENT, $trans_no, $debtor_id, $branch_id, $date_, $ref, $amount, $discount, $tax, 0, 0, 0, 0, 0, 0, $date_, 0, $rate);
      $bank_gl_account = Bank_Account::get_gl($bank_account);
      if ($trans_no != 0) {
        DB_Comments::delete(ST_CUSTPAYMENT, $trans_no);
        Bank_Trans::void(ST_CUSTPAYMENT, $trans_no, true);
        GL_Trans::void(ST_CUSTPAYMENT, $trans_no, true);
        Sales_Allocation::void(ST_CUSTPAYMENT, $trans_no, $date_);
      }
      $total = 0;
      /* Bank account entry first */
      $total += Debtor_TransDetail::add_gl_trans(
        ST_CUSTPAYMENT,
        $payment_no,
        $date_,
        $bank_gl_account,
        0,
        0,
        $amount - $charge,
        $debtor_id,
        "Cannot insert a GL transaction for the bank account debit",
        $rate
      );
      if ($branch_id != ANY_NUMERIC) {
        $branch_data      = Sales_Branch::get_accounts($branch_id);
        $debtors_account  = $branch_data["receivables_account"];
        $discount_account = $branch_data["payment_discount_account"];
        $tax_group        = Tax_Groups::get($branch_data["payment_discount_account"]);
      } else {
        $debtors_account  = $company_record["debtors_act"];
        $discount_account = $company_record["default_prompt_payment_act"];
      }
      if (($discount + $amount) != 0) {
        /* Now Credit Debtors account with receipts + discounts */
        $total += Debtor_TransDetail::add_gl_trans(
          ST_CUSTPAYMENT,
          $payment_no,
          $date_,
          $debtors_account,
          0,
          0,
          -($discount + $amount),
          $debtor_id,
          "Cannot insert a GL transaction for the debtors account credit",
          $rate
        );
      }
      if ($discount != 0) {
        /* Now Debit discount account with discounts allowed*/
        $total += Debtor_TransDetail::add_gl_trans(
          ST_CUSTPAYMENT,
          $payment_no,
          $date_,
          $discount_account,
          0,
          0,
          $discount,
          $debtor_id,
          "Cannot insert a GL transaction for the payment discount debit",
          $rate
        );
      }
      if ($charge != 0) {
        /* Now Debit bank charge account with charges */
        $charge_act = DB_Company::_get_pref('bank_charge_act');
        $total += Debtor_TransDetail::add_gl_trans(
          ST_CUSTPAYMENT,
          $payment_no,
          $date_,
          $charge_act,
          0,
          0,
          $charge,
          $debtor_id,
          "Cannot insert a GL transaction for the payment bank charge debit",
          $rate
        );
      }
      if ($tax != 0) {
        $taxes = Tax_Groups::get_for_item($tax_group);
      }
      /*Post a balance post if $total != 0 */
      GL_Trans::add_balance(ST_CUSTPAYMENT, $payment_no, $date_, -$total, PT_CUSTOMER, $debtor_id);
      /*now enter the bank_trans entry */
      Bank_Trans::add(ST_CUSTPAYMENT, $payment_no, $bank_account, $ref, $date_, $amount - $charge, PT_CUSTOMER, $debtor_id, Bank_Currency::for_debtor($debtor_id), "", $rate);
      DB_Comments::add(ST_CUSTPAYMENT, $payment_no, $date_, $memo_);
      Ref::save(ST_CUSTPAYMENT, $ref);
      DB::_commit();
      return $payment_no;
    }
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     */
    public static function void($type, $type_no) {
      DB::_begin();
      Bank_Trans::void($type, $type_no, true);
      GL_Trans::void($type, $type_no, true);
      Sales_Allocation::void($type, $type_no);
      Debtor_Trans::void($type, $type_no);
      DB::_commit();
    }
    /**
     * @static
     *
     * @param        $customer
     * @param        $credit
     * @param string $parms
     */
    public static function credit_row($customer, $credit, $parms = '') {
      Table::label(
        _("Current Credit:"),
        "<a target='_blank' " . ($credit < 0 ? ' class="redfg openWindow"' : '') . " href='" . e(
          '/sales/search/transactions?frame=1&debtor_id=' . $customer
        ) . "'>" . Num::_priceFormat($credit) . "</a>",
        $parms
      );
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected
     */
    public static function allocations_select($label, $name, $selected = null) {
      if ($label != null) {
        Cell::label($label);
      }
      echo "<td>\n";
      $allocs = array(
        ALL_TEXT => _("All Types"),
        '1'      => _("Sales Invoices"),
        '2'      => _("Overdue Invoices"),
        '3'      => _("Payments"),
        '4'      => _("Credit Notes"),
        '5'      => _("Delivery Notes"),
        '6'      => _("Invoices Only")
      );
      echo Forms::arraySelect($name, $selected, $allocs, ['class' => 'med']);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $debtor_id
     * @param bool $refund
     */
    public static function read_customer_data($debtor_id, $refund = false) {
      if ($refund == false) {
        $myrow = Debtor::get_habit($debtor_id);
        $type  = ST_CUSTPAYMENT;
      } else {
        $sql
                = "SELECT debtors.payment_discount,
                  credit_status.dissallow_invoices
                  FROM debtors, credit_status
                  WHERE debtors.credit_status = credit_status.id
                      AND debtors.debtor_id = " . $debtor_id;
        $result = DB::_query($sql, "could not query customers");
        $myrow  = DB::_fetch($result);
        $type   = ST_CUSTREFUND;
      }
      $_POST['HoldAccount']      = $myrow["dissallow_invoices"];
      $_POST['payment_discount'] = $myrow["payment_discount"];
      $_POST['ref']              = Ref::get_next($type);
    }
    /**
     * @static
     *
     * @param $type
     *
     * @return bool
     */
  }
