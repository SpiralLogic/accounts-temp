<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App;

  use ADV\Core\Session;
  use ADV\Core\Auth;
  use ADV\Core\JS;
  use ADV\Core\Event;
  use DB_Company;
  use ADV\Core\DB\DB;
  use ADV\Core\Traits\StaticAccess;

  /**
   * @method static _theme
   * @method User ii()
   * @method static \ADV\App\User _i()
   * @method static _logout()
   * @method static _date_format()
   * @method static _date_sep()
   * @method static int _qty_dec()
   * @method static _price_dec()
   * @method static _numeric($input)
   * @method static _print_profile()
   * @method static _page_size()
   * @method static _show_gl()
   * @method static _rep_popup()
   * @method static _percent_dec()
   *  @method static _graphic_links()
   */
  class User extends \ADV\App\DB\Base implements \ADV\App\Pager\Pageable
  {
    use \ADV\Core\Traits\Hook;
    use StaticAccess;

    public $user;
    public $id = 0;
    public $user_id;
    /** @var string * */
    public $loginname;
    /** @var */
    public $username;
    /** @var */
    public $real_name;
    public $name;
    /** @var string * */
    public $company = 'default';
    public $company_name;
    /** @var */
    public $pos;
    /** @var bool * */
    public $salesmanid = false;
    /** @var */
    public $access;
    /** @var */
    public $timeout;
    /** @var */
    public $last_action;
    /** @var bool * */
    public $logged = false;
    /**@var UserPrefs */
    public $prefs;
    public $phone;
    public $language;
    public $email;
    /** @var bool * */
    public $change_password = false;
    public $selectedApp;
    public $startup_tab = 'Sales';
    public $hash = 'none';
    /** @var */
    public $last_record;
    /** @var \ADV\App\Security */
    public $Security;
    protected $_table = 'users';
    protected $_classname = 'User';
    protected $_id_column = 'id';
    /** @var array * */
    protected $role_set = [];
    /** @var */
    protected $access_sections;
    /**
     */
    public function __construct($id = 0) {
      parent::__construct($id);
      $this->logged   = false;
      $this->password = '';
      $this->prefs    = new UserPrefs();
    }
    /**
     * @return \ADV\App\User
     */
    public static function i() {
      return $_SESSION['User'];
    }
    /**
     * @param bool $inactive
     *
     * @return array
     */
    public static function getAll($inactive = false) {
      $q = DB::_select('users.id', 'user_id', 'real_name', 'phone', 'email', 'last_visit_date', 'role', 'users.inactive')->from('users,security_roles')->where(
             'security_roles.id=users.role_id'
      );
      if (!$inactive) {
        $q->andWhere('users.inactive=', 0);
      }
      return $q->fetch()->all();
    }
    /**
     * @return bool
     */
    public function logged_in() {
      if ($this->timeout()) {
        return false;
      }
      if ($this->logged && date('i', time() - $this->last_record) > 4) {
        $this->last_record = time();
        Event::registerShutdown([$this, '_addLog']);
      }
      return $this->logged;
    }
    /**
     * @return bool
     */
    public function timeout() {
      // skip timeout on logout page
      if ($this->logged) {
        if (time() > $this->last_action + $this->timeout) {
          return true;
        }
      }
      $this->last_action = time();
      return false;
    }
    /**
     * @param $company
     * @param $loginname
     * @param $password
     *
     * @return bool
     */
    public function login($company, $loginname, $password) {
      $this->company = $company;
      $this->logged  = false;
      $auth          = new Auth($loginname);
      if ($auth->isBruteForce()) {
        return false;
      }
      $myrow = $auth->checkUserPassword($loginname, $password);
      if ($myrow) {
        if ($myrow["inactive"]) {
          return false;
        }
        $this->role_set = [];
        $this->access   = $myrow['role_id'];
        $this->hash     = $myrow["hash"];
        $this->Security = new Security();
        // store area codes available for current user role
        $role = $this->Security->get_role($myrow['role_id']);
        if (!$role) {
          return false;
        }
        $this->access_sections = $role['sections'];
        foreach ($role['areas'] as $code) // filter only area codes for enabled security sections
        {
          if (in_array($code & ~0xff, $role['sections'])) {
            $this->role_set[] = $code;
          }
        }
        $this->change_password = $myrow['change_password'];
        $this->logged          = true;
        $this->id = $myrow['id'];
        $this->name            = $myrow['real_name'];
        $this->pos             = $myrow['pos'];
        $this->username        = $this->loginname = $loginname;
        $this->prefs           = new UserPrefs($myrow);
        $this->user            = $myrow['id'];
        $this->last_action     = time();
        $this->timeout         = DB_Company::_get_pref('login_tout');
        $this->company_name    = DB_Company::_get_pref('coy_name');
        $this->salesmanid      = $this->get_salesmanid();
        $this->fireHooks('login');
        Event::registerShutdown(['Users', 'update_visitdate'], [$this->username]);
        Event::registerShutdown([$this, '_addLog']);
      }
      return $this->logged;
    }
    /**
     * @return mixed
     */
    private function get_salesmanid() {
      return DB::_select('salesman_code')->from('salesman')->where('user_id=', $this->user)->fetch()->one('salesman_code');
    }
    /**
     * @param $user_id
     * @param $password
     *
     * @return bool|mixed
     */
    public function  get_for_login($user_id, $password) {
    }
    /**
     * @param       $function
     * @param array $arguments
     *
     * @return void
     */
    public function register_login($function = null, $arguments = []) {
      $this->registerHook('login', $function, $arguments);
    }
    /**
     * @param       $function
     * @param array $arguments
     *
     * @return void
     */
    public function register_logout($function, $arguments = []) {
      $this->registerHook('logout', $function, $arguments);
    }
    public function addLog() {
      DB::_insert('user_login_log')->values(
        [
        'user'    => $this->username,
        'IP'      => Auth::get_ip(),
        'success' => 2
        ]
      ) ->exec();
    }
    /**
     * @param $page_level
     *
     * @return bool
     */
    public function hasAccess($page_level) {
      if ($page_level === SA_OPEN) {
        return true;
      }
      if ($page_level == SA_DENIED) {
        return false;
      }
      return $this->Security->hasAccess($this, $page_level);
    }
    /**
     * @param $section
     *
     * @return bool
     */
    public function hasSectionAccess($section) {
      return isset($this->access_sections) and in_array($section, $this->access_sections);
    }
    /**
     * @param $role
     *
     * @return bool
     */
    public function hasRole($role) {
      return in_array($role, $this->role_set);
    }
    /**
     */
    public function update_prefs($prefs) {
      $this->prefs = new UserPrefs($this->get());
      $this->prefs->update($this->user, $prefs);
    }
    /**
     * @return \ADV\Core\DB\Query\Result
     */
    protected function  get() {
      $sql    = "SELECT * FROM users WHERE id=" . DB::_escape($this->user);
      $result = DB::_query($sql, "could not get user " . $this->user);
      return DB::_fetch($result);
    }
    /**
     * @static
     * @return UserPrefs
     */
    public function prefs() {
      return $this->prefs;
    }
    /**
     * @static
     */
    public function add_js_data() {
      $js = "var user = {" . "ts: '" . $this->prefs->tho_sep //
        . "',ds: '" . $this->prefs->dec_sep //
        . "',pdec: " . $this->prefs->price_dec //
        . "};";
      JS::_beforeload($js);
    }
    /**
     * @static
     *
     * @param $input
     *
     * @return bool|float|int|mixed|string
     */
    public function numeric($input) {
      $num = trim($input);
      $sep = $this->prefs->tho_sep;
      if ($sep != '') {
        $num = str_replace($sep, '', $num);
      }
      $sep = $this->prefs->dec_sep;
      if ($sep != '.') {
        $num = str_replace($sep, '.', $num);
      }
      if (!is_numeric($num)) {
        return false;
      }
      $num = (float) $num;
      if ($num == (int) $num) {
        return (int) $num;
      } else {
        return $num;
      }
    }
    /**
     * @static
     * @return mixed
     */
    public function pos() {
      return $this->pos;
    }
    /**
     * @static
     * @return mixed
     */
    public function language() {
      return $this->prefs->language;
    }
    /**
     * @static
     * @return mixed
     */
    public function qty_dec() {
      return $this->prefs->qty_dec;
    }
    /**
     * @static
     * @return mixed
     */
    public function price_dec() {
      return $this->prefs->price_dec;
    }
    /**
     * @static
     * @return mixed
     */
    public function exrate_dec() {
      return $this->prefs->exrate_dec;
    }
    /**
     * @static
     * @return mixed
     */
    public function percent_dec() {
      return $this->prefs->percent_dec;
    }
    /**
     * @static
     * @return mixed
     */
    public function show_gl() {
      return $this->prefs->show_gl;
    }
    /**
     * @static
     * @return mixed
     */
    public function show_codes() {
      return $this->prefs->show_codes;
    }
    /**
     * @static
     * @return mixed
     */
    public function date_format() {
      return $this->prefs->date_format;
    }
    /**
     * @static
     * @return mixed
     */
    public function date_display() {
      return $this->prefs->date_display();
    }
    /**
     * @static
     * @return int
     */
    public function date_sep() {
      return (isset($_SESSION["User"])) ? $this->prefs->date_sep : 0;
    }
    /**
     * @return int
     */
    public function tho_sep() {
      return $this->prefs->tho_sep;
    }
    /**
     * @static
     * @return mixed
     */
    public function dec_sep() {
      return $this->prefs->dec_sep;
    }
    /**
     * @static
     * @return mixed
     */
    public function theme() {
      return $this->prefs->theme;
    }
    /**
     * @static
     * @return mixed
     */
    public function page_size() {
      return $this->prefs->page_size;
    }
    /**
     * @static
     * @return mixed
     */
    public function hints() {
      return $this->prefs->show_hints;
    }
    /**
     * @static
     * @return mixed
     */
    public function print_profile() {
      return $this->prefs->print_profile;
    }
    /**
     * @static
     * @return mixed
     */
    public function rep_popup() {
      return $this->prefs->rep_popup;
    }
    /**
     * @static
     * @return mixed
     */
    public function query_size() {
      return $this->prefs->query_size;
    }
    /**
     * @static
     * @return mixed
     */
    public function graphic_links() {
      return $this->prefs->graphic_links;
    }
    /**
     * @static
     * @return mixed
     */
    public function sticky_doc_date() {
      return $this->prefs->sticky_doc_date;
    }
    /**
     * @static
     * @return mixed
     */
    public function startup_tab() {
      return $this->prefs->startup_tab;
    }
    public function logout() {
      \ADV\Core\Config::_removeAll();
      Session::_kill();
      $this->logged = false;
    }
    public function getHash() {
      return $this->hash;
    }
    /**
     * @return array
     */
    public function getPagerColumns() {
      $cols = [
        ['type' => "skip"],
        _("User ID"),
        _("Name"),
        _("Phone"),
        _("Email"),
        _("Last Visit Date"),
        _("Role"),
        _('Inactive') => ['type' => 'inactive'],
      ];
      return $cols;
    }
    /**
     * @return \ADV\Core\Traits\Status|bool
     */
    protected function canProcess() {
      if (strlen($this->user_id) < 4) {
        return $this->status(false, "The user login entered must be at least 4 characters long.", 'user_id');
      }
      if ($this->password == '') {
        unset($this->password);
        return true;
      }
      $auth  = new Auth($_POST['user_id']);
      $check = $auth->checkPasswordStrength($this->password, $this->user_id);
      if ($check['error'] > 0) {
        return $this->status(false, $check['text']);
      } elseif ($check['strength'] < 3) {
        return $this->status(false, _("Password Too Weak!"));
      }
      return true;
    }
    /**
     * @param null       $changes
     * @param array|null $changes can take an array of  changes  where key->value pairs match properties->values and applies them before save
     *
     * @return array|bool|int|null
     * @return \ADV\Core\Traits\Status|array|bool|int|string
     */
    public function save($changes = []) {
      $result = parent::save($changes);
      $auth   = new Auth($_POST['user_id']);
      //  $this->status(false, 'Password potentially changed');
      $auth->updatePassword($this->id, $this->password);
      unset($this->password);
      return $result;
    }
  }


