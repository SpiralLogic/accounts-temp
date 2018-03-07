<?php
  namespace ADV\Controllers\Sales\Manage;

  use ADV\App\Form\Form;
  use Inv_Location;
  use Bank_UI;
  use ADV\App\Sales\Point;
  use ADV\Core\View;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Points extends \ADV\App\Controller\FormPager
  {

    protected $security = SA_SALESAREA;
    protected function before() {
      $this->object = new Point();
      $this->runPost();
      $this->setTitle("Sales Points");
    }
    /**
     * @param $form
     * @param $view
     *
     * @return mixed|void
     */
    protected function formContents(Form $form, View $view) {
      $view['title'] = 'Sales POS';
      $form->hidden('id');
      $form->text('pos_name')->label('Name: ');
      $form->checkbox('cash_sale')->label('Cash Sales: ');
      $form->checkbox('credit_sale')->label('Credit Sales: ');
      $form->custom(Bank_UI::cash_accounts_row(null, 'pos_account', null, false, true))->label('Name: ');
      $form->custom(Inv_Location::select('pos_location'))->label('Location: ');
    }

  }

