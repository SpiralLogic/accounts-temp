<?php
  use ADV\App\Display;

  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Purch_UI
  {
    // SUPPLIERS
    /**
     * @static
     *
     * @param        $supplier
     * @param        $credit
     * @param string $parms
     */
    public static function credit_row($supplier, $credit, $parms = '') {
      Table::label(
        _("Current Credit:"),
        "<a target='_blank' " . ($credit < 0 ? 'class="redfg openWindow"' : '') . "href='/purchases/search/transactions?creditor_id=" . $supplier . "' >" . Num::_priceFormat(
          $credit
        ) . "</a>",
        $parms
      );
    }
    /**
     * @param $type
     * @param $trans_no
     *
     * @return null|string
     */
    public static function transLink($type, $trans_no) {
      $viewer = "/purchases/view/";
      switch ($type) {
        case ST_PURCHORDER:
          $viewer .= "view_po.php";
          break;
        case ST_SUPPINVOICE:
          $viewer .= "view_supp_invoice.php";
          break;
        case ST_SUPPCREDIT:
          $viewer .= "view_supp_credit.php";
          break;
        case ST_SUPPAYMENT:
          $viewer .= "view_supp_payment.php";
          break;
        case ST_SUPPRECEIVE:
          $viewer .= "view_grn.php";
          break;
        default:
          return null;
      }
      $viewer .= "?trans_no=$trans_no";
      return $viewer;
    }
    /**
     * @static
     *
     * @param        $type
     * @param        $trans_no
     * @param string $label
     * @param bool   $icon
     * @param string $class
     * @param string $id
     * @param bool   $raw
     *
     * @return null|string
     */
    public static function viewTrans($type, $trans_no, $label = "", $icon = false, $class = '', $id = '', $raw = false) {
      $url = Purch_UI::transLink($type, $trans_no);
      if ($raw || $url === null) {
        return $url;
      }
      if ($label == "") {
        $label = $trans_no;
      }
      return Display::viewer_link($label, $url, $class, $id, $icon);
    }
  }
