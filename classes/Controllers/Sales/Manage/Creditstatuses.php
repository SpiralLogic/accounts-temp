<?php
  namespace ADV\Controllers\Sales\Manage;

  use ADV\App\Form\Form;
  use ADV\App\Sales\CreditStatus;
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
  class Creditstatuses extends \ADV\App\Controller\FormPager
  {
    protected $tableWidth = '80';
    protected $security = SA_CRSTATUS;
    protected function before() {
      $this->object = new CreditStatus();
      $this->runPost();
      $this->setTitle("Credit Statuses");
    }
    /**
     * @param $form
     * @param $view
     *
     * @return mixed|void
     */
    protected function formContents(Form $form, View $view) {
      $view['title'] = 'Sales Credit Status';
      $form->hidden('id');
      $form->text('reason_description')->label('Description:');
      $form->arraySelect('dissallow_invoices', ['No', 'Yes'])->label('Disallow Invoices:');
    }

  }

