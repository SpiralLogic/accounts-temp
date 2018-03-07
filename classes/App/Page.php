<?php
  namespace ADV\App;

  use ADV\App\ADVAccounting;
  use ADV\Core\Session;
  use ADV\Core\Cache;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  use ADV\Core\Files;
  use ADV\Core\Input\Input;
  use ADV\Core\View;
  use ADV\Core\JS;
  use ADV\Core\Ajax;
  use ADV\Core\Config;

  /** **/
  class Page
  {
    /** @var */
    public $encoding;
    /** @var */
    public $ajaxpage;
    public $lang_dir = '';
    /** @var \ADV\App\ADVAccounting */
    protected $App;
    /** @var User */
    protected $User;
    protected $Config;
    protected $sel_app;
    /** @var bool * */
    protected $frame = false;
    /** @var bool * */
    protected $menu = true;
    public static $before_box = '';
    /** @var bool * */
    protected $isIndex = false;
    /** @var array * */
    protected $css = [];
    /** @var bool * */
    protected $header = true;
    /** @var string * */
    protected $theme = 'default';
    /** @var string * */
    protected $title = '';
    /** @var Page */
    public static $i = null;
    protected $JS = null;
    /** @var Dates */
    protected $Dates = null;
    protected $security;
    public $hide_back_link;
    public $renderedjs;
    /**
     * @param \ADV\Core\Session $session
     * @param User              $user
     * @param \ADV\Core\Config  $config
     * @param \ADV\Core\Ajax    $ajax
     * @param \ADV\Core\JS      $js
     * @param Dates             $dates
     */
    public function __construct(Session $session, User $user, Config $config, \ADV\Core\Ajax $ajax, \ADV\Core\JS $js, \ADV\App\Dates $dates) {
      $this->Session = $session;
      $this->User    = $user;
      $this->Config  = $config;
      $this->Ajax    = $ajax;
      $this->JS      = $js;
      $this->Dates   = $dates;
      $this->frame   = isset($_GET['frame']);
    }
    /**
     * @static
     *
     * @param        $title
     * @param string $security
     * @param bool   $no_menu
     * @param bool   $isIndex
     *
     * @return null|Page
     */
    public static function start($title, $security = SA_OPEN, $no_menu = false, $isIndex = false) {
      if (static::$i === null) {
        static::$i = new static(Session::i(), User::_i(), Config::i(), \ADV\Core\Ajax::i(), \ADV\Core\JS::i(), \ADV\App\Dates::i());
      }
      static::$i->init($title, $security, $no_menu, $isIndex);
      return static::$i;
    }
    /**
     * @param      $title
     * @param      $security
     * @param bool $no_menu
     * @param bool $isIndex
     *
     * @internal param $menu
     */
    public function init($title, $security, $no_menu = false, $isIndex = false) {
      $this->title    = $title;
      $this->isIndex  = $isIndex;
      $this->security = $security;
      $this->App      = ADVAccounting::i();
      $path           = explode('/', $_SERVER['DOCUMENT_URI']);
      if (count($path) && isset($this->App->applications[ucfirst($path[1])])) {
        $this->sel_app = $path[1];
      }
      if (!$this->sel_app) {
        $this->sel_app = ($this->User->prefs->startup_tab ? : $this->Config->get('apps.default'));
      }
      $this->ajaxpage = (REQUEST_AJAX || Ajax::_inAjax());
      $this->menu     = ($this->frame) ? false : !$no_menu;
      $this->theme    = $this->User->theme();
      $this->encoding = $_SESSION['language']->encoding;
      $this->lang_dir = $_SESSION['language']->dir;
      if (!$this->ajaxpage) {
        $this->header();
        $this->JS->openWindow(900, 500);
        echo '<div id="header">';
        if ($this->menu) {
          $this->menu_header();
        }
      }
      if (!REQUEST_JSON) {
        $this->errorBox();
      }
      echo "</div>";
      if (!$this->ajaxpage) {
        echo "<div id='wrapper'>";
      }
      if (!REQUEST_JSON) {
        if ($this->title && !$this->isIndex && !$this->frame) {
          echo "<div class='titletext'>{$this->title}</div>";
        }
        Ajax::_start_div('_page_body');
      }
    }
    protected function header() {
      $this->header = true;
      $this->JS->openWindow(900, 500);
      if (!headers_sent()) {
        header("Content-type: text/html; charset={$this->encoding}");
      }
      $header                = new View('header');
      $header['class']       = strtolower($this->sel_app);
      $header['lang_dir']    = $this->lang_dir;
      $header['title']       = $this->title;
      $header['body_class']  = !$this->menu ? 'lite' : '';
      $header['encoding']    = $_SESSION['language']->encoding;
      $header['stylesheets'] = $this->renderCSS();
      $header['scripts']     = [];
      if (class_exists('JS', false)) {
        $header['scripts'] = $this->JS->renderHeader();
      }
      $header->render();
    }
    /**
     * @return array
     */
    protected function renderCSS() {
      $this->css += $this->Config->get('assets.css');
      $path = PATH_THEME . $this->theme . DS;
      $css  = implode(',', $this->css);
      return [$path . $css];
    }
    /**
     * @return string
     */
    protected function menu_header() {
      $menu = $this->Session->get('menu_header');
      if ($menu instanceof View) {
        $menu['activeapp'] = strtolower($this->sel_app);
        return $menu->render();
      }
      $menu                = new View('menu_header');
      $menu['theme']       = $this->User->prefs->theme;
      $menu['company']     = $this->User->company_name;
      $menu['server_name'] = $_SERVER['SERVER_NAME'];
      $menu['username']    = $this->User->username;
      $menu['name']        = $this->User->name;
      /** @var ADVAccounting $application */
      $menuitems = [];
      foreach ($this->App->applications as $app => $config) {
        $item = [];
        if (!$config['enabled']) {
          continue;
        }
        $item['name'] = strtolower($app);
        $item['href'] = '/' . strtolower($app);
        $app          = '\\ADV\\Controllers\\' . $app;
        if (class_exists($app)) {
          /** @var \ADV\App\Controller\Menu $app */
          $app           = new $app($this->Session, $this->User);
          $item['extra'] = $app->getModules();
        }
        $menuitems[] = $item;
      }
      $menu->set('menu', $menuitems);
      $this->Session->set('menu_header', $menu);
      $menu['activeapp'] = strtolower($this->sel_app);
      return $menu->render();
    }
    /**
     * @return \ADV\Core\View
     */
    protected function menu_footer() {
      $footer             = new View('footer');
      $footer['backlink'] = false;
      if ((!$this->isIndex && !$this->hide_back_link)) {
        //  $footer['backlink'] = $this->menu ? _("Back") : _("Close");
      }
      $footer['today']     = $this->Dates->today();
      $footer['now']       = $this->Dates->now();
      $footer['mem']       = Files::convertSize(memory_get_usage(true)) . '/' . Files::convertSize(memory_get_peak_usage(true));
      $footer['load_time'] = $this->Dates->getReadableTime(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);
      $footer['user']      = $this->User->username;
      $footer['footer']    = $this->menu && !REQUEST_AJAX;
      return $footer;
    }
    /**
     * @return mixed
     */
    protected function footer() {
      $validate = [];
      $footer   = $this->menu_footer();
      $footer->set('beforescripts', "_focus = '" . Input::_post('_focus') . "';_validate = " . $this->Ajax->php2js($validate) . ";");
      $this->User->add_js_data();
      $footer->set('sidemenu', ($this->header && $this->menu ? ['bank' => $this->User->hasAccess(SS_GL)] : false));
      $footer->set('JS', $this->JS);
      $footer->set('messages', (!REQUEST_AJAX ? Messages::show() : ''));
      $footer->set('page_body', Ajax::_end_div(true));
      $footer->render();
    }
    public static function footer_exit() {
      static::$i->end_page(true);
      exit;
    }
    public function endExit() {
      $this->end_page(true);
      exit;
    }
    /**
     * @param $hide_back_link
     */
    public function end_page($hide_back_link = false) {
      $this->hide_back_link = $hide_back_link;
      if ($this->frame) {
        $this->hide_back_link = true;
        $this->header         = false;
      }
      $this->footer();
    }
    /**
     * @static
     *
     * @param bool $hide_back_link
     */
    public static function end($hide_back_link = false) {
      if (static::$i) {
        static::$i->end_page($hide_back_link);
      }
    }
    /**
     * @static
     *
     * @param bool $numeric_id
     *
     * @return array
     */
    public static function simple_mode($numeric_id = true) {
      $default     = $numeric_id ? -1 : '';
      $selected_id = Input::_post('selected_id', null, $default);
      foreach (array(ADD_ITEM, UPDATE_ITEM, MODE_RESET, MODE_CLONE) as $m) {
        if (isset($_POST[$m])) {
          Ajax::_activate('_page_body');
          if ($m == MODE_RESET || $m == MODE_CLONE) {
            $selected_id = $default;
          }
          unset($_POST['_focus']);
          return array($m, $selected_id);
        }
      }
      foreach (array(MODE_EDIT, MODE_DELETE) as $m) {
        foreach ($_POST as $p => $pvar) {
          if (strpos($p, $m) === 0) {
            unset($_POST['_focus']); // focus on first form entry
            $selected_id = quoted_printable_decode(substr($p, strlen($m)));
            Ajax::_activate('_page_body');
            return array($m, $selected_id);
          }
        }
      }
      return array('', $selected_id);
    }
    /** @static */
    public function errorBox() {
      printf("<div %s='msgbox'>", REQUEST_AJAX ? 'class' : 'id');
      static::$before_box = ob_get_clean(); // save html content before error box
      ob_start([$this->App, 'flush_handler']);
      echo "</div>";
    }
  }

