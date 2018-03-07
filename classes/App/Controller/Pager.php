<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      5/09/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\Controller;

  use ADV\App\Form\Button;
  use ADV\Core\Status;
  use ADV\App\Pager\Edit;
  use ADV\Core\Input\Input;

  /** **/
  abstract class Pager extends Action
  {
    /** @var \ADV\App\DB\Base $object */
    protected $object;
    protected $defaultFocus;
    protected $tableWidth = '50';
    protected $security;
    protected $form_id = null;
    /**
     * @param                       $changes
     * @param \ADV\App\DB\Base|null $object
     * @param int                   $id
     *
     * @return \ADV\Core\Status|array
     */
    protected function onSave($changes, \ADV\App\DB\Base $object = null, $id = 0) {
      $object = $object ? : $this->object;
      $object->save($changes);
      //run the sql from either of the above possibilites
      $status = $object->getStatus();
      if ($status['status'] == Status::ERROR) {
        $this->JS->renderStatus($status);
      }
      $object->load($id);
      return $status;
    }
    /**
     * @param      $id
     * @param null $object
     */
    protected function onEdit($id, $object = null) {
      $object = $object ? : $this->object;
      $object->load($id);
    }
    /**
     * @param      $id
     * @param null $object
     *
     * @return array|string
     */
    protected function onDelete($id, $object = null) {
      $object = $object ? : $this->object;
      $object->load($id);
      $object->delete();
      $status = $object->getStatus();
      return $status;
    }
    abstract protected function beforeTable();
    /**
     * @return \ADV\App\Pager\Pager
     */
    abstract protected function generateTable();
    /**
     * @param $pager_name
     *
     * @return mixed
     */
    protected function getTableRows($pager_name) {
      $inactive = $this->getShowInactive($pager_name);
      return $this->object->getAll($inactive);
    }
    /**
     * @param $pager_name
     *
     * @return bool
     */
    protected function getShowInactive($pager_name) {
      $inactive = false;
      if (isset($_SESSION['pager'][$pager_name])) {
        $inactive = ($this->action == 'showInactive' && $this->Input->post(
          FORM_VALUE, Input::NUMERIC
        ) == 1) || ($this->action != 'showInactive' && $_SESSION['pager'][$pager_name]->showInactive);
        return $inactive;
      }
      return $inactive;
    }
    /**
     * @param $row
     *
     * @return Button
     */
    public function formatEditBtn($row) {
      return $this->formatBtn(EDIT, $row[$this->object->getIDColumn()], ICON_EDIT);
    }
    /**
     * @param $row
     *
     * @return Button
     */
    public function formatDeleteBtn($row) {
      return $this->formatBtn(DELETE, $row[$this->object->getIDColumn()], ICON_DELETE, Button::DANGER);
    }
    /**
     * @param        $action
     * @param string $id
     * @param null   $icon
     * @param string $type
     *
     * @return Button
     */
    public function formatBtn($action, $id = '', $icon = null, $type = Button::PRIMARY) {
      $button = new Button(FORM_ACTION, $action . $id, $action);
      $button->preIcon($icon);
      $button->type(Button::MINI)->type($type);
      return $button;
    }
    /**
     * @return mixed
     * @throws \UnexpectedValueException
     */
    protected function getPagerColumns() {
      if ($this->object instanceof \ADV\App\Pager\Pageable) {
        return $this->object->getPagerColumns();
      }
      throw new \UnexpectedValueException(get_class($this->object) . " is not pageable!");
    }
  }
