<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App;

  use ADV\Core\DB\DB;
  use ADV\Core\Dialog;

  /** **/
  class Messages
  {
    /** @var string * */
    protected static $messages = '';
    /** @var int * */
    protected static $count = 0;
    /**

     */
    public function __construct() {
    }
    /**
     * @static
     *
     * @param bool $userid
     *
     * @return bool|int
     */
    public static function  get($userid = false) {
      if (!$userid) {
        return false;
      }
      $result        = DB::_select('um.*,u.real_name as `from`')->from('user_messages um, users u')->where('um.user=', $userid)->andWhere('um.from=u.id')->andWhere('unread>', 0)
        ->fetch()
        ->all();
      static::$count = count($result);
      foreach ($result as $row) {
        if (!empty($row['subject'])) {
          static::$messages .= '<div class="subject"><span>From:</span>' . e($row['from']) . "<br><span>Subject:</span>" . e($row['subject']) . '</div>';
          static::$messages .= '<hr/><div class="message">' . e(trim($row['message'])) . '</div>';
        } else {
          static::$messages .= '<hr/> <div class="message">' . trim($row['message']) . '</div>';
        }
        $unread = $row['unread'] - 1;
        $id     = $row['id'];
        $sql2   = "UPDATE user_messages SET unread={$unread} WHERE id={$id} AND user=" . $userid;
        DB::_query($sql2, 'Could not mark messages as unread');
      }
      return static::$count;
    }
    /**
     * @static
     *
     * @param $userid
     * @param $subject
     * @param $message
     *
     * @return null|\PDOStatement
     */
    public static function set($userid, $subject, $message) {
      $sql    = "INSERT INTO user_messages (user, subject,message,unread,`from`) VALUES (" . DB::_escape($userid) . ", " . DB::_escape($subject) . ", " . DB::_escape(
        $message
      ) . ", 1, " . DB::_escape(User::_i()->user) . ")";
      $result = DB::_query($sql, "Couldn't add message for $userid");
      return $result;
    }
    /**
     * @static
     *
     * @param User $user
     *
     * @return string
     */
    public static function show($user = null) {
      $user = $user ? : User::_i();
      if (!$user || !$user->logged) {
        return '';
      }
      ob_start();
      static::get($user->user);
      if (static::$count > 0) {
        static::makeDialog();
      }
      return ob_get_clean();
    }
    public static function makeDialog() {
      $dialog = new Dialog(static::$count . ' New Messages', 'messagesbox', static::$messages);
      $dialog->addButtons(array('Close' => '$(this).dialog("close");'));
      $dialog->setOptions(
        array(
             'autoOpen'   => true,
             'modal'      => true,
             'width'      => '500',
             'resizeable' => false
        )
      );
      $dialog->show();
    }
  }

