<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\GL {
    use ADV\Core\DB\DB;
    use GL_QuickEntry;
    use ADV\App\Pager\Pager;
    use ADV\App\Validation;

    /**

     */
    class QuickEntry extends \ADV\App\DB\Base implements \ADV\App\Pager\Pageable
    {

      protected $_table = 'quick_entries';
      protected $_classname = 'Quick Entry';
      protected $_id_column = 'id';
      public $id = 0;
      public $type = 0;
      public $description;
      public $base_amount = 0.0000;
      public $base_desc;
      /**
       * @return \ADV\Core\Traits\Status|bool
       */
      public function delete() {
        $count = static::$DB->select('count(*) as count')->from('quick_entry_lines')->where('qid=', $this->id)->fetch()->one();
        if ($count) {
          return $this->status('false', "Could not retreive quick entry");
        }
        return parent::delete();
      }
      /**
       * @return \ADV\Core\Traits\Status|bool
       */
      protected function canProcess() {
        if (strlen($this->description) == 0) {
          return $this->status(false, 'The Quick Entry description cannot be empty.', 'description');
        }
        if (strlen($this->description) > 60) {
          return $this->status(false, 'Description must be not be longer than 60 characters!', 'description');
        }
        if (!Validation::is_num($this->base_amount, null)) {
          return $this->status(false, 'Base Amount must be a number', 'base_amount');
        }
        if (strlen($this->base_desc) == 0) {
          return $this->status(false, 'Base Description must be not be longer than 60 characters!', 'base_desc');
        }
        if (strlen($this->base_desc) > 60) {
          return $this->status(false, 'Base Description must be not be longer than 60 characters!', 'base_desc');
        }
        return true;
      }
      /**
       * @internal param bool $inactive
       * @return array
       */
      public static function getAll() {
        $q = DB::_select()->from('quick_entries');
        return $q->fetch()->all();
      }
      /**
       * @internal param bool $inactive
       * @return array
       */
      public function getLines() {
        DB::_query(
          'SELECT id, qid, action, account_name, dest_id, amount FROM quick_entry_lines ,chart_master WHERE account_code=dest_id  AND qid=' . DB::_quote($this->id) . 'UNION' . '
          SELECT qel.id, qid, action, name, dest_id, amount FROM quick_entry_lines qel,tax_types WHERE tax_types.id=dest_id  AND qid=' . DB::_quote($this->id)
        );
        return DB::_fetchAll();
      }
      /**
       * @return array
       */
      public function getPagerColumns() {
        $cols = [
          ['type' => 'skip'],
          'Type'        => ['fun' => [$this, 'formatType']],
          'Description',
          'Base Amount' => ['type' => Pager::TYPE_AMOUNT],
          'Description',
        ];
        return $cols;
      }
      /**
       * @param $row
       *
       * @return mixed
       */
      public function formatType($row) {
        return GL_QuickEntry::$types[$row['type']];
      }
    }
  }
  namespace {
    use ADV\Core\DB\DB;
    use ADV\App\Forms;
    use ADV\App\User;
    use ADV\Core\Num;
    use ADV\App\Page;
    use ADV\Core\JS;
    use ADV\Core\Event;

    /**

     */
    class GL_QuickEntry
    {

      public static $actions
        = [
          '='  => 'Remainder', // post current base amount to GL account
          'a'  => 'Amount', // post amount to GL account
          'a+' => 'Amount, increase base', // post amount to GL account and increase base
          'a-' => 'Amount, reduce base', // post amount to GL account and reduce base
          '%'  => '% amount of base', // store acc*amount% to GL account
          '%+' => '% amount of base, increase base', // ditto & increase base amount
          '%-' => '% amount of base, reduce base', // ditto & reduce base amount
          'T'  => 'Taxes added', // post taxes calculated on base amount
          'T+' => 'Taxes added, increase base', // ditto & increase base amount
          'T-' => 'Taxes added, reduce base', // ditto & reduce base amount
          't'  => 'Taxes included', // post taxes calculated on base amount
          't+' => 'Taxes included, increase base', // ditto & increase base amount
          't-' => 'Taxes included, reduce base' // ditto & reduce base amount
        ];
      public static $types
        = [
          QE_DEPOSIT => "Bank Deposit", //
          QE_PAYMENT => "Bank Payment", //
          QE_JOURNAL => "Journal Entry", //
          QE_SUPPINV => "Supplier Invoice/Credit"
        ];
      /**
       * @static
       *
       * @param $description
       * @param $type
       * @param $base_amount
       * @param $base_desc
       */
      public static function add($description, $type, $base_amount, $base_desc) {
        $sql
          = "INSERT INTO quick_entries (description, type, base_amount, base_desc)
        VALUES (" . DB::_escape($description) . ", " . DB::_escape($type) . ", " . DB::_escape($base_amount) . ", " . DB::_escape($base_desc) . ")";
        DB::_query($sql, "could not insert quick entry for $description");
      }
      /**
       * @static
       *
       * @param $selected_id
       * @param $description
       * @param $type
       * @param $base_amount
       * @param $base_desc
       */
      public static function update($selected_id, $description, $type, $base_amount, $base_desc) {
        $sql = "UPDATE quick_entries	SET description = " . DB::_escape($description) . ",
            type=" . DB::_escape($type) . ", base_amount=" . DB::_escape($base_amount) . ", base_desc=" . DB::_escape($base_desc) . "
            WHERE id = " . DB::_escape($selected_id);
        DB::_query($sql, "could not update quick entry for $selected_id");
      }
      /**
       * @static
       *
       * @param $selected_id
       */
      public static function delete($selected_id) {
        $sql = "DELETE FROM quick_entries WHERE id=" . DB::_escape($selected_id);
        DB::_query($sql, "could not delete quick entry $selected_id");
      }
      /**
       * @static
       *
       * @param $qid
       * @param $action
       * @param $dest_id
       * @param $amount
       * @param $dim
       * @param $dim2
       */
      public static function add_line($qid, $action, $dest_id, $amount, $dim, $dim2) {
        $sql
          = "INSERT INTO quick_entry_lines
            (qid, action, dest_id, amount, dimension_id, dimension2_id)
        VALUES
            ($qid, " . DB::_escape($action) . "," . DB::_escape($dest_id) . ",
                " . DB::_escape($amount) . ", " . DB::_escape($dim) . ", " . DB::_escape($dim2) . ")";
        DB::_query($sql, "could not insert quick entry line for $qid");
      }
      /**
       * @static
       *
       * @param $selected_id
       * @param $qid
       * @param $action
       * @param $dest_id
       * @param $amount
       * @param $dim
       * @param $dim2
       */
      public static function update_line($selected_id, $qid, $action, $dest_id, $amount, $dim, $dim2) {
        $sql = "UPDATE quick_entry_lines SET qid = " . DB::_escape($qid) . ", action=" . DB::_escape($action) . ",
            dest_id=" . DB::_escape($dest_id) . ", amount=" . DB::_escape($amount) . ", dimension_id=" . DB::_escape($dim) . ", dimension2_id=" . DB::_escape($dim2) . "
            WHERE id = " . DB::_escape($selected_id);
        DB::_query($sql, "could not update quick entry line for $selected_id");
      }
      /**
       * @static
       *
       * @param $selected_id
       */
      public static function delete_line($selected_id) {
        $sql = "DELETE FROM quick_entry_lines WHERE id=" . DB::_escape($selected_id);
        DB::_query($sql, "could not delete quick entry line $selected_id");
      }
      /**
       * @static
       *
       * @param null $type
       *
       * @return bool
       */
      public static function has($type = null) {
        $sql = "SELECT id FROM quick_entries";
        if ($type != null) {
          $sql .= " WHERE type=" . DB::_escape($type);
        }
        $result = DB::_query($sql, "could not retreive quick entries");
        return DB::_numRows($result) > 0;
      }
      /**
       * @static
       *
       * @param null $type
       *
       * @return null|PDOStatement
       */
      public static function getAll($type = null) {
        $sql = "SELECT * FROM quick_entries";
        if ($type != null) {
          $sql .= " WHERE type=" . DB::_escape($type);
        }
        $sql .= " ORDER BY description";
        return DB::_query($sql, "could not retreive quick entries");
      }
      /**
       * @static
       *
       * @param $selected_id
       *
       * @return \ADV\Core\DB\Query\Result|Array
       */
      public static function get($selected_id) {
        $sql    = "SELECT * FROM quick_entries WHERE id=" . DB::_escape($selected_id);
        $result = DB::_query($sql, "could not retreive quick entry $selected_id");
        return DB::_fetch($result);
      }
      /**
       * @static
       *
       * @param $qid
       *
       * @return null|PDOStatement
       */
      public static function get_lines($qid) {
        $sql
          = "SELECT quick_entry_lines.*, chart_master.account_name,
                tax_types.name as tax_name
            FROM quick_entry_lines
            LEFT JOIN chart_master ON
                quick_entry_lines.dest_id = chart_master.account_code
            LEFT JOIN tax_types ON
                quick_entry_lines.dest_id = tax_types.id
            WHERE
                qid=" . DB::_escape($qid) . " ORDER by id";
        return DB::_query($sql, "could not retreive quick entries");
      }
      /**
       * @static
       *
       * @param $qid
       *
       * @return bool
       */
      public static function has_lines($qid) {
        $sql    = "SELECT id FROM quick_entry_lines WHERE qid=" . DB::_escape($qid);
        $result = DB::_query($sql, "could not retreive quick entries");
        return DB::_numRows($result) > 0;
      }
      /**
       * @static
       *
       * @param $selected_id
       *
       * @return \ADV\Core\DB\Query\Result|Array
       */
      public static function has_line($selected_id) {
        $sql    = "SELECT * FROM quick_entry_lines WHERE id=" . DB::_escape($selected_id);
        $result = DB::_query($sql, "could not retreive quick entry for $selected_id");
        return DB::_fetch($result);
      }
      //
      //	Expands selected quick entry $id into GL posings and adds to order.
      //		returns calculated amount posted to bank GL account.
      //
      /**
       * @static
       *
       * @param Sales_Order|Purch_Order $order
       * @param        $id
       * @param        $base
       * @param        $type
       * @param string $descr
       *
       * @return int
       */
      public static function addEntry(&$order, $id, $base, $type, $descr = '') {
        $bank_amount = 0;
        if (!isset($id) || $id == null || $id == "") {
          Event::error(_("No Quick Entries are defined."));
          JS::_setFocus('total_amount');
        } else {
          if ($type == QE_DEPOSIT) {
            $base = -$base;
          }
          if ($type != QE_SUPPINV) // only one quick entry on journal/bank transaction
          {
            $order->clear_items();
          }
          $qe = GL_QuickEntry::get($id);
          if ($descr != '') {
            $qe['description'] .= ': ' . $descr;
          }
          $result   = GL_QuickEntry::get_lines($id);
          $totrate  = 0;
          $qe_lines = [];
          while ($row = DB::_fetch($result)) {
            $qe_lines[] = $row;
            switch (strtolower($row['action'])) {
              case "t": // post taxes calculated on base amount
              case "t+": // ditto & increase base amount
              case "t-": // ditto & reduce base amount
                if (substr($row['action'], 0, 1) != 'T') {
                  $totrate += Tax_Type::get_default_rate($row['dest_id']);
                }
            }
          }
          $first   = true;
          $taxbase = 0;
          if (!count($qe_lines)) {
            Event::error(_('There are no lines for this quick entry!'));
            return false;
          }
          foreach ($qe_lines as $qe_line) {
            switch (strtolower($qe_line['action'])) {
              case "=": // post current base amount to GL account
                $part = $base;
                break;
              case "a": // post amount to GL account and reduce base
                $part = $qe_line['amount'];
                break;
              case "a+": // post amount to GL account and increase base
                $part = $qe_line['amount'];
                $base += $part;
                break;
              case "a-": // post amount to GL account and reduce base
                $part = $qe_line['amount'];
                $base -= $part;
                break;
              case "%": // store acc*amount% to GL account
                $part = Num::_round($base * $qe_line['amount'] / 100, User::_price_dec());
                break;
              case "%+": // ditto & increase base amount
                $part = Num::_round($base * $qe_line['amount'] / 100, User::_price_dec());
                $base += $part;
                break;
              case "%-": // ditto & reduce base amount
                $part = Num::_round($base * $qe_line['amount'] / 100, User::_price_dec());
                $base -= $part;
                break;
              case "t": // post taxes calculated on base amount
              case "t+": // ditto & increase base amount
              case "t-": // ditto & reduce base amount
                if ($first) {
                  $taxbase = $base / ($totrate + 100);
                  $first   = false;
                }
                if (substr($qe_line['action'], 0, 1) != 'T') {
                  $part = $taxbase;
                } else {
                  $part = $base / 100;
                }
                $item_tax = Tax_Type::get($qe_line['dest_id']);
                //if ($type == QE_SUPPINV && substr($qe_line['action'],0,1) != 'T')
                if ($type == QE_SUPPINV) {
                  $taxgroup = $order->tax_group_id;
                  $rates    = 0;
                  $res      = Tax_Groups::get_for_item($order->tax_group_id);
                  while ($row = DB::_fetch($res)) {
                    $rates += $row['rate'];
                  }
                  if ($rates == 0) {
                    continue 2;
                  }
                }
                $tax = Num::_round($part * $item_tax['rate'], User::_price_dec());
                if ($tax == 0) {
                  continue 2;
                }
                $gl_code = ($type == QE_DEPOSIT || ($type == QE_JOURNAL && $base < 0)) ? $item_tax['sales_gl_code'] : $item_tax['purchasing_gl_code'];
                if (!Tax_Type::is_tax_gl_unique($gl_code)) {
                  Event::error(_("Cannot post to GL account used by more than one tax type."));
                  break 2;
                }
                if ($type != QE_SUPPINV) {
                  $order->add_gl_item($gl_code, $qe_line['dimension_id'], $qe_line['dimension2_id'], $tax, $qe['description']);
                } else {
                  $acc_name = GL_Account::get_name($gl_code);
                  $order->add_gl_codes_to_trans($gl_code, $acc_name, $qe_line['dimension_id'], $qe_line['dimension2_id'], $tax, $qe['description']);
                }
                if (strpos($qe_line['action'], '+')) {
                  $base += $tax;
                } elseif (strpos($qe_line['action'], '-')) {
                  $base -= $tax;
                }
                continue 2;
            }
            if ($type != QE_SUPPINV) {
              $order->add_gl_item($qe_line['dest_id'], $qe_line['dimension_id'], $qe_line['dimension2_id'], $part, $qe['description']);
            } else {
              $acc_name = GL_Account::get_name($qe_line['dest_id']);
              $order->add_gl_codes_to_trans($qe_line['dest_id'], $acc_name, $qe_line['dimension_id'], $qe_line['dimension2_id'], $part, $qe['description']);
            }
          }
        }
        return $bank_amount;
      }
      /**
       * @static
       *
       * @param      $name
       * @param null $selected_id
       * @param null $type
       * @param bool $submit_on_change
       *
       * @return string
       */
      public static function select($name, $selected_id = null, $type = null, $submit_on_change = false) {
        $where = false;
        $sql   = "SELECT id, description FROM quick_entries";
        if ($type != null) {
          $sql .= " WHERE type=$type";
        }
        return Forms::selectBox(
          $name,
          $selected_id,
          $sql,
          'id',
          'description',
          [
            'spec_id'       => '',
            'order'         => 'description',
            'select_submit' => $submit_on_change,
            'async'         => false
          ]
        );
      }
      /**
       * @static
       *
       * @param      $label
       * @param      $name
       * @param null $selected_id
       * @param      $type
       * @param bool $submit_on_change
       */
      public static function cells($label, $name, $selected_id = null, $type, $submit_on_change = false) {
        if ($label != null) {
          echo "<td>$label</td>\n";
        }
        echo "<td>";
        echo GL_QuickEntry::select($name, $selected_id, $type, $submit_on_change);
        echo "</td>";
      }
      /**
       * @static
       *
       * @param      $label
       * @param      $name
       * @param null $selected_id
       * @param      $type
       * @param bool $submit_on_change
       */
      public static function row($label, $name, $selected_id = null, $type, $submit_on_change = false) {
        echo "<tr><td class='label'>$label</td>";
        GL_QuickEntry::cells(null, $name, $selected_id, $type, $submit_on_change);
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
      public static function actions($label, $name, $selected_id = null, $submit_on_change = false) {
        echo "<tr><td class='label'>$label</td><td>";
        echo Forms::arraySelect($name, $selected_id, static::$actions, ['select_submit' => $submit_on_change]);
        echo "</td></tr>\n";
      }
      /**
       * @static
       *
       * @param      $label
       * @param      $name
       * @param null $selected_id
       * @param bool $submit_on_change
       */
      public static function types($label, $name, $selected_id = null, $submit_on_change = false) {
        echo "<tr><td class='label'>$label</td><td>";
        echo Forms::arraySelect($name, $selected_id, static::$types, ['select_submit' => $submit_on_change]);
        echo "</td></tr>\n";
      }
    }
  }
