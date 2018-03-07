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

  use ADV\App\Users;
  use ADV\App\Sales\Person;
  use ADV\App\Form\Form;
  use ADV\Core\View;

  /** **/
  class People extends \ADV\App\Controller\FormPager
  {
    protected $tableWidth = '80';
    protected $security = SA_SALESMAN;
    protected function before() {
      $this->object = new Person(0);
      $this->runPost();
      $this->setTitle("Sales Persons");
    }
    /**
     * @param \ADV\App\Form\Form $form
     * @param View               $view
     *
     * @return mixed|void
     */
    protected function formContents(Form $form, View $view) {
      $view['title'] = 'Sales Person Details';
      $form->hidden('salesman_code');
      $form->text('salesman_name', ['maxlength' => 30])->label('Name: ')->focus($this->action == EDIT);
      $form->custom(Users::select('user_id', null, " ", true))->label('User:');
      $form->text('salesman_phone', ['maxlength' => 20])->label('Telephone number: ');
      $form->text('salesman_fax', ['maxlength' => 20])->label('Fax number: ');
      $form->text('salesman_email')->label('Email Address: ');
      $form->percent('provision')->label("Provision: ");
      $form->amount('break_pt')->label("Break Pt.:");
      $form->percent('provision2')->label("Provision 2: ");
    }
  }

