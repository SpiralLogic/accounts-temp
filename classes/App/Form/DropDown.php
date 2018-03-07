<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      12/10/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\Form;

  use ADV\Core\View;

  /** **/
  class DropDown
  {
    protected $items = [];
    public $split = false;
    protected $auto = '';
    protected $title = '';
    /**
     * @param        $label
     * @param string $href
     * @param array  $data
     * @param array  $attr
     *
     * @return DropDown
     */
    public function addItem($label, $href = '#', $data = [], $attr = []) {
      foreach ($data as $k => $v) {
        $attr['data-' . $k] = $v;
      }
      $attr['href']  = $href;
      $this->items[] = ['label' => $label, 'attr' => $attr];
      return $this;
    }
    /**
     * @param bool $on
     *
     * @return \ADV\App\Form\DropDown
     */
    public function setAuto($on = true) {
      $this->auto = $on ? 'auto' : '';
      return $this;
    }
    /**
     * @param bool $on
     *
     * @return \ADV\App\Form\DropDown
     */
    public function setSplit($on = true) {
      $this->split = (bool)$on;
      return $this;
    }
    /**
     * @param $title
     *
     * @return \ADV\App\Form\DropDown
     */
    public function setTitle($title) {
      $this->title = (string)$title;
      return $this;
    }
    /**
     * @param bool $return
     *
     * @return string
     */
    public function render($return = false) {
      $view          = new View('ui/dropdown');
      $view['auto']  = $this->auto;
      $view['title'] = $this->title ? : $this->items[0]['label'];
      $view->set('split', $this->split);
      $view->set('items', $this->items);
      $output = $view->render($return);
      unset ($view);
      return $output;
    }
    /**
     * @return DropDown
     */
    public function addDivider() {
      return $this;
    }
  }
