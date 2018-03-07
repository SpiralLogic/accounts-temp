<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 17/05/12
   * Time: 11:37 AM
   * To change this template use File | Settings | File Templates.
   */
  namespace ADV\App\Controller;

  use ADV\App\Page;
  use ADV\App\User;
  use ADV\Core\Session;

  /** **/
  abstract class Base
  {
    protected $title;
    /*** @var User */
    protected $User;
    /*** @var \ADV\Core\Session */
    protected $Session;
    public $help_context;
    /** @var Page */
    public $Page;
    protected $security = SA_DENIED;
    protected $embedded = false;
    /**

     */
    public function __construct($session, $user) {
      $this->Session = $session;
      $this->User    = $user;
      $this->checkSecurity();
    }
    /**
     * @param \ADV\App\Page $page
     */
    public function setPage(Page $page) {
      $this->Page = $page;
    }
    abstract protected function index();
    /**
     * @param      $title
     */
    protected function setTitle($title = null) {
      $title = $title ? : $this->title;
      $this->title = _($this->help_context = $title);
    }
    final protected function checkSecurity() {
      if (!$this->User->hasAccess($this->security)) {
        Page::start('No access');
        echo "<div class='center'><br><br><br><span class='bold'>";
        echo _("The security settings on your account do not permit you to access this function");
        echo "</span>";
        echo "<br><br><br><br></div>";
        Page::end();
        exit;
      }
    }
    /**
     * @param $embed
     *
     * @return mixed
     */
    abstract public function run($embed = false);
  }
