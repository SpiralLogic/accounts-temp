<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class GL_PaymentMethod
  {
    /**
     * @static
     *
     * @param     $payment_method
     * @param     $undeposited
     * @param int $inactive
     */
    public static function add($payment_method, $undeposited, $inactive = 0)
    {
      DB::_insert('payment_methods')->values(array('name'       => $payment_method,
                                                 'undeposited' => $undeposited,
                                                 'inactive'    => $inactive
                                            ))->exec();
    }
    /**
     * @static
     *
     * @param     $id
     * @param     $payment_method
     * @param     $undeposited
     * @param int $inactive
     */
    public static function update($id, $payment_method, $undeposited, $inactive = 0)
    {
      DB::_update('payment_methods')->values(array('name'       => $payment_method,
                                                 'undeposited' => $undeposited,
                                                 'inactive'    => $inactive
                                            ))->where('id=', $id)->exec();
    }
    /**
     * @static
     *
     * @param $id
     */
    public static function delete($id)
    {
      DB::_delete('payment_methods')->where('id=', $id)->exec();
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return \ADV\Core\DB\Query\Result|Array
     */
    public static function get($id)
    {
      $sql    = "SELECT * FROM payment_methods WHERE id=" . DB::_escape($id);
      $result = DB::_query($sql, "could not retreive bank account for $id");

      return DB::_fetch($result);
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     *
     * @return string
     */
    public static function select($name, $selected_id = null)
    {
      $result = DB::_select('name')->from('payment_methods')->where('inactive=', 0);
      while ($row = DB::_fetch($result)) {
        $payment_methods[] = $row['name'];
      }

      return Forms::arraySelect($name, $selected_id, $payment_methods);
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     */
    public static function cells($label, $name, $selected_id = null)
    {
      if ($label != null) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo GL_PaymentMethod::select($name, $selected_id);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     */
    public static function row($label, $name, $selected_id = null)
    {
      echo "<tr><td class='label'>$label</td>";
      Bank_Account::type_cells(null, $name, $selected_id);
      echo "</tr>\n";
    }
  }
