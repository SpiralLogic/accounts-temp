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
  use ADV\App\Pager\Pager;
  use ADV\App\Form\Form;
  use ADV\Core\View;
  use ADV\Core\Input\Input;

  /** **/
  abstract class FormPager extends \ADV\App\Controller\Pager
  {
    /** @var \ADV\App\DB\Base */
    protected $object;
    protected $defaultFocus;
    protected $tableWidth = '50';
    protected $security;
    /**
     * @param \ADV\App\Form\Form $form
     * @param \ADV\Core\View     $view
     *
     * @return mixed
     */
    abstract protected function formContents(Form $form, View $view);
    protected function runPost() {
      if (REQUEST_POST) {
        $id = $this->getActionId([DELETE, EDIT, INACTIVE]);
        switch ($this->action) {
          case DELETE:
            $this->object->load($id);
            $this->object->delete();
            $status = $this->object->getStatus();
            break;
          case EDIT:
            $this->object->load($id);
            break;
          case INACTIVE:
            $this->object->load($id);
            $changes['inactive'] = $this->Input->post(FORM_VALUE, Input::NUMERIC);
          case SAVE:
            $changes = isset($changes) ? $changes : $_POST;
            $this->object->save($changes);
            //run the sql from either of the above possibilites
            $status = $this->object->getStatus();
         if ($status['status'] == Status::ERROR) {
              $this->JS->renderStatus($status);
            }
            $this->object->load(0);
            break;
          case CANCEL:
            $status = $this->object->getStatus();
            break;
          case 'showInactive':
            $this->generateTable();
            exit();
        }
        if (isset($status)) {
          $this->Ajax->addStatus($status);
        }
      }
    }
    protected function index() {
      $this->beforeTable();
      $this->generateTable();
      echo '<br>';
      $this->generateForm();
    }
    protected function beforeTable() {
    }
    /**
     * @param \ADV\App\Form\Form $form
     * @param \ADV\Core\View     $view
     * @param null               $object
     * @param bool               $contents
     */
    protected function generateForm(Form $form = null, View $view = null, $object = null, $contents = true) {
      $view = $view ? : new View('form/simple');
      $form = $form ? : new Form();
      if ($contents) {
        $view['title'] = $this->title;
        $this->formContents($form, $view);
      }
      $form->name($this->object->getClassname());
      $form->group('buttons');
      $form->submit(CANCEL)->type(Button::DANGER)->preIcon(ICON_CANCEL);
      $form->submit(SAVE)->type(Button::SUCCESS)->preIcon(ICON_ADD);
      $form->setValues($object ? : $this->object);
      $view->set('form', $form);
      $view->render();
      $this->Ajax->addJson(true, 'setFormValues', $form);
    }
    /**
     * @return \ADV\App\Pager\Pager
     */
    protected function generateTable() {
      $cols       = $this->getPagerColumns();
      $pager_name = end(explode('\\', ltrim(get_called_class(), '\\'))) . '_table';
      // Pager::kill($pager_name);
      $table = Pager::newPager($pager_name, $cols);
      $table->setData($this->getTableRows($pager_name));
      $table->width = $this->tableWidth;
      $table->display();
    }
    /**
     * @return array
     */
    public function getPagerColumns() {
      $columns   = parent::getPagerColumns();
      $columns[] = ['type' => 'insert', "align" => "center", 'fun' => [$this, 'formatEditBtn']];
      $columns[] = ['type' => 'insert', "align" => "center", 'fun' => [$this, 'formatDeleteBtn']];
      return $columns;
    }
  }
