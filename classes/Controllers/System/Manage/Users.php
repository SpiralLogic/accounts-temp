<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Controllers\System\Manage;


  use Reports_UI;
  use Sales_Point;
  use ADV\App\Languages;
  use ADV\App\User;
  use ADV\App\Security;
  use ADV\App\Form\Form;
  use ADV\Core\View;

  /** **/
  class Users extends \ADV\App\Controller\FormPager
  {
    protected $tableWidth = '80';
    protected $security = SA_USERS;

    protected function before() {
      $this->object = new User();
      $this->runPost();
      $this->setTitle("Users");
    }

    /**
     * @param \ADV\App\Form\Form $form
     * @param View               $view
     *
     * @return mixed|void
     */
    protected function formContents(Form $form, View $view) {
      $view['title'] = 'Users';
      $form->hidden('id');
      $form->text('user_id')->label('User ID:')->focus($this->action == EDIT);
      $form->password('password')->label('Password:');
      $form->text('real_name')->label('Name:');
      $form->text('phone')->label('Telephone number:');
      $form->text('email')->label('Email Address:');
      $form->custom(Languages::select('language'))->label('Language:');
      $form->custom(Sales_Point::select('pos'))->label('User\'s POS');
      $form->custom(Reports_UI::select('print_profile'))->label('Printing profile:');
      $form->custom(Security::roles('role_id'))->label("Access Level:");
      $form->checkbox('rep_popup')->label('Use popup window for reports:');
      $form->checkbox('change_password')->label('Must change password next logon:');
    }
  }

  /*
    use ADV\App\Users;
    use ADV\App\Forms;
    use ADV\App\User;

    /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au

    $this->Page->init(_($help_context = "Users"), SA_USERS);
    list($Mode, $selected_id) = list($Mode, $selected_id) = $this->Page->simple_mode(true);
    if (!empty($_POST['password']) && ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM)) {
      $auth = new Auth($selected_id);
      $auth->updatePassword($selected_id, $_POST['password'],$_POST['change_password']);
      if (can_process($auth)) {
        if ($Mode == UPDATE_ITEM) {
          Users::update(
            $selected_id,
            $_POST['user_id'],
            $_POST['real_name'],
            $_POST['phone'],
            $_POST['email'],
            $_POST['Access'],
            $_POST['language'],
            $_POST['profile'],
            Input::_hasPost('rep_popup'),
            $_POST['pos']
          );
          Event::success(_("The selected user has been updated."));
        } else {
          Users::add(
            $_POST['user_id'],
            $_POST['real_name'],
            $_POST['phone'],
            $_POST['email'],
            $_POST['Access'],
            $_POST['language'],
            $_POST['profile'],
            Input::_hasPost('rep_popup'),
            $_POST['pos']
          );
          Event::success(_("A new user has been added."));
        }
        $Mode = MODE_RESET;
      }
    }
    if ($Mode == MODE_DELETE) {
      Users::delete($selected_id);
      Event::notice(_("User has been deleted."));
      $Mode = MODE_RESET;
    }
    if ($Mode == MODE_RESET) {
      $selected_id = -1;
      $sav         = Input::_post('show_inactive');
      unset($_POST); // clean all input fields
      $_POST['show_inactive'] = $sav;
    }
    $result = Users::getAll(Input::_hasPost('show_inactive'));
    Forms::start();
    Table::start('padded grid');
    $th = array(
      _("User login"),
      _("Full Name"),
      _("Phone"),
      _("Email"),
      _("Last Visit"),
      _("Access Level"),
      "",
      ""
    );
    Forms::inactiveControlCol($th);
    Table::header($th);
    $k = 0; //row colour counter
    while ($myrow = DB::_fetch($result)) {
      $last_visit_date = Dates::_sqlToDate($myrow["last_visit_date"]);
      /*The security_headings array is defined in config.php
      $not_me = strcasecmp($myrow["user_id"], $this->User->username);
      Cell::label($myrow["user_id"]);
      Cell::label($myrow["real_name"]);
      Cell::label($myrow["phone"]);
      Cell::email($myrow["email"]);
      Cell::label($last_visit_date, ' class="nowrap"');
      Cell::label($myrow["role"]);
      if ($not_me) {
        Forms::inactiveControlCell($myrow["id"], $myrow["inactive"], 'users', 'id');
      } elseif (Input::_hasPost('show_inactive')) {
        Cell::label('');
      }
      Forms::buttonEditCell("Edit" . $myrow["id"], _("Edit"));
      if ($not_me) {
        Forms::buttonDeleteCell("Delete" . $myrow["id"], _("Delete"));
      } else {
        Cell::label('');
      }
      echo '</tr>';
    } //END WHILE LIST LOOP
    Forms::inactiveControlRow($th);
    Table::end(1);
    Table::start('standard');
    $_POST['email'] = "";
    if ($selected_id != -1) {
      if ($Mode == MODE_EDIT) {
        //editing an existing User
        $myrow              = Users::get($selected_id);
        $_POST['id']        = $myrow["id"];
        $_POST['user_id']   = $myrow["user_id"];
        $_POST['real_name'] = $myrow["real_name"];
        $_POST['phone']     = $myrow["phone"];
        $_POST['email']     = $myrow["email"];
        $_POST['Access']    = $myrow["role_id"];
        $_POST['language']  = $myrow["language"];
        $_POST['profile']   = $myrow["print_profile"];
        $_POST['rep_popup'] = $myrow["rep_popup"];
        $_POST['pos']       = $myrow["pos"];
      }
      Forms::hidden('selected_id', $selected_id);
      Forms::hidden('user_id');
      echo '<tr>';
      Table::label(_("User login:"), Input::_post('user_id'));
    } else { //end of if $selected_id only do the else when a new record is being entered
      Forms::textRow(_("User Login:"), "user_id", null, 22, 20);
      $_POST['language']  = User::_language();
      $_POST['profile']   = User::_print_profile();
      $_POST['rep_popup'] = User::_rep_popup();
      $_POST['pos']       = User::_pos();
    }
    $_POST['password'] = "";
    Forms::passwordRow(_("Password:"), 'password', $_POST['password']);
    if ($selected_id != -1) {
      Table::sectionTitle(_("Enter a new password to change, leave empty to keep current."));
    }
    Forms::textRowEx(_("Full Name") . ":", 'real_name', 50);
    Forms::textRowEx(_("Telephone No.:"), 'phone', 30);
    Forms::emailRowEx(_("Email Address:"), 'email', 50);
    Security::roles_row(_("Access Level:"), 'Access', null);
    Languages::row(_("Language:"), 'language', null);
    Sales_Point::row(_("User's POS") . ':', 'pos', null);
    Reports_UI::print_profiles_row(_("Printing profile") . ':', 'profile', null, _('Browser printing support'));
    Forms::checkRow(_("Use popup window for reports:"), 'rep_popup', Input::_post('rep_popup'), false, _('Set this option to on if your browser directly supports pdf files'));
    Forms::checkRow(_("Must change password next logon:"), 'change_password');
    Table::end(1);
    Forms::submitAddUpdateCenter($selected_id == -1, '', 'both');
    Forms::end();
    $this->Page->end_page();
    /**
     * @param \Auth $auth
     *
     * @internal param $user
     * @return bool

    function can_process(Auth $auth) {
      if (strlen($_POST['user_id']) < 4) {
        Event::error(_("The user login entered must be at least 4 characters long."));
        JS::_setFocus('user_id');
        return false;
      }
      $check = $auth->checkPasswordStrength($_POST['password']);
      if (!$check && $check['error'] > 0) {
        Event::error($check['text']);
        return false;
      }
      if (!$check && $check['strength'] < 3) {
        Event::error(_("Password Too Weak!"));
        return false;
      }
      return true;
    }

  */
