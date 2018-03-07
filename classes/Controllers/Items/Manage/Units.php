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
  namespace ADV\Controllers\Items\Manage;

  use ADV\App\Validation;
  use ADV\App\Item\Unit;
  use ADV\Core\View;
  use ADV\App\Form\Form;

  /** **/
  class Units extends \ADV\App\Controller\FormPager
  {
    protected $stock_id;
    protected $security = SA_UOM;
    protected function before() {
      $this->object = new Unit();
      $this->runPost();
      $this->setTitle("Item Units");
    }
    /**
     * @param \ADV\App\Form\Form $form
     * @param \ADV\Core\View     $view
     *
     * @return mixed
     */
    protected function formContents(Form $form, View $view) {
      $view['title'] = 'Item Units of Measure';
      $form->hidden('id');
      $form->text('abbr')->label('Abbreviation:')->focus();
      $form->text('name')->label('Description:');
      $form->number('decimals', 0)->label('Decimals:');
      $form->checkbox('use_pref')->label('Use user preference:');
    }
    protected function runValidation() {
      Validation::check(Validation::PURCHASE_ITEMS, _("There are no purchasable inventory items defined in the system."), STOCK_PURCHASED);
      Validation::check(Validation::SUPPLIERS, _("There are no suppliers defined in the system."));
    }
  }

