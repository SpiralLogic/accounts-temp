<?php
  namespace ADV\Controllers\Sales\Manage;

  use ADV\App\Form\Form;
  use ADV\App\Sales\Area;
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
  class Areas extends \ADV\App\Controller\FormPager
  {
    protected $security = SA_SALESAREA;
    protected function before() {
      $this->object = new Area();
      $this->runPost();
      $this->setTitle("Sales Areas");
    }
    /**
     * @param $form
     * @param $view
     *
     * @return mixed|void
     */
    protected function formContents(Form $form, View $view) {
      $view['title'] = 'Sales Area';
      $form->hidden('area_code');
      $form->text('description')->label('Area Name:');
    }

  }

