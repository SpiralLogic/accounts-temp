<?php

  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core;

  /** **/
  class Cell
  {
    /**
     * @param        $amount
     * @param string $attrs
     * @param null   $id
     */
    public static function amountDecimal($amount, $attrs = '', $id = null) {
      $amount = Num::_priceDecimal($amount);
      Cell::label($amount, ' class="alignright nowrap"' . $attrs, $id);
    }
    /**
     * @param        $amount
     * @param string $attrs
     * @param null   $id
     */
    /**
     * @param        $amount
     * @param bool   $bold
     * @param string $attrs
     * @param null   $id
     */
    public static function amount($amount, $bold = false, $attrs = '', $id = null) {
      $amount = Num::_priceFormat($amount);
      if ($bold) {
        $amount = "<span class='bold'>" . $amount . "</span>";
      }
      Cell::label($amount, "class='amount' " . $attrs, $id);
    }
    /**
     * @param        $description
     * @param string $attrs
     * @param null   $id
     */
    public static function description($description, $attrs = "", $id = null) {
      Cell::label($description, $attrs . "class='desc' ", $id);
    }
    /**
     * @param        $email
     * @param string $attrs
     * @param null   $id
     */
    public static function email($email, $attrs = "", $id = null) {
      $email = "<a href='mailto:$email'>$email</a>";
      Cell::label($email, $attrs, $id);
    }
    /**
     * @param        $label
     * @param        $value
     * @param string $label_attrs
     * @param string $value_attrs
     * @param null   $id
     */
    public static function labelled($label, $value, $label_attrs = '', $value_attrs = "", $id = null) {
      if ($label) {
        if (strpos($label_attrs, 'class=') === false) {
          $label_attrs .= " class='label'";
        }
        echo "<td $label_attrs>$label</td>";
      }
      Cell::label($value, $value_attrs, $id);
    }
    /**
     * @param        $label
     * @param string $attrs
     */
    public static function labelHeader($label, $attrs = "") {
      echo "<th $attrs>$label</th>";
    }
    /**
     * @param        $label
     * @param string $attrs
     * @param null   $id
     *
     * @return mixed
     */
    public static function label($label, $attrs = "", $id = null) {
      if ($id) {
        $attrs .= " id='$id'";
        Ajax::_addUpdate($id, $id, $label);
      }
      echo "<td $attrs >$label</td>";
      return $label;
    }
    /**
     * @param      $percent
     * @param bool $bold
     * @param null $id
     */
    public static function percent($percent, $bold = false, $id = null) {
      $percent = Num::_percentFormat($percent);
      if ($bold) {
        $percent = "<span class='bold'>" . $percent . "</span>";
      }
      Cell::label($percent . '%', ' class="alignright nowrap"', $id);
    }
    /**
     * @param      $qty
     * @param bool $bold
     * @param int  $dec
     * @param null $id
     */
    public static function qty($qty, $bold = false, $dec = null, $id = null) {
      $qty = Num::_format(Num::_round($qty, $dec), $dec);
      if ($bold) {
        $qty = "<span class='bold'>" . $qty . "</span>";
      }
      Cell::label($qty, ' class="alignright nowrap"', $id);
    }
    /**
     * @param $value
     */
    public static function debitOrCredit($value) {
      if ($value >= 0) {
        Cell::amount($value);
        Cell::label("");
      } elseif ($value < 0) {
        Cell::label("");
        Cell::amount(abs($value));
      }
    }
  }

