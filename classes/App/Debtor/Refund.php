<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  /*
         Write/update customer refund.
       */
  class Debtor_Refund {
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
     *
     * @return int
     */
    public static function add($trans_no, $debtor_id, $branch_id, $bank_account, $date_, $ref, $amount, $discount, $memo_, $rate = 0, $charge = 0) {
      $amount = $amount * -1;
      DB::_begin();
      $company_record  = DB_Company::_get_prefs();
      $refund_no       = Debtor_Trans::write(ST_CUSTREFUND, $trans_no, $debtor_id, $branch_id, $date_, $ref, $amount, $discount, 0, 0, 0, 0, 0, 0, 0, "", 0, $rate);
      $bank_gl_account = Bank_Account::get_gl($bank_account);
      if ($trans_no != 0) {
        DB_Comments::delete(ST_CUSTREFUND, $trans_no);
        Bank_Trans::void(ST_CUSTREFUND, $trans_no, true);
        GL_Trans::void(ST_CUSTREFUND, $trans_no, true);
        Sales_Allocation::void(ST_CUSTREFUND, $trans_no, $date_);
      }
      $total = 0;
      /* Bank account entry first */
      $total += Debtor_TransDetail::add_gl_trans(
        ST_CUSTREFUND,
        $refund_no,
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
      } else {
        $debtors_account  = $company_record["debtors_act"];
        $discount_account = $company_record["default_prompt_payment_act"];
      }
      if (($discount + $amount) != 0) {
        /* Now Credit Debtors account with receipts + discounts */
        $total += Debtor_TransDetail::add_gl_trans(
          ST_CUSTREFUND,
          $refund_no,
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
      if ($charge != 0) {
        /* Now Debit bank charge account with charges */
        $charge_act = DB_Company::_get_pref('bank_charge_act');
        $total += Debtor_TransDetail::add_gl_trans(
          ST_CUSTREFUND,
          $refund_no,
          $date_,
          $charge_act,
          0,
          0,
          $charge,
          $debtor_id,
          "Cannot insert a GL transaction for the refund bank charge debit",
          $rate
        );
      }
      /*Post a balance post if $total != 0 */
      GL_Trans::add_balance(ST_CUSTREFUND, $refund_no, $date_, -$total, PT_CUSTOMER, $debtor_id);
      /*now enter the bank_trans entry */
      Bank_Trans::add(ST_CUSTREFUND, $refund_no, $bank_account, $ref, $date_, $amount - $charge, PT_CUSTOMER, $debtor_id, Bank_Currency::for_debtor($debtor_id), "", $rate);
      DB_Comments::add(ST_CUSTREFUND, $refund_no, $date_, $memo_);
      Ref::save(ST_CUSTREFUND, $refund_no, $ref);
      DB::_commit();
      return $refund_no;
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
  }
