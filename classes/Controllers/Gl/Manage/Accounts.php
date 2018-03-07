<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Controllers\GL\Manage;

  use ADV\App\Form\Form;
  use ADV\App\Pager\Edit;
  use GL_Type;
  use ADV\App\GL\Account;
  use ADV\Core\View;

  /**
   * @property Account $object
   */
  class Accounts extends \ADV\App\Controller\FormPager
  {
    protected $tableWidth = '80';
    protected $security = SA_GLACCOUNT;
    protected function before() {
      $this->setTitle("GL Accounts");
      $this->object = new Account();
      $this->runPost();
    }
    /**
     * @param $form
     * @param $view
     *
     * @return mixed|void
     */
    protected function formContents(Form $form, View $view) {
      $view['title'] = 'GL Account';
      $form->text('account_name')->label("Name:")->focus($this->action == EDIT);
      $form->text('account_code')->label("Code:");
      $form->text('account_code2')->label("Code2:");
      $form->checkbox('inactive')->label('Inactive:');
      $form->custom(GL_Type::select('account_type'))->label('Type:');
    }

  }


