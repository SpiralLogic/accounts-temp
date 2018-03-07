<?php
  /**
   * Created by PhpStorm.
   * User: advanced
   * Date: 4/12/13
   * Time: 7:04 PM
   */
  namespace ADV\Controllers\System\Manage;

  use ADV\App\Controller\Action;
  use ADV\App\Form\Button;
  use ADV\App\Form\Form;
  use ADV\Core\Auth;
  use ADV\Core\Status;
  use ADV\Core\View;

  /**
   * Class Password
   * @package ADV\Controllers\System\Manage
   */
  class Password extends Action
  {
    protected $title = "Change password";
    protected $security = SA_CHGPASSWD;
    /** @var  Status */
    protected $status;
    protected function before() {
      if (REQUEST_POST) {
        $this->status = new Status();
        $password     = $this->Input->post('password');
        $confirmpwd   = $this->Input->post('confirmpwd');
        if ($password && $confirmpwd) {
          $this->process($password, $confirmpwd);
        }
        $this->JS->renderStatus($this->status);
      }
    }
    protected function index() {
      $view = new View('form/simple');
      $form = new Form();
      $view->set('form', $form);
      $view->set('title', 'Change Password');
      $form->start('changepwd');
      $form->text('user_id')->readonly(true)->value($this->User->username)->label('User:');
      $form->password('password')->label('Password:');
      $form->password('confirmpwd')->label('Confirm password:');
      $form->submit('change', 'Change Password')->type(Button::SUCCESS)->preIcon(ICON_SUBMIT);
      $form->end();
      $view->render();
    }
    /**
     * @param $password
     * @param $confirmpwd
     *
     * @return bool
     */
    protected function process($password, $confirmpwd) {
      $auth = new Auth($this->User->username);
      if ($password != $confirmpwd) {
        return $this->status->set(false, 'Passwords do not match!', 'password');
      }
      $check = $auth->checkPasswordStrength($password, $this->User->username);
      if ($check['error'] > 0) {
        return $this->status->set(false, $check['text']);
      } elseif ($check['strength'] < 3) {
        return $this->status->set(false, 'Password is not strong enough!');
      }
      $auth->updatePassword($this->User->user, $password, 0);
      return $this->status->set(true, 'Password successfully changed');
    }
  }
