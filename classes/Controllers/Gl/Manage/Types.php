<?php
  namespace ADV\Controllers\Gl\Manage;

  use ADV\App\Form\Form;
  use ADV\App\GL\Type;
  use Item_Price;
  use ADV\Core\View;

  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Types extends \ADV\App\Controller\FormPager
  {
    protected $security = SA_GLACCOUNTGROUP;
    protected function before() {
      $this->setTitle('GL Account Types');
      $this->object = new Type();
    }
    /**
     * @param \ADV\App\Pager\Edit $pager
     */
    protected function getEditing(\ADV\App\Pager\Edit $pager) {
      $pager->setObject($this->object);
    }

    /**
     * @param \ADV\App\Form\Form $form
     * @param \ADV\Core\View     $view
     *
     * @return mixed
     */
    protected function formContents(Form $form, View $view) {
      // TODO: Implement formContents() method.
    }
  }

