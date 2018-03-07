<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.core
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core;

  /**
   * @method static _priceFormat($number)
   * @method static _format($number, $decimals = 0)
   * @method static _percentFormat($number)
   * @method static _qtyFormat($number)
   * @method static _exrateFormat($number)
   * @method static _priceDecimal($number, $dec = null)
   * @method static _round($number, $decimals = 0)
   * @method static Num i()

   */
  class Num {
    use Traits\StaticAccess;

    /** @var int * */
    public $price_dec = 2;
    public $qty_dec = 0;
    /** @var string * */
    public $tho_sep = ',';
    /** @var string * */
    public $dec_sep = '.';
    /** @var string * */
    public $exrate_dec = 4;
    /** @var */
    public $percent_dec;
    /**
     * @static
     *
     * @param     $number
     * @param int $decimals
     *
     * @return float
     */
    public function round($number, $decimals = 0) {
      return round($number, $decimals, PHP_ROUND_HALF_UP);
    }
    /**
     * @static
     *
     * @param     $number
     * @param int $decimals
     *
     * @return int|string
     */
    public function format($number, $decimals = 0) {
      $tsep = $this->tho_sep;
      $dsep = $this->dec_sep;
      //return number_format($number, $decimals, $dsep,	$tsep);
      $delta  = ($number < 0 ? -.0000000001 : .0000000001);
      $number = number_format($number + $delta, $decimals, $dsep, $tsep);
      if ($number == 0) {
        $number = ltrim($number, '-');
      }
      return $number;
    }
    /**
     * @static
     *
     * @param $number
     * @param $dec
     *
     * @return int|string
     */
    public function priceDecimal($number, $dec = null) {
      $dec = $dec !== null ? $dec : $this->price_dec;
      $str = strval($number);
      $pos = strpos($str, '.');
      if ($pos !== false) {
        $len = strlen(substr($str, $pos + 1));
        if ($len > $dec) {
          $dec = $len;
        }
      }
      return $this->format($number, $dec);
    }
    /**
     * @static
     *
     * @param $number
     *
     * @return int|string
     */
    public function priceFormat($number) {
      $number = str_replace($this->tho_sep, '', $number);
      return $this->format($this->round($number, $this->price_dec), $this->price_dec);
    }
    /**
     * @static
     *
     * @param      $number
     * @param null $dec
     *
     * @return int|string
     */
    public function qtyFormat($number, $dec = null) {
      $dec = (int) ($dec === null || $dec === false ? : $this->qty_dec);
      return $this->format($this->round($number, $dec), $dec);
    }
    /**
     * @static
     *
     * @param $number
     *
     * @return int|string
     */
    public function percentFormat($number) {
      return $this->format($number, $this->percent_dec);
    }
    /**
     * @static
     *
     * @param $number
     *
     * @return int|string
     */
    public function exrateFormat($number) {
      return $this->format($number, $this->exrate_dec);
    }
    /**
     * @static
     *
     * @param $number
     *
     * @return string
     * Simple English version of number to words conversion.

     */
    public function toWords($number) {
      $Bn = floor($number / 1000000000); /* Billions (giga) */
      $number -= $Bn * 1000000000;
      $Gn = floor($number / 1000000); /* Millions (mega) */
      $number -= $Gn * 1000000;
      $kn = floor($number / 1000); /* Thousands (kilo) */
      $number -= $kn * 1000;
      $Hn = floor($number / 100); /* Hundreds (hecto) */
      $number -= $Hn * 100;
      $Dn  = (int) floor($number / 10); /* Tens (deca) */
      $n   = $number % 10; /* Ones */
      $res = "";
      if ($Bn) {
        $res .= $this->toWords($Bn) . " Billion";
      }
      if ($Gn) {
        $res .= (empty($res) ? "" : " ") . $this->toWords($Gn) . " Million";
      }
      if ($kn) {
        $res .= (empty($res) ? "" : " ") . $this->toWords($kn) . " Thousand";
      }
      if ($Hn) {
        $res .= (empty($res) ? "" : " ") . $this->toWords($Hn) . " Hundred";
      }
      $ones = array(
        "",
        "One",
        "Two",
        "Three",
        "Four",
        "Five",
        "Six",
        "Seven",
        "Eight",
        "Nine",
        "Ten",
        "Eleven",
        "Twelve",
        "Thirteen",
        "Fourteen",
        "Fifteen",
        "Sixteen",
        "Seventeen",
        "Eightteen",
        "Nineteen"
      );
      $tens = array("", "", "Twenty", "Thirty", "Fourty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety");
      if ($Dn || $n) {
        if (!empty($res)) {
          $res .= " and ";
        }
        if ($Dn < 2) {
          $res .= $ones[$Dn * 10 + $n];
        } else {
          $res .= $tens[$Dn];
          if ($n) {
            $res .= "-" . $ones[$n];
          }
        }
      }
      if (empty($res)) {
        $res = "zero";
      }
      return $res;
    }
    /**
     * @static
     *
     * @param $price
     * @param $round_to
     *
     * @return float|int
     */
    public function toNearestCents($price, $round_to) {
      if ($price == 0) {
        return 0;
      }
      $pow = pow(10, $this->price_dec);
      if ($pow >= $round_to) {
        $mod = ($pow % $round_to);
      } else {
        $mod = ($round_to % $pow);
      }
      if ($mod != 0) {
        $price = ceil($price) - ($pow - $round_to) / $pow;
      } else {
        $price = ceil($price * ($pow / $round_to)) / ($pow / $round_to);
      }
      return $price;
    }
  }
