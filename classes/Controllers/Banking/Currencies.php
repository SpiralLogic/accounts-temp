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
  namespace ADV\Controllers\Banking\Manage;

  use ADV\App\Bank\Currency;
  use ADV\Core\View;
  use ADV\App\Form\Form;

  /**   */
  class Currencies extends \ADV\App\Controller\FormPager
  {
    protected $security = SA_CURRENCY;
    protected $title = 'Currencies';
    public function before() {
      $this->object = new Currency();
      $this->tableWidth = '80';
      $this->runPost();
    }
    /**
     * @param \ADV\App\Form\Form $form
     * @param \ADV\Core\View     $view
     *
     * @return mixed
     */
    protected function formContents(Form $form, View $view) {
      $view['title'] = 'Currency';
      $form->text('currency')->label('Currency');
      $form->text('curr_abrev')->label('Abbreviation');
      $form->text('curr_symbol')->label('Symbol');
      $form->text('country')->label('Country');
      $form->text('hundreds_name')->label('Hundreds name');
      $form->arraySelect('inactive', ['No', 'Yes'])->label('Inactive:');
      $form->text('auto_update ')->label('Auto Update');
      ;
    }

  }

