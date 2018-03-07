<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Controllers\Access;

  use ADV\Core\View;

  /**
   *
   */
  class Logout extends \ADV\App\Controller\Base
  {
    protected $security = SA_OPEN;
    protected function index() {
      $this->Page->start('Logout', SA_OPEN, true);
      (new View('logout'))->render();
      $this->Page->end(true);
    }
    /**
     * @param bool $embed
     */
    /**
     * @param bool $embed
     *
     * @return mixed|void
     */
    public function run($embed = false) {
      $this->index();
      $this->after();
    }
    protected function after() {
      $this->User->logout();
    }
  }
