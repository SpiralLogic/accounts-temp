<?php
    /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
    namespace ADV\Controllers\Access;

    use ADV\App\Form\Form;
    use ADV\App\Dates;
    use ADV\Core\Auth;
    use ADV\Core\View;

    /** **/
    class Password extends \ADV\App\Controller\Action
    {
        public $view;
        /** @var \ADV\Core\Config */
        protected $Config;
        /** @var \ADV\App\Dates */
        protected $security = SA_CHGPASSWD;

        protected function before()
        {
        }

        protected function index()
        {
            $this->setTitle("Change Password");
            $form = new Form();
            echo $form->start('changepwd');
            $form->password('password')->label('Password');
            $form->password('password2')->label('Repeat Password');
            $password_iv = Auth::generateIV();
            $form->hidden('password_iv')->value($this->Session->setFlash('password_iv', $password_iv));
            unset($_POST['user_name'], $_POST['password'], $_POST['SubmitUser'], $_POST['login_company']);
            $form->submit('change', "Change")->type(\ADV\App\Form\Button::SMALL)->type('inverse');
            echo $form;
            echo $form->end();
            $this->JS->footerFile('/js/libs/aes.js');
        }
    }

