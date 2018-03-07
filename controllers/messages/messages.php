<?php
    /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
    if (REQUEST_AJAX && isset($_POST['user_id'])) {
        Messages::set($_POST['user_id'], $_POST['subject'], $_POST['message']);
        Event::success("Message sent!");
        JS::_renderJSON([]);
    }
    JS::_footerFile("/js/messages.js");
    Page::start(_($help_context = "Messages"), SA_OPEN, Input::_request('frame'));
    echo HTML::div(['style' => 'margin:0 auto;text-align:center']);
    Users::row(_("User:"), 'user_id');
    echo HTML::br(false)->label(
             array(
                  'content' => "Subject: ",
                  'for'     => 'subject'
             )
    )->br->input('subject', ['size' => 50])->label;
    echo HTML::br(false)->label(
             [
             'content' => "Message: ",
             'for'     => 'message'
             ]
    )->br->textarea(
         'message',
           array(
                'cols'  => 35,
                'rows'  => 5,
                'title' => 'Message to send:'
           )
      )->textarea->label->br;
    echo HTML::button('btnSend', 'Send Message');
    echo HTML::_div();
    Page::end();
