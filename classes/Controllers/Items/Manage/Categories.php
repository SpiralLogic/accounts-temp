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

  use ADV\App\Form\Form;
  use GL_UI;
  use Tax_ItemType;
  use Item_Unit;
  use ADV\App\Item\Category;
  use ADV\Core\View;

  /**
   * @property Category $object
   */
  class Categories extends \ADV\App\Controller\FormPager
  {
    protected $tableWidth = '80';
    protected $security = SA_ITEMCATEGORY;
    protected function before() {
      $this->setTitle('Item Categories');
      $this->object = new Category();
      $this->runPost();
    }
    /**
     * @param $form
     * @param $view
     *
     * @return mixed|void
     */
    protected function formContents(Form $form, View $view) {
      $view['title'] = 'Item Category';
      $form->hidden('category_id');
      $form->text('description')->label("Category Name:")->focus($this->action == EDIT);
      $form->checkbox('dflt_no_sale')->label("Exclude from sales:");
      $form->arraySelect('dflt_mb_flag', [STOCK_SERVICE => 'Service', STOCK_MANUFACTURE => 'Manufacture', STOCK_PURCHASED => 'Purchased', STOCK_INFO => 'Info'])->label('Type:');
      $form->custom(Item_Unit::select('dflt_units', null))->label(_("Units of Measure:"));
      $form->custom(Tax_ItemType::select('dflt_tax_type', '', null))->label(_("Tax Type:"));
      $form->custom(GL_UI::all('dflt_sales_act'))->label(_("Sales Account:"));
      $form->custom(GL_UI::all('dflt_inventory_act'))->label(_("Inventory Account:"));
      $form->custom(GL_UI::all('dflt_cogs_act'))->label(_("C.O.G.S. Account:"));
      $form->custom(GL_UI::all('dflt_adjustment_act'))->label(_("Inventory Adjustment Account:"));
      $form->custom(GL_UI::all('dflt_assembly_act'))->label(_("Assembly Cost Account:"));
      if ($this->object->dflt_mb_flag == STOCK_SERVICE || $this->object->dflt_mb_flag == STOCK_INFO) {
        $form->hide('dflt_cogs_act');
        $form->hide('dflt_inventory_act');
        $form->hide('dflt_adjustment_act');
        $form->hide('dflt_assembly_act');
      }
      if ($this->object->dflt_mb_flag == STOCK_PURCHASED) {
        $form->hide('dflt_assembly_act');
      }
      if ($this->object->dflt_mb_flag == STOCK_INFO) {
        $form->hide('dflt_sales_act');
      }
    }

  }


