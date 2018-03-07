<?php
  namespace ADV\Controllers\Items\Manage;

  use ADV\App\Item\Item;
  use ADV\App\Item\Reorder;
  use ADV\App\Item\Purchase;
  use Item_Price;
  use ADV\App\Item\Price;
  use ADV\App\Form\Form;
  use GL_UI;
  use Item_UI;
  use Tax_ItemType;
  use Item_Unit;
  use Item_Category;
  use ADV\Core\MenuUI;
  use ADV\Core\View;
  use ADV\App\UI;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Items extends \ADV\App\Controller\Action
  {
    protected $itemData;
    protected $stock_id = 0;
    protected $stockid = 0;
    /** @var \ADV\App\Item\Item */
    protected $item;
    protected $formid;
    protected $security = SA_CUSTOMER;
    protected function before() {
      $this->formid = $this->Input->getPostGlobal(FORM_ID);
      if (REQUEST_GET) {
        $this->stock_id = & $this->Input->getPostGlobal('stock_id');
        if ($this->stock_id) {
          $this->stockid = Item::getStockID($this->stock_id);
        }
      } elseif ($this->Input->hasPost('id')) {
        $this->stockid = & $this->Input->postGlobal('stockid');
        $this->stockid = $this->Input->post('id');
      }
      $this->item        = new Item($this->stockid);
      $_POST['stock_id'] = $this->stock_id = $this->item->stock_id;
      $this->Session->setGlobal('stock_id', $this->stock_id);
      $this->runPost();
      $this->JS->footerFile("/js/quickitems.js");
      $this->setTitle("Items");
    }
    /**
     * @`` param $id
     * @return string
     */
    protected function getItemData() {
      $data['item']          = $this->item;
      $data['stockLevels']   = $this->item->getStockLevels();
      $data['sellprices']    = $this->embed('Items\\Manage\\Prices');
      $data['buyprices']     = $this->embed('Items\\Manage\\Purchasing');
      $data['reorderlevels'] = $this->embed('Items\\Manage\\Reorders');
      return $data;
    }
    protected function runPost() {
      $data = [];
      if (REQUEST_POST && $this->formid == 'item_form') {
        switch ($this->action) {
          case SAVE:
            $this->item->save($_POST);
            $data['status'] = $this->item->getStatus();
            break;
          case 'clone':
            $this->stock_id = $this->item->stock_id = '';
            $this->stockid  = $this->item->id = 0;
        }
        if (isset($_GET['page'])) {
          $data['page'] = $_GET['page'];
        }
      }
      if (REQUEST_POST) {
        $this->JS->renderJSON($this->getItemData());
      }
    }
    protected function index() {
      $view = new View('items/quickitems');
      $menu = new MenuUI('disabled', 'itemedit');
      $view->set('menu', $menu);
      $form = new Form();
      $form->start('item', '/Items/Manage/Items');
      $form->group('items');
      $form->hidden('stockid');
      $form->hidden('id');
      $form->text('stock_id')->label('Item Code:');
      $form->text('description')->label('Item Name:');
      $form->textarea('long_description', ['rows' => 4])->label('Description:');
      $form->custom(Item_Category::select('category_id'))->label('Category:');
      $form->custom(Item_Unit::select('uom'))->label('Units:');
      $form->custom(Tax_ItemType::select('tax_type_id'))->label('Tax Type:');
      $form->group('accounts');
      $form->custom(Item_UI::type('mb_flag'))->label('Type:');
      $form->custom(GL_UI::all('sales_account'))->label('Sales Account:');
      $form->custom(GL_UI::all('inventory_account'))->label('Inventory Account:');
      $form->custom(GL_UI::all('cogs_account'))->label('Cost of Goods Sold Account:');
      $form->custom(GL_UI::all('adjustment_account'))->label('Adjustment Account:');
      $form->custom(GL_UI::all('assembly_account'))->label('Assembly Account:');
      $form->group('buttons');
      $form->button(FORM_ACTION, ADD, ADD)->type(\ADV\App\Form\Button::PRIMARY)->id('btnNew')->mergeAttr(['form' => 'item_form']);
      $form->button(FORM_ACTION, 'clone', 'Clone')->type(\ADV\App\Form\Button::PRIMARY)->id('btnClone')->mergeAttr(['form' => 'item_form']);
      $form->button(FORM_ACTION, CANCEL, CANCEL)->type(\ADV\App\Form\Button::DANGER)->preIcon(ICON_CANCEL)->id('btnCancel')->hide()->mergeAttr(['form' => 'item_form']);
      $form->button(FORM_ACTION, SAVE, SAVE)->type(\ADV\App\Form\Button::SUCCESS)->preIcon(ICON_SAVE)->id('btnConfirm')->hide()->mergeAttr(['form' => 'item_form']);
      $view->set('form', $form);
      $data = $this->getItemData();
      $this->JS->onload("Items.onload(" . json_encode($data) . ");");
      $this->JS->autocomplete('itemSearchId', 'Items.fetch', 'Item');
      if (!$this->Input->hasGet('stock_id')) {
        $searchBox = UI::search(
          'itemSearchId', [
                          'url'      => 'Item',
                          'idField'  => 'stock_id',
                          'name'     => 'itemSearchId', //
                          'focus'    => true,
                          'value'    => $this->stock_id,
                          'callback' => 'Items.fetch'
                          ], true
        );
        $view->set('searchBox', $searchBox);
        $this->JS->setFocus('itemSearchId');
      }
      $view->set('sellprices', $data['sellprices']);
      $view->set('buyprices', $data['buyprices']);
      $view->set('reorderlevels', $data['reorderlevels']);
      $view->set('firstPage', $this->Input->get('page'));
      $view->render();
    }
  }


