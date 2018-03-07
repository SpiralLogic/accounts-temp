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
    class GL_Class
    {
        public static $types = [
            CL_ASSETS => "Assets", //
            CL_LIABILITIES => "Liabilities", //
            CL_EQUITY => "Equity", //
            CL_INCOME => "Income", //
            CL_COGS => "Cost of Goods Sold", //
            CL_EXPENSE => "Expense",
        ];

        /**
         * @static
         *
         * @param $id
         * @param $name
         * @param $ctype
         *
         * @return null|PDOStatement
         */
        public static function add($id, $name, $ctype)
        {
            $sql = "INSERT INTO chart_class (cid, class_name, ctype)
        VALUES (" . DB::_escape($id) . ", " . DB::_escape($name) . ", " . DB::_escape($ctype) . ")";
            return DB::_query($sql);
        }

        /**
         * @static
         *
         * @param $id
         * @param $name
         * @param $ctype
         *
         * @return null|PDOStatement
         */
        public static function update($id, $name, $ctype)
        {
            $sql = "UPDATE chart_class SET class_name=" . DB::_escape($name) . ",
        ctype=" . DB::_escape($ctype) . " WHERE cid = " . DB::_escape($id);
            return DB::_query($sql);
        }

        /**
         * @static
         *
         * @param bool $all
         * @param      $balance
         *
         * @return null|PDOStatement
         */
        public static function getAll($all = false, $balance = -1)
        {
            $sql = "SELECT * FROM chart_class";
            if (!$all) {
                $sql .= " WHERE !inactive";
            }
            if ($balance == 0) {
                $sql .= " AND ctype>" . CL_EQUITY . " OR ctype=0";
            } elseif ($balance == 1) {
                $sql .= " AND ctype>0 AND ctype<" . CL_INCOME;
            }
            $sql .= " ORDER BY cid";
            return DB::_query($sql, "could not get account classes");
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
            $sql = "SELECT * FROM chart_class WHERE cid = " . DB::_escape($id);
            $result = DB::_query($sql, "could not get account type");
            return DB::_fetch($result);
        }

        /**
         * @static
         *
         * @param $id
         *
         * @return mixed
         */
        public static function get_name($id)
        {
            $sql = "SELECT class_name FROM chart_class WHERE cid =" . DB::_escape($id);
            $result = DB::_query($sql, "could not get account type");
            $row = DB::_fetchRow($result);
            return $row[0];
        }

        /**
         * @static
         *
         * @param $id
         */
        public static function delete($id)
        {
            $sql = "DELETE FROM chart_class WHERE cid = " . DB::_escape($id);
            DB::_query($sql, "could not delete account type");
        }

        /**
         * @static
         *
         * @param      $name
         * @param null $selected_id
         * @param bool $submit_on_change
         *
         * @return string
         */
        public static function  select($name, $selected_id = null, $submit_on_change = false)
        {
            $sql = "SELECT cid, class_name FROM chart_class";
            return Forms::selectBox(
                $name,
                $selected_id,
                $sql,
                'cid',
                'class_name',
                [
                    'select_submit' => $submit_on_change,
                    'async' => false
                ]
            );
        }

        /**
         * @static
         *
         * @param      $label
         * @param      $name
         * @param null $selected_id
         * @param bool $submit_on_change
         */
        public static function  cells($label, $name, $selected_id = null, $submit_on_change = false)
        {
            if ($label != null) {
                echo "<td>$label</td>\n";
            }
            echo "<td>";
            echo GL_Class::select($name, $selected_id, $submit_on_change);
            echo "</td>\n";
        }

        /**
         * @static
         *
         * @param      $label
         * @param      $name
         * @param null $selected_id
         * @param bool $submit_on_change
         */
        public static function  row($label, $name, $selected_id = null, $submit_on_change = false)
        {
            echo "<tr><td class='label'>$label</td>";
            GL_Class::cells(null, $name, $selected_id, $submit_on_change);
            echo "</tr>\n";
        }

        /**
         * @static
         *
         * @param      $label
         * @param      $name
         * @param null $selected_id
         * @param bool $submit_on_change
         */
        public static function  types_row($label, $name, $selected_id = null, $submit_on_change = false)
        {
            echo "<tr><td class='label'>$label</td><td>";
            echo Forms::arraySelect($name, $selected_id, GL_Class::$types, ['select_submit' => $submit_on_change]);
            echo "</td></tr>\n";
        }
    }
