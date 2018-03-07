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
  namespace ADV\App;

  use ADV\Core\Config;
  use ADV\Core\DB\DB;

  /** **/
  class UserPrefs
  {
    use \ADV\Core\Traits\SetFromArray;

    /** @var Array|mixed * */
    public $language;
    /** @var */
    public $qty_dec;
    /** @var int * */
    public $price_dec = 2;
    /** @var */
    public $exrate_dec = 4;
    /** @var int * */
    public $percent_dec = 0;
    /** @var */
    public $show_gl;
    /** @var */
    public $show_codes;
    /** @var Array|mixed * */
    public $date_format = 1;
    /** @var Array|mixed * */
    public $date_sep = 0;
    /** @var int * */
    public $tho_sep;
    /** @var int * */
    public $dec_sep = 0;
    /** @var string * */
    public $theme = 'default';
    /** @var */
    public $print_profile;
    /** @var */
    public $rep_popup;
    /** @var */
    public $page_size; // for printing
    /** @var */
    public $show_hints;
    /** @var */
    public $query_size = 2; // table pager page length
    /** @var */
    public $graphic_links; // use graphic links
    /** @var int * */
    public $sticky_doc_date = 0; // save date on subsequent document entry
    /** @var Array|mixed * */
    public $startup_tab; // default start-up menu tab
    /**
     * @param null $user
     */
    public function __construct($user = null) {
      if ($user == null) {
        // set default values, used before login
        $this->date_sep    = Config::_get('date.ui_separator');
        $this->date_format = Config::_get('date.ui_format');
        $this->language    = Config::_get('default.language');
        $this->startup_tab = Config::_get('apps.default');
      } else {
        $this->setFromArray($user);
        //     $_SESSION['language']->setLanguage($this->language);
        $tho_seps       = Config::_get('separators_thousands');
        $this->tho_sep  = $tho_seps[$this->tho_sep];
        $dec_seps       = Config::_get('separators_decimal');
        $this->dec_sep  = $dec_seps[$this->dec_sep];
        $date_seps      = Config::_get('date.separators');
        $this->date_sep = $date_seps[$this->date_sep];
      }
    }
    /**
     * @return string
     */
    public function date_display() {
      $date_seps = Config::_get('date.separators');
      $sep       = $date_seps[$this->date_sep];
      if ($this->date_format == 0) {
        return "m" . $sep . "d" . $sep . "Y";
      } elseif ($this->date_format == 1) {
        return "d" . $sep . "m" . $sep . "Y";
      } else {
        return "Y" . $sep . "m" . $sep . "d";
      }
    }
    /**
     * @return mixed
     */
    public function tho_sep() {
    }
    /**
     * @return mixed
     */
    public function dec_sep() {
    }
    public function date_sep() {
    }
    /**
     * @static
     */
    public function  update($id, Array $prefs) {
      $this->setFromArray($prefs);
      if (isset($prefs['tho_sep'])) {
        $prefs['tho_sep'] = array_search($prefs['tho_sep'], Config::_get('separators_thousands'));
      }
      if (isset($prefs['dec_sep'])) {
        $prefs['tho_sep'] = array_search($prefs['dec_sep'], Config::_get('separators_decimal'));
      }
      if (isset($prefs['date_sep'])) {
        $prefs['date_sep'] = array_search($prefs['date_sep'], Config::_get('date.separators'));
      }
      DB::_update('users')->values($prefs)->where('id=', $id)->exec();
      session_regenerate_id();
    }
  }
