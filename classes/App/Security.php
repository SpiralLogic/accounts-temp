<?php

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App;

  /**
   * @property Security i
   * @method Security i
   */
  use ArrayAccess;
  use ADV\Core\DB\DB;
  use ADV\Core\Config;

  class Security extends \ADV\Core\Security implements ArrayAccess
  {
    public $areas;
    protected $sections;
    protected $Config;
    /**
     * @param \ADV\Core\Config $config
     */
    public function __construct(\ADV\Core\Config $config = null) {
      $this->Config   = $config ? : Config::i();
      $this->areas    = $this->Config->get('access_levels.areas');
      $this->sections = $this->Config->get('access_levels.sections');
    }
    /**
     * @param User $user
     * @param      $page_level
     *
     * @return bool
     */
    public function hasAccess(User $user, $page_level) {

      if ($page_level === SA_OPEN) {
        return true;
      }
      if ($page_level === SA_DENIED || $page_level === '') {
        return false;
      }
      $access = false;
      if (isset($this->areas[$page_level])) {
        $code   = $this->areas[$page_level][0];
        $access = $code && $user->hasRole($code);
      } elseif ($user->hasSectionAccess($page_level)) {
        $access = $user->hasSectionAccess($page_level);
      }

      // only first registered company has site admin privileges
      return $access && ($user->company == 'default' || (isset($code) && ($code & ~0xff) != SS_SADMIN));
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return \ADV\Core\DB\Query\Result|Array
     */
    public function get_role($id) {
      $sql = "SELECT * FROM security_roles WHERE id='$id'";
      $ret = DB::_query($sql, "could not retrieve security roles");
      $row = DB::_fetch($ret);
      if ($row != false) {
        $row['areas']    = explode(';', $row['areas']);
        $row['sections'] = explode(';', $row['sections']);
      }

      return $row;
    }
    /**
     * @static
     *
     * @param $name
     * @param $description
     * @param $sections
     * @param $areas
     */
    public static function add_role($name, $description, $sections, $areas) {
      $sql = "INSERT INTO security_roles (role, description, sections, areas) VALUES (" . //
        DB::_escape($name) . "," . //
        DB::_escape($description) . "," . //
        DB::_escape(implode(';', $sections)) . ", " . //
        DB::_escape(implode(';', $areas)) . ")";
      DB::_query($sql, "could not add new security role");
    }
    /**
     * @static
     *
     * @param $id
     * @param $name
     * @param $description
     * @param $sections
     * @param $areas
     */
    public static function update_role($id, $name, $description, $sections, $areas) {
      $sql = "UPDATE security_roles SET" . //
        " role=" . DB::_escape($name) . "," . //
        " description=" . DB::_escape($description) . "," . //
        " sections=" . DB::_escape(implode(';', $sections)) . "," . //
        " areas=" . DB::_escape(implode(';', $areas)) . //
        " WHERE id=$id";
      DB::_query($sql, "could not update role");
    }
    /**
     * @static
     *
     * @param $id
     */
    public static function delete($id) {
      $sql = "DELETE FROM security_roles WHERE id=$id";
      DB::_query($sql, "could not delete role");
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return mixed
     */
    public static function check_role_used($id) {
      $sql = "SELECT count(*) FROM users WHERE role_id=$id";
      $ret = DB::_query($sql, 'cannot check role usage');
      $row = DB::_fetch($ret);

      return $row[0];
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $new_item
     * @param bool $submit_on_change
     * @param bool $show_inactive
     *
     * @return string
     */
    public static function roles($name, $selected_id = null, $new_item = false, $submit_on_change = false, $show_inactive = false) {
      $sql = "SELECT id, role, inactive FROM security_roles";

      return Forms::selectBox(
        $name,
        $selected_id,
        $sql,
        'id',
        'description',
        array(
             'spec_option'                               => $new_item ? _("New role") : false,
             'spec_id'                                   => '',
             'select_submit'                             => $submit_on_change,
             'show_inactive'                             => $show_inactive
        )
      );
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $new_item
     * @param bool $submit_on_change
     * @param bool $show_inactive
     */
    public static function roles_cells($label, $name, $selected_id = null, $new_item = false, $submit_on_change = false, $show_inactive = false) {
      if ($label != null) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo static::roles($name, $selected_id, $new_item, $submit_on_change, $show_inactive);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $new_item
     * @param bool $submit_on_change
     * @param bool $show_inactive
     */
    public function roles_row($label, $name, $selected_id = null, $new_item = false, $submit_on_change = false, $show_inactive = false) {
      echo "<tr><td class='label'>$label</td>";
      Security::roles_cells(null, $name, $selected_id, $new_item, $submit_on_change, $show_inactive);
      echo "</tr>\n";
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset <p>
     *                      An offset to check for.
     * </p>
     *
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     *       The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset) {
      // TODO: Implement offsetExists() method.
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset <p>
     *                      The offset to retrieve.
     * </p>
     *
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset) {
      switch ($offset) {
        case 'areas':
          return $this->areas;
        case 'sections':
          return $this->sections;
        default:
          return false;
      }
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset <p>
     *                      The offset to assign the value to.
     * </p>
     * @param mixed $value  <p>
     *                      The value to set.
     * </p>
     *
     * @return void
     */
    public function offsetSet($offset, $value) {
      return;
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset <p>
     *                      The offset to unset.
     * </p>
     *
     * @return void
     */
    public function offsetUnset($offset) {
      // TODO: Implement offsetUnset() method.
    }
  }
