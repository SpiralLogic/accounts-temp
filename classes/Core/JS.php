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

  use ADV\App\ADVAccounting;

  /**
   * @method static \ADV\Core\JS i()
   * @method static JS _openWindow($width, $height)
   * @method static JS _setFocus($selector, $cached = false)
   * @method static JS _headerFile($file)
   * @method static JS _footerFile($file)
   * @method static JS _onload()
   * @method static JS _redirect($url)
   * @method static JS _renderJSON()
   * @method static JS _renderStatus(Status $status)
   * @method static JS _autocomplete()
   * @method static JS _addLive($action, $clean = false)
   * @method static JS _beforeload($JS_ = false)
   * @method static JS _addLiveEvent($selector, $type, $action, $delegate = false, $cached = false)
   * @method static JS _defaultFocus($name = null)
   */
  class JS
  {
    use Traits\StaticAccess;

    /** @var array * */
    private $beforeload = [];
    /** @var array * */
    private $onload = [];
    /** @var array * */
    private $onlive = [];
    /** @var array * */
    private $headerFiles = [];
    /** @var array * */
    private $footerFiles = [];
    /** @var bool * */
    private $focus = false;
    /** @var bool * */
    public $outputted = false;
    /** @var bool * */
    protected $openWindow = false;
    public $apikey;
    /**
     * @static
     *
     * @param $width
     * @param $height
     *
     * @return mixed
     */
    public function openWindow($width, $height) {
      if ((bool) $this->openWindow) {
        return;
      }
      $js = "Adv.hoverWindow.init($width,$height);";
      $this->onload($js);
      $this->openWindow = true;
    }
    /**
     * @static
     *
     * @param       $id
     * @param       $callback
     * @param       $type
     * @param array $data
     *
     * @internal param bool $url
     */
    public function autocomplete($id, $callback, $type, $data = []) {
      static $ids = [];
      if (isset($ids[$id])) {
        return;
      }
      $ids[$id] = true;
      $data     = json_encode($data);
      $js       = "Adv.Forms.autocomplete('$id','$type',$callback,$data);";
        $this->addLive($js);
    }
    /**
     * @static
     *
     * @param null $name
     *
     * @return null|string
     * Set default focus on first field $name if not set yet
     * Returns unique name if $name=null

     */
    public function defaultFocus($name = null) {
      if ($name == null) {
        $name = uniqid('_el', true);
      }
      if (!isset($_POST['_focus'])) {
        $this->setFocus($name);
      }
      return $name;
    }
    /**
     * @static
     */
    public function resetFocus() {
      unset($_POST['_focus']);
    }
      /**
     * @static
     */
    public function renderHeader() {
      $scripts = [];
      /** @noinspection PhpDynamicAsStaticMethodCallInspection */
      foreach ($this->headerFiles as $dir => $files) {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $scripts[] = $dir . '/' . implode(',', $files);
      }
      return $scripts;
    }
    /**
     * @static
     */
    public function render($return = false) {
      if ($return) {
        ob_start();
      }
      $files = $content = $onReady = '';
      foreach ($this->footerFiles as $dir => $file) {
          $files .= (new HTML)->script(['src' => $dir . '/' . implode(',', $file)], false);
      }
      echo $files;
      if (!REQUEST_AJAX) {
        $this->beforeload = array_merge($this->beforeload, $this->onload);
        $this->onload     = [];
      }
      if ($this->beforeload) {
        $content .= implode("", $this->beforeload);
      }
      if ($this->onlive) {
        $onReady .= 'Adv.Events.onload(function(){' . implode("", $this->onlive) . '}';
        $onReady .= ',"' . ADVAccounting::i()->getController() . '");';
      }
      if ($this->onload) {
        $onReady .= implode("", $this->onload);
      }
      if (!empty($this->focus)) {
        $onReady .= 'Adv.Forms.setFocus("' . $this->focus . '");';
      }
      if ($onReady != '') {
        $content .= "\n$(function(){ " . $onReady . '});';
      }
      /** @noinspection PhpDynamicAsStaticMethodCallInspection */
      if ($content) {
          echo (new HTML)->script(null, $content, [], false);
      }
      if ($return) {
        return ob_get_clean();
      }
      return true;
    }
    /**
     * @param $status
     *
     * @return mixed
     */
    public function renderStatus(Status $status) {
      $this->renderJSON(['status' => $status->get()]);
    }
    /**
     * @static
     *
     * @param $data
     */
    public function renderJSON($data) {
      $data  = (array) $data;
      $error = Errors::JSONError();
      if (isset($data['status']) && $data['status'] && Errors::dbErrorCount()) {
        $data['status'] = $error;
      } elseif (!isset($data['status']) && Errors::messageCount()) {
        $data['status'] = $error;
      }
      if (!empty($GLOBALS['JsHttpRequest_Active'])) {
        $this->resetFocus();
        Ajax::_addJson(true, null, $data);
        exit();
      }
      ob_end_clean();
      if (!headers_sent()) {
        header('Content-type: application/json');
      }
      $this->utf8($data);
      echo json_encode($data);
      exit();
    }
    /**
     * @param $data
     */
    public function utf8(&$data) {
      if (is_object($data)) {
        $arraydata = [];
        foreach ($data as $k => $v) {
          $arraydata [$k] = $v;
        }
        $data= $arraydata;
      }
      if (is_array($data)) {
        array_walk_recursive(
          $data, [$this, 'utf8']
        );
      } elseif (is_string($data)) {
        $data = utf8_encode($data);
      }
    }
    /**
     * @static
     *
     * @param      $selector
     */
    public function setFocus($selector) {
      if (empty($selector)) {
        return;
      }
      $this->focus = $selector;
      Ajax::_addFocus(true, $selector);
      $_POST['_focus'] = $selector;
    }
    /**
     * @static
     *
     * @param array $options
     * @param array $funcs
     * @param int   $level
     *
     * @return string
     * @return array|mixed|string
     */
    public function arrayToOptions($options = [], $funcs = [], $level = 0) {
      foreach ($options as $key => $value) {
        if (is_array($value)) {
          $ret           = $this->arrayToOptions($value, $funcs, 1);
          $options[$key] = $ret[0];
          $funcs         = $ret[1];
        } else {
          if (substr($value, 0, 9) == 'function(') {
            $func_key         = "#" . uniqid() . "#";
            $funcs[$func_key] = $value;
            $options[$key]    = $func_key;
          }
        }
      }
      if ($level == 1) {
          return [$options, $funcs];
      } else {
        $input_json = json_encode($options);
        foreach ($funcs as $key => $value) {
          $input_json = str_replace('"' . $key . '"', $value, $input_json);
        }
        return $input_json;
      }
    }
    /**
     * @static
     *
     * @param $selector
     * @param $type
     * @param $action
     */
    public function addEvent($selector, $type, $action) {
      $this->onload("$('$selector').bind('$type',function(e){ {$action} }).css('cursor','pointer');");
    }
    /**
     * @static
     *
     * @param      $selector
     * @param      $type
     * @param      $action
     * @param bool $delegate
     * @param bool $cached
     */
    public function addLiveEvent($selector, $type, $action, $delegate = false, $cached = false) {
      if (!$delegate) {
        $this->addLive("$('$selector').bind('$type',function(e){ {$action} });");
      } else {
        $cached = (!$cached) ? "$('$delegate')" : 'Adv.o.' . $delegate;
        $this->register($cached . ".delegate('$selector','$type',function(e){ {$action} } )", $this->onload);
      }
    }
    /**
     * @static
     *
     * @param      $action
     * @param bool $clean
     */
      public function addLive($action) {
          $this->register($action, $this->onlive);
      }
    /**
     * @static
     *
     * @param array $events
     */
    public function addEvents($events = []) {
      if (is_array($events)) {
        foreach ($events as $event) {
          if (count($event) == 3) {
              call_user_func_array([$this, 'addEvent'], $event);
          }
        }
      }
    }
    /**
     * @static
     *
     * @param bool $js
     *
     * @return \ADV\Core\JS
     */
    public function onload($js = false) {
      if ($js) {
        $this->register($js, $this->onload);
      }
      return $this;
    }
    /**
     * @static
     *
     * @param bool $js
     */
    public function beforeload($js = false) {
      if ($js) {
        $this->register($js, $this->beforeload);
      }
    }
    /**
     * @static
     *
     * @param $file
     */
    public function headerFile($file) {
      $this->registerFile($file, $this->headerFiles);
    }
    /**
     * @static
     *
     * @param $file
     */
    public function footerFile($file) {
      $this->registerFile($file, $this->footerFiles);
    }
    /**
     * @static
     *
     * @param bool $message
     */
    public function onUnload($message = false) {
      if ($message) {
        $this->addLiveEvent(':input', 'change', "Adv.Events.onLeave('$message')", 'wrapper', true);
        $this->addLiveEvent('form', 'submit', "Adv.Events.onLeave()", 'wrapper', true);
      }
    }
    /**
     * @static
     *
     * @param $url
     */
    public function redirect($url) {
        $data['status'] = ['status' => 'redirect', 'message' => $url];
        $this->renderJSON($data);
    }
    /**
     * @return array
     */
    public function getState() {
      $state = get_object_vars($this);
      return $state;
    }
    /**
     * @param array $state
     */
    public function addState(Array $state = []) {
      foreach ($state as $property => $value) {
        if (property_exists($this, $property)) {
          if (is_array($this->$property)) {
            $this->$property = array_merge_recursive($this->$property, $value);
            continue;
          }
          $this->$property = $value;
        }
      }
    }
    /**
     * @static
     *
     * @param array|bool $js
     * @param            $var
     */
    protected function register($js = false, &$var) {
      if (is_array($js)) {
        foreach ($js as $j) {
          $this->register($j, $var);
        }
      } else {
        $js = rtrim($js, ';') . ';';
          array_push($var, str_replace(['<script>', '</script>'], '', $js));
      }
    }
    /**
     * @static
     *
     * @param array|bool $file
     * @param            $var
     */
    protected function registerFile($file, &$var) {
      if (is_array($file)) {
        foreach ($file as $f) {
          $this->registerFile($f, $var);
        }
      } else {
        $dir  = dirname($file);
        $file = basename($file);
        isset($var[$dir]) or $var[$dir] = [];
        $var[$dir][$file] = $file;
      }
    }
  }

