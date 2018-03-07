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
   * @method static \ADV\Core\Ajax i()
   * @method static Ajax _activate($trigname)
   * @method static Ajax _redirect($url)
   * @method static Ajax _popup($url)
   * @method static Ajax _addAssign($trigger, $sTarget, $sAttribute, $sData)
   * @method static Ajax _addUpdate($trigger, $sTarget, $sData)
   * @method static Ajax _addEnable($trigger, $sTarget, $sData = true)
   * @method static Ajax _addDisable($trigger, $sTarget, $sData = true)
   * @method static Ajax _addFocus($trigger, $sTarget)
   * @method static Ajax _addDebug($debug)
   * @method static Ajax _addStatus(Status $status)
   * @method static Ajax _run()
   * @method static Ajax _addJson($trigger, $sTarget, $json)
   * @method static Ajax _flush()
   * @method static Ajax _inAjax()
   * @method static Ajax _absoluteURL()
   * @method static Ajax _start_div($id = null, $trigger = null, $non_ajax = false)
   * @method static Ajax _end_div($return_div = false)
   */
  class Ajax extends \JsHttpRequest
  {
    use Traits\StaticAccess;

    /** @var array * */
    protected $aCommands = [];
    /** @var array * */
    protected $triggers = [];
    protected $ajax_divs = [];
    public $debug;
    /**

     */
    public function __construct() {
      $enc = (session_status() == PHP_SESSION_ACTIVE) ? $_SESSION['language']->encoding : 'UTF-8';
      parent::__construct($enc);
    }
    /**
     *   This function is used in ctrl routines to activate
     *   update of ajaxified html element selected by given name/id.
     *
     * @param      $trigname
     * @param null $newurl
     * @param null $historyurl
     * @param      $title
     *
     * @return void
     */
    public function activate($trigname, $newurl = null, $historyurl = null, $title = null) {
      if ($this->inAjax()) {
        $this->triggers[$trigname] = true;
        if ($historyurl) {
          $this->addScript(true, "window.onpopstate=function(event) { window.location.href = '$historyurl';};history.pushState({},'" . $title . "','" . $newurl . "');");
        }
      }
    }
    /**
     *   Javascript clientside redirection.
     *   This is the last command added to reponse (if any).
     *
     * @param $url
     *
     * @return void
     */
    public function redirect($url) {
      if ($this->isActive()) {
        $this->addCommand(true, ['n' => 'rd'], $this->absoluteURL($url));
        $this->run();
      }
    }
    /**
     * Popup window (target=_blank)
     *
     * @param $url
     *
     * @return void
     */
    public function popup($url) {
      $this->addCommand(true, ['n' => 'pu'], $this->absoluteURL($url));
    }
    /**
     * Adds an executable Javascript code.
     *
     * @param $trigger
     * @param $sJS
     *
     * @return Ajax
     */
    public function addScript($trigger, $sJS) {
      $this->addCommand($trigger, ['n' => 'js'], $sJS, 'js');
      return $this;
    }
    /**
     * @param $debug
     *
     * @return Ajax
     */
    public function addDebug($debug) {
      $this->debug[] = $debug;
      return $this;
    }
    /**
     * Assign target attribute with data.
     *
     * @param $trigger
     * @param $sTarget
     * @param $sAttribute
     * @param $sData
     *
     * @return Ajax
     */
    public function addAssign($trigger, $sTarget, $sAttribute, $sData) {
      $this->addCommand(
        $trigger, [
                       'n' => 'as',
                       't' => $sTarget,
                       'p' => $sAttribute
                  ], $sData
      );
      return $this;
    }
    /**
     * Updates input element or label with data.
     *
     * @param $trigger
     * @param $sTarget
     * @param $sData
     *
     * @return Ajax
     */
    public function addUpdate($trigger, $sTarget, $sData) {
      $this->addCommand(
        $trigger, [
                       'n' => 'up',
                       't' => $sTarget
                  ], $sData
      );
      return $this;
    }
    /**
     * Set disable state of element.
     *
     * @param      $trigger
     * @param      $sTarget
     * @param bool $sData
     *
     * @return Ajax
     */
    public function addDisable($trigger, $sTarget, $sData = true) {
      $this->addCommand(
        $trigger, [
                       'n' => 'di',
                       't' => $sTarget
                  ], $sData
      );
      return $this;
    }
    /**
     * Set state of element to enabled.
     *
     * @param      $trigger
     * @param      $sTarget
     * @param bool $sData
     *
     * @return Ajax
     */
    public function addEnable($trigger, $sTarget, $sData = true) {
      $this->addCommand(
        $trigger, [
                       'n' => 'di',
                       't' => $sTarget
                  ], !$sData
      );
      return $this;
    }
    /**
     * @param Status $status
     */
    public function addStatus(Status $status) {
      $this->addJson(true, null, ['status' => $status->get()]);
    }
    /**
     * @param      $trigger
     * @param null $formMethod
     * @param      $json
     */
    public function addJson($trigger, $formMethod = null, $json) {
      $this->addCommand($trigger, ['n' => 'json', 'p' => $formMethod], $json);
    }
    /**
     * Set current focus.
     *
     * @param $trigger
     * @param $sTarget
     *
     * @return Ajax
     */
    public function addFocus($trigger, $sTarget) {
      $this->addCommand($trigger, ['n' => 'fc'], $sTarget, 'fc');
      return $this;
    }
    /**
     * Internal procedure adding command to response.
     *
     * @param      $trigger
     * @param      $aAttributes
     * @param      $mData
     * @param null $special
     *
     * @return void
     */
    protected function addCommand($trigger, $aAttributes, $mData, $special = null) {
      if ($this->isActive() && ($trigger !== false)) {
        //		Event::error('adding '.$trigger.':'.htmlentities($mData));
        $aAttributes['why']  = $trigger;
        $aAttributes['data'] = $mData;
        if ($special) {
          $this->aCommands[$special] = $aAttributes;
        } else {
          $this->aCommands[] = $aAttributes;
        }
      }
    }
    /**
     * @param string $id
     * @param null   $trigger
     * @param bool   $non_ajax
     */
    public function start_div($id = null, $trigger = null, $non_ajax = false) {
      if ($non_ajax) { // div for non-ajax elements
        array_push($this->ajax_divs, [$id, null]);
        echo "<div class='js hidden' " . ($id ? "id='$id'" : '') . ">";
        return;
      }
      array_push($this->ajax_divs, [$id, $trigger === null ? $id : $trigger]);
      echo "<div " . ($id ? "id='$id'" : '') . ">";
      if ($this->_inAjax()) {
        ob_start();
      }
    }
    /**
     * @param bool $return_div
     *
     * @return string
     */
    public function end_div($return_div = false) {
      if ($div = array_pop($this->ajax_divs)) {
        if ($div[1] !== null) {
          if ($this->_inAjax()) {
            $this->addUpdate($div[1], $div[0], ob_get_flush());
          }
        }
        if ($return_div) {
          return "</div>";
        }
        echo "</div>";
      }
      return '';
    }
    /**
     * @return mixed
     */
    public function run() {
      if (!$this->isActive()) {
        return;
      }
      if ($this->debug) {
        $js = "console.log(" . json_encode($this->debug) . ");";
        $this->addScript(true, $js);
      }
      // remove not active commands
      foreach ($this->aCommands as $idx => $com) {
        // If we should reload whole page content ignore all commands but the update.
        // This is page repost equivalent, although header and footer are not reloaded.
        if ($com['why'] !== true && !isset($this->triggers[$com['why']])) {
          unset($this->aCommands[$idx]);
          //			Event::error('unset '.$com['t']);
        } else {
          if ($com['n'] == 'up' && $com['t'] == '_page_body') {
            $cmds = [$com];
            if ($this->aCommands['fc']) {
              $cmds[] = $this->aCommands['fc'];
            }
            if ($this->aCommands['js']) {
              $cmds[] = $this->aCommands['js'];
            }
            $this->aCommands = $cmds;
            break;
          }
        }
      }
      //		Event::error('Activate:'.htmlentities(print_r($this->triggers, true)));
      //		Event::error('Commands :'.htmlentities(print_r($this->aCommands, true)));
      $GLOBALS['_RESULT'] = $this->aCommands;
    }
    /**
     * @return bool
     */
    public function inAjax() {
      return $this->isActive();
    }
    /**
     * @static
     * @return bool
     */
    public function flush() {
      $this->aCommands = [];
    }
    /**
     * Returns absolute path of relative $url. To be used in ajax calls
     * for proper redirection from any referer page.
     *
     * @param $url
     *
     * @return string
     */
    public function absoluteURL($url) {
      return strpos($url, '..') === 0 ? dirname($_SERVER['DOCUMENT_URI']) . '/' . $url : str_replace(ROOT_WEB, '/', $url);
    }
  }
