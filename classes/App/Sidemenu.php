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

  use ADV\Core\View;

  /** **/
  class Sidemenu
  {
    protected $menu;
    /**
     * @return string
     */
    public function render() {
      return $this->menu->render(true);
    }
    /**
     * @return array
     */
    public function get() {
      return $this->menu->getALL();
    }
    /**
     * @param User $user
     */
    public function __construct(User $user) {
      $this->menu         = new View('sidemenu');
      $this->menu['bank'] = $user->hasAccess(SS_GL);
    }
  }
