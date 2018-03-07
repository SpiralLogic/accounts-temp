<?php
  namespace ADV\Controllers\Items\Manage;

  use ADV\App\Inv\Location;
  use ADV\App\Form\Form;
  use ADV\Core\View;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Locations extends \ADV\App\Controller\FormPager
  {
    protected $tableWidth = '90';
    protected $security = SA_INVENTORYLOCATION;
    protected function before() {
      $this->object = new Location();
      $this->runPost();
      $this->setTitle("Inventory Locations");
    }
    protected function index() {
      $this->generateTable();
      echo '<br>';
      $this->generateForm();
    }
    /**
     * @param \ADV\App\Form\Form $form
     * @param \ADV\Core\View     $view
     *
     * @return mixed|void
     */
    protected function formContents(Form $form, View $view) {
      $view['title'] = 'Inventory Location';
      $form->hidden('id');
      $form->text('loc_code')->label('Location Code:');
      $form->text('location_name')->label('Location Name:');
      $form->arraySelect('type', [Location::BOTH => 'Both', Location::INWARD => 'Inward', Location::OUTWARD => 'Outward'])->label('Type:');
      $form->textarea('delivery_address')->label('Location Address:');
      $form->text('phone')->label('Phone:');
      $form->text('phone2')->label('Phone2:');
      $form->text('fax')->label('Fax:');
      $form->text('email')->label('Email:');
      $form->text('contact')->label('Contact Name:');
    }
    /**
     * @param $pager_name
     *
     * @return mixed
     */
    protected function getTableRows($pager_name) {
      $inactive = $this->getShowInactive($pager_name);
      return $this->object->getAll($inactive);
    }
  }


