<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 17/05/12
   * Time: 11:37 AM
   * To change this template use File | Settings | File Templates.
   */
  namespace ADV\App\Controller;

  use ADV\Core\Ajax;
  use ADV\Core\DB\DB;
  use ADV\App\User;
  use ADV\Core\Session;
  use ADV\Core\JS;
  use ADV\Core\Input\Input;

  /** **/
  abstract class Action extends Base
  {
    use \ADV\Core\Traits\Action;

    protected $title;
    /*** @var \ADV\Core\Ajax */
    protected $Ajax;
    /** @var \ADV\Core\DB\DB */
    static $DB;
    /*** @var JS */
    protected $JS;
    /** @var Input */
    protected $Input;
    public $help_context;
    /**

     */
    public function __construct(Session $session, User $user, Ajax $ajax, JS $js, Input $input, DB $db = null) {
      parent::__construct($session, $user);
      $this->Ajax  = $ajax;
      $this->JS    = $js;
      $this->Input = $input;
      static::$DB  = $db;
    }
    /**
     * @param  $controller
     *
     * @return bool
     */
    protected function embed($controller) {
      $controller = '\\ADV\\Controllers\\' . $controller;
      if (class_exists($controller)) {
        return (new $controller($this->Session, $this->User, $this->Ajax, $this->JS, $this->Input, static::$DB))->run(true);
      }
      return false;
    }
    /**
     * @param bool $embed
     *
     * @return mixed|void
     */
    public function   run($embed = false) {
      $this->action   = $this->Input->post(FORM_ACTION);
      $this->embedded = $embed || $this->Input->request('frame');
      $this->setTitle();
      $this->before();
      if ($this->Page && !$this->Ajax->inAjax()) {
        $this->Page->init($this->title, $this->security, $this->embedded);
        $this->index();
        echo '<br>';
        $this->Page->end_page(true);
      } elseif (!$embed && $this->Ajax->inAjax()) {
        $this->Ajax->start_div('_page_body');
        $this->index();
        $this->Ajax->end_div();
      } else {
        ob_start();
        $this->index();
      }
      $this->after();
      return !$embed ? : ob_get_clean();
    }
    protected function before() {
    }
    protected function after() {
    }
    /**
     * @internal param $prefix
     * @return bool|mixed
     */
    protected function runValidation() {
    }
    protected function runAction() {
      if ($this->action && is_callable([$this, $this->action])) {
        call_user_func([$this, $this->action]);
      }
    }
  }
