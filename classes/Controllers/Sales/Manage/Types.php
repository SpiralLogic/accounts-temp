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
  namespace ADV\Controllers\Sales\Manage;

  use ADV\App\Form\Form;
  use ADV\App\Sales\Type;
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
    protected $security = SA_SALESTYPES;
    protected function before() {
      $this->object = new Type();
      $this->runPost();
      $this->setTitle("Sales Types");
    }
    /**
     * @param $form
     * @param $view
     *
     * @return mixed|void
     */
    protected function formContents(Form $form, View $view) {
      $view['title'] = $this->title;
      $form->hidden('id');
      $form->text('sales_type')->label('Sales Type:')->focus();
      $form->percent('factor')->label('Factor:');
      $form->checkbox('tax_included')->label('Tax Included:');
      $form->checkbox('inactive')->label('Inactive:');
    }

  }

