<?php
  namespace ADV\Controllers\Sales\Manage;

  use ADV\App\Sales\Group;
  use ADV\App\Form\Form;
  use ADV\Core\View;

  /** **/
  class Groups extends \ADV\App\Controller\FormPager
  {
    protected $security = SA_SALESGROUP;
    protected function before() {
      $this->object = new Group();
      $this->runPost();
      $this->setTitle("Sales Groups");
    }
    /**
     * @param \ADV\App\Form\Form   $form
     * @param \ADV\Core\View       $view
     *
     * @return mixed
     */
    protected function formContents(Form $form, View $view) {
      $view['title'] = 'Sales Group';
      $form->hidden('id');
      $form->text('description')->label('Group Name:');
    }
  }

