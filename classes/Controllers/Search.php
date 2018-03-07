<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      20/09/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Controllers;

  use ADV\App\Controller\Action;
  use ADV\App\Item\Item;

  /** **/
  class Search extends Action
  {
    protected $security = SA_OPEN;
    protected function before() {
      if (!REQUEST_AJAX) {
        header('Location: /');
      }
    }
    /**

     */
    protected function index() {
      $type        = $this->Input->request('type');
      $searchdata  = $this->Input->request('data');
      $searchclass = '\\' . $type;
      $term        = $this->Input->get('term');
      if ($term) {
        $uniqueID = $this->Input->get('UniqueID');
        if ($uniqueID) {
          $data = Item::searchOrder($term, $uniqueID);
        } else {
          $data = $searchclass::search($term, $searchdata);
        }
        $this->JS->renderJSON($data);
      }
    }
  }

