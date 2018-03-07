<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Controllers\GL\Manage;

  use GL_UI;
  use Tax_Type;
  use ADV\App\Pager\Pager;
  use GL_QuickEntry;
  use ADV\Core\View;
  use ADV\App\Form\Form;

  /** **/
  class Quickentries extends \ADV\App\Controller\FormPager
  {
    protected $tableWidth = '70';
    protected $security = SA_QUICKENTRY;
    public $linesid;
    /** @var  \ADV\App\GL\QuickEntryLine */
    public $line;
    protected function before() {
      $this->object = new \ADV\App\GL\QuickEntry();
      $this->line   = new \ADV\App\GL\QuickEntryLine();
      $this->runPost();
      if (!$this->object->id) {
        $this->object->load($this->Input->post('qid'));
      }
      $this->line->qid = $this->object->id;
    }
    protected function index() {
      $this->setTitle("Quick Entries");
      $this->generateTable();
      echo '<br>';
      if (!REQUEST_POST || $this->Input->post(FORM_ID) != 'QE_Lines_form') {
        $this->generateForm();
      }
      $this->generateLineTable();
    }
    protected function generateLineTable() {
      $cols       = [
        ['type' => 'hidden'],
        ['type' => 'hidden'],
        'Action'           => ['fun' => [$this, 'formatActionLine'], 'edit' => [$this, 'formatActionLineEdit']],
        'Account/Tax Type' => ['edit' => 'hidden'],
        ['type' => 'hidden', 'edit' => [$this, 'formatAccountLineEdit']],
        'Amount'           => ['type' => 'amount', 'edit' => [$this, 'formatAmountLineEdit']],
        ['type' => 'hidden'],
        ['type' => 'hidden'],
      ];
      $pager_name = 'QE_Lines';
      $linestable = \ADV\App\Pager\Edit::newPager($pager_name, $cols);
      $linestable->setObject($this->line);
      $linestable->editing->qid = $this->object->id;
      if ($this->Input->post(FORM_ACTION) == CHANGED && $this->Input->post(FORM_CONTROL) == 'action') {
        $this->line->action = $this->Input->post('action');
      }
      $linestable->width = $this->tableWidth;
      $linestable->setData($this->object->getLines());
      $linestable->display();
    }
    /**≈
     * @param \ADV\App\Form\Form $form
     * @param \ADV\Core\View     $view
     *
     * @return mixed
     */
    protected function formContents(Form $form, View $view) {
      $view['title'] = 'Quick Entry';
      $form->hidden('id');
      $form->text('description')->label('Description')->focus($this->action == EDIT);
      $form->arraySelect('type', GL_QuickEntry::$types)->label('Type');
      $form->amount('base_amount')->label('Base Amount');
      $form->text('base_desc')->label('Base Description');
    }
    /**
     * @param $id
     */
    protected function onEdit($id) {
      $this->linesid = $id;
      parent::onEdit($id);
    }
    /**
     * @param $form
     */
    public function formatAmountLineEdit(Form $form) {
      $actn = $this->line->action;
      if ($actn != '=') {
        if ($actn == '%') {
          return $form->number('amount', $this->User->prefs->exrate_dec);
        } else {
          return $form->amount('amount');
        }
      } else {
        return $form->hidden('amount');
      }
    }
    /**
     * @param \ADV\App\Form\Form $form
     *
     * @internal param $row
     * @return mixed
     */
    public function formatActionLineEdit(Form $form) {
      $this->Ajax->addFocus(true, 'action');
      $field = $form->arraySelect('action', GL_QuickEntry::$actions);
      $field['class'] .= ' async';
      return $field;
    }
    /**
     * @param \ADV\App\Form\Form $form
     *
     * @internal param $row
     * @return mixed
     */
    public function formatAccountLineEdit(Form $form) {
      $actn = $this->line->action;
      if (strtolower($actn[0]) == 't') {
        //Tax_ItemType::row(_("Item Tax Type").":",'dest_id', null);
        return $form->custom(Tax_Type::select('dest_id'));
      } else {
        return $form->custom(GL_UI::all('dest_id', null, $_POST['type'] == QE_DEPOSIT || $_POST['type'] == QE_PAYMENT));
      }
    }
    /**
     * @param $row
     *
     * @return mixed
     */
    public function formatActionLine($row) {
      return GL_QuickEntry::$actions[$row['action']];
    }
  }
