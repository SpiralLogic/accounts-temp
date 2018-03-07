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
  namespace ADV\Controllers\Tax\Manage;

  use GL_UI;
  use ADV\App\Tax\Type;
  use ADV\Core\View;
  use ADV\App\Form\Form;

  /**   */
  class Types extends \ADV\App\Controller\FormPager
  {
    protected $security = SA_TAXRATES;
    public function before() {
      $this->object = new Type();
      $this->tableWidth = '80';
      $this->runPost();
      $this->setTitle('Tax Types');
    }
    /**
     * @param \ADV\App\Form\Form $form
     * @param \ADV\Core\View     $view
     *
     * @return mixed
     */
    protected function formContents(Form $form, View $view) {
      $view['title'] = 'Tax Type';
      $form->hidden('id');
      $form->text('name')->label('Description');
      $form->percent('rate')->label('Default Rate (%)');
      $form->custom(GL_UI::all('sales_gl_code'))->label('Sales GL Account');
      $form->custom(GL_UI::all('purchasing_gl_code'))->label('Purchasing GL Account');
      $form->checkbox('inactive')->label('Inactive:');
    }

  }
