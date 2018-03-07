<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  // Link to printing single document with bulk report template file.
  // Ex. Cell::label(static::print_doc_link($myrow['order_no'], _("Print")), $type);
  // or Event::warning(static::print_doc_link($order_no, _("Print this order")));
  // You only need full parameter list for invoices/credit notes
  namespace ADV\App;

  use ADV\Core\HTML;
  use ADV\Core\JS;
  use ADV\Core\Config;
  use ADV\App\UI;

  /** **/
  class Reporting
  {
    static $debug = null;
    /** @var User */
    static $User = null;
    /** @var \ADV\Core\JS */
    static $JS = null;
    /**
     * @static
     *
     * @param        $doc_no
     * @param        $link_text
     * @param bool   $link
     * @param        $type_no
     * @param bool   $icon
     * @param string $class
     * @param string $id
     * @param int    $email
     * @param int    $extra
     * @param bool   $raw
     *
     * @return string
     */
    public static function print_doc_link(
      $doc_no,
      $link_text,
      $link = true,
      $type_no,
      $icon = false,
      $class = 'button printlink',
      $id = '',
      $email = 0,
      $extra = 0,
      $raw = false
    ) {
      $url     = '/reporting/prn_redirect.php?';
      $options = static::print_option_array($type_no, $doc_no, $email, $extra);
      $ar      = $options[0];
      $rep     = $options[1];
      return static::print_link($link_text, $rep, $ar, "", $icon, $class, $id, $raw);
    }
    /**
     * @static
     *
     * @param     $type_no
     * @param     $doc_no
     * @param int $email
     * @param int $extra
     *
     * @return array
     */
    public static function print_option_array($type_no, $doc_no, $email = 0, $extra = 0) {
      $ar  = [];
      $rep = '';
      switch ($type_no) {
        case ST_SALESQUOTE :
          $rep = 111;
          // from, to, currency, bank acc, email, quote, comments
          $ar = array(
            'PARAM_0' => $doc_no,
            'PARAM_1' => $doc_no,
            'PARAM_2' => '',
            'PARAM_3' => $email,
            'PARAM_4' => ''
          );
          break;
        case ST_SALESORDER :
          $rep = 109;
          // from, to, currency, bank acc, email, quote, comments
          $ar = array(
            'PARAM_0' => $doc_no,
            'PARAM_1' => $doc_no,
            'PARAM_2' => '',
            'PARAM_3' => $email,
            'PARAM_4' => 0,
            'PARAM_5' => ''
          );
          break;
        case ST_CUSTDELIVERY :
          $rep = 110;
          // from, to, email, comments
          $ar = array(
            'PARAM_0' => $doc_no,
            'PARAM_1' => $doc_no,
            'PARAM_2' => $email,
            'PARAM_3' => $extra
          );
          break;
        case ST_SALESINVOICE : // Sales Invoice
        case ST_CUSTCREDIT : // Customer Credit Note
          $rep = 107;
          // from, to, currency, bank acc, email, paylink, comments, type
          $ar = array(
            'PARAM_0' => $doc_no,
            'PARAM_1' => $doc_no,
            'PARAM_2' => '',
            'PARAM_3' => $email,
            'PARAM_4' => '',
            'PARAM_5' => '',
            'PARAM_6' => $type_no
          );
          break;
        case ST_PURCHORDER :
          $rep = 209;
          // from, to, currency, bank acc, email, comments
          $ar = array(
            'PARAM_0' => $doc_no,
            'PARAM_1' => $doc_no,
            'PARAM_2' => '',
            'PARAM_3' => $email,
            'PARAM_4' => ''
          );
          break;
        case ST_CUSTPAYMENT :
          $rep = 112;
          // from, to, currency, bank acc, email, comments
          $ar = array(
            'PARAM_0' => $doc_no,
            'PARAM_1' => $doc_no,
            'PARAM_2' => '',
            'PARAM_4' => ''
          );
          break;
        case ST_STATEMENT :
          $rep = 108;
          // from, to, currency, bank acc, email, comments
          $ar = array(
            'PARAM_0' => $extra,
            'PARAM_1' => 0,
            'PARAM_2' => 0,
            'PARAM_4' => 0,
            'PARAM_6' => 0,
            'PARAM_5' => 0,
          );
          break;
        case ST_CUSTREFUND :
          $rep = 113;
          // from, to, currency, bank acc, email, comments
          $ar = array(
            'PARAM_0' => $doc_no,
            'PARAM_1' => $doc_no,
            'PARAM_2' => '',
            'PARAM_4' => ''
          );
          break;
        case ST_PROFORMA :
          $rep = 129;
          // from, to, currency, bank acc, email, comments
          $ar = array(
            'PARAM_0' => $doc_no,
            'PARAM_1' => $doc_no,
            'PARAM_2' => '',
            'PARAM_3' => $email,
            'PARAM_4' => '2'
          );
          break;
        case ST_PROFORMAQ :
          $rep = 131;
          // from, to, currency, bank acc, email, comments
          $ar = array(
            'PARAM_0' => $doc_no,
            'PARAM_1' => $doc_no,
            'PARAM_2' => '',
            'PARAM_3' => $email,
            'PARAM_4' => '3'
          );
          break;
        case ST_SUPPAYMENT :
          $rep = 210;
          // from, to, currency, bank acc, email, comments
          $ar = array(
            'PARAM_0' => $doc_no,
            'PARAM_1' => $doc_no,
            'PARAM_2' => '',
            'PARAM_3' => $email,
            'PARAM_4' => ''
          );
          break;
        //		default: $ar = [];
      }
      return array($ar, $rep);
    }
    /**
     * @static
     *
     * @param        $doc_no
     * @param        $link_text
     * @param bool   $link
     * @param        $type_no
     * @param string $class
     * @param string $id
     * @param array  $emails
     * @param int    $extra
     * @param bool   $return
     *
     * @return bool|string
     */
    public static function email_link($doc_no, $link_text, $link = true, $type_no, $class = 'EmailLink', $id = '', $emails = [], $extra = 0, $return = false) {
      if (empty($emails)) {
        return false;
      }
      if (static::$debug === null) {
        static::$debug = Config::_get('debug.pdf');
      }
      if (!static::$JS) {
        static::$JS = JS::i();
      }
      $url     = '/reporting/prn_redirect.php?';
      $options = static::print_option_array($type_no, $doc_no, 1, $extra);
      $ars     = $options[0];
      $rep     = $options[1];
      foreach ($ars as $ar => $val) {
        $ars[$ar] = "$ar=" . urlencode($val);
      }
      $ars[] = 'REP_ID=' . urlencode($rep);
      $url .= implode('&', $ars);
      $html = new HTML;
      $html->br()->p(['class' => 'center']);
      UI::select('EmailSelect' . $type_no, $emails, ['style' => 'max-width:400px'], null, $html)->br;
      $html->button(
        'EmailButton' . $type_no, $link_text, array(
                                                   'style'    => 'margin:20px',
                                                   'data-url' => $url,
                                              ), false
      )->p;
      $js
        = <<<JS
		$('#EmailButton$type_no').click(function() {
		if (!confirm("Send email now?")) { return false;}
			var email = $("#EmailSelect$type_no").val();
				$.getJSON($(this).data('url') + "&Email="+email);
		Adv.loader.on(65000);
			Adv.o.\$emailBox.dialog("close");
		return false;
		});
JS;
      if ($return) {
        return $html->script('null', $js, false)->__tostring();
      }
      echo $html;
      static::$JS->onload($js);
    }
    /**
     * Universal link to any kind of report.
     * @static
     *
     * @param        $link_text
     * @param        $rep
     * @param array  $pars
     * @param string $dir
     * @param bool   $icon
     * @param string $class
     * @param string $id
     * @param bool   $raw
     *
     * @return string
     */
    public static function print_link($link_text, $rep, $pars = [], $dir = '', $icon = false, $class = 'printlink', $id = '', $raw = false) {
      if (!static::$User) {
        static::$User = User::_i();
      }
      if (!static::$JS) {
        static::$JS = JS::i();
      }
      if (static::$debug === null) {
        static::$debug = Config::_get('debug.pdf');
      }
      $url = $dir ? : ROOT_URL . 'reporting/prn_redirect.php?';
      $id  = static::$JS->defaultFocus($id);
      foreach ($pars as $par => $val) {
        $pars[$par] = "$par=" . urlencode($val);
      }
      $pars[] = 'REP_ID=' . urlencode($rep);
      $url .= implode('&', $pars);
      if ($class != '' && !static::$debug) {
        $class = "class='" . e($class) . "'";
      }
      if ($id) {
        $id = "id='" . e($id) . "'";
      }
      $pars = Display::access_string($link_text);
      if (static::$User->graphic_links() && $icon) {
        $pars[0] = Forms::setIcon($icon, $pars[0]);
      }
      if ($raw) {
        return $url;
      }
      return "<a target='_blank' href='" . e($url) . "' $id $class $pars[1]>$pars[0]</a>";
    }
    /**
     * @static
     *
     * @param        $id
     * @param        $type
     * @param        $type_no
     * @param string $text
     *
     * @return \ADV\Core\HTML|string
     */
    public static function emailDialogue($id, $type, $type_no, $text = "Email") {
      return (new HTML)->button(
        false, $text, array(
                           'class'        => 'button email-button',
                           'data-emailid' => $id . '-' . $type . '-' . $type_no
                      ), false
      )->__toString();
    }
  }
