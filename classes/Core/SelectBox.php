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
  namespace ADV\Core;

  use ADV\Core\DB\DB;
  use ADV\Core\Input\Input;

  /** **/
  class SelectBox
  {
    /** @var array * */
    protected $where = []; // additional constraints
    /** @var */
    protected $order; // list sort order
    // special option parameters
    /** @var bool * */
    protected $spec_option = false; // option text or false
    /** @var int * */
    protected $spec_id = 0; // option id
    /** @var bool * */
    protected $cache = false; // option id
    // submit on select parameters
    /** @var string * */
    protected $default = ''; // default value when $_POST is not set
    /** @var bool * */
    protected $multi = false; // multiple select
    /** @var bool * */
    protected $select_submit = false; //submit on select: true/false
    /** @var bool * */
    protected $async = true; // select update via ajax (true) vs _page_body reload
    protected $class = ''; // select update via ajax (true) vs _page_body reload
    // search box parameters
    /** @var null * */
    protected $sel_hint = null;
    /** @var bool * */
    protected $search_box = false; // name or true/false
    /** @var int * */
    protected $type = 0; // type of extended selector:
    // 0 - with (optional) visible search box, search by id
    // 1 - with hidden search box, search by option text
    // 2 - TODO reverse: box with hidden selector available via enter; this
    // would be convenient for optional ad hoc adding of new item
    /** @var bool * */
    protected $search_submit = true; //search submit button: true/false
    /** @var int * */
    protected $size = 8; // size and max of box tag
    /** @var int * */
    protected $max = 50;
    /** @var bool * */
    protected $height = false; // number of lines in select box
    /** @var bool * */
    protected $cells = false; // combo displayed as 2 <td></td> cells
    /** @var array * */
    protected $search = []; // sql field names to search
    /** @var Callable * */
    protected $format; // format functions for regular options
    /** @var bool * */
    protected $disabled = false;
    /** @var null * */
    protected $box_hint = null; // box/selectors hints; null = std see below
    /** @var bool * */
    protected $category = false; // category column name or false
    /** @var bool * */
    protected $show_inactive = false; // show inactive records.
    /** @var bool * */
    protected $editable = false; // false, or length of editable entry field
    /** @var string * */
    protected $rel = ''; // false, or length of editable entry field
    /** @var */
    protected $name;
    /** @var array * */
    protected $selected_id;
    /** @var */
    protected $sql;
    /** @var */
    protected $valfield;
    /** @var */
    protected $namefield;
    /** @var Input */
    protected $Input;
    /** @var JS */
    protected $JS;
    /** @var Ajax */
    protected $Ajax;
    /** @var DB */
    static $DB;
    /**
     * @param       $name
     * @param null  $selected_id
     * @param       $sql
     * @param       $valfield
     * @param       $namefield
     * @param array $options
     */
    public function __construct($name, $selected_id = null, $sql, $valfield, $namefield, $options = []) {
      $this->name        = $name;
      $this->order       = $namefield;
      $this->selected_id = $selected_id;
      $this->sql         = $sql;
      $this->valfield    = $valfield;
      $this->namefield   = $namefield;
      static::$DB        = DB::i();
      $this->Input       = Input::i();
      $this->JS          = JS::i();
      $this->Ajax        = Ajax::i();
      $options           = (array)$options;
      foreach ($options as $option => $value) {
        if (property_exists($this, $option)) {
          $this->$option = $value;
        }
      }
      if (!is_array($this->where)) {
        $this->where = array($this->where);
      }
    }
    /**
     * @return string
     */
    public function create() {
      // ------ merge options with defaults ----------
      $search_box = $this->search_box === true ? '_' . $this->name . '_edit' : $this->search_box;
      // select content filtered by search field:
      $search_submit = $this->search_submit === true ? '_' . $this->name . '_button' : $this->search_submit;
      // select set by select content field
      $search_button = $this->editable ? '_' . $this->name . '_button' : ($search_box ? $search_submit : false);
      $select_submit = $this->select_submit;
      $by_id         = ($this->type == 0);
      $class         = $this->class .= ($by_id ? ' combo' : ' combo2');
      $disabled      = $this->disabled ? "disabled" : '';
      $multi         = $this->multi;
      if (!count($this->search)) {
        $this->search = array($by_id ? $this->valfield : $this->namefield);
      }
      if ($this->sel_hint === null) {
        $this->sel_hint = $by_id || $search_box == false ? '' : _('Press Space tab for search pattern entry');
      }
      if ($this->box_hint === null) {
        $this->box_hint = $search_box && $search_submit != false ?
          ($by_id ? _('Enter code fragment to search or * for all') : _('Enter description fragment to search or * for all')) : '';
      }
      if ($this->selected_id == null) {
        $this->selected_id = $this->Input->post($this->name, null, (string)$this->default);
      }
      if (!is_array($this->selected_id)) {
        $this->selected_id = array((string)$this->selected_id);
      } // code is generalized for multiple selection support
      $txt = $this->Input->post($search_box);
      if (isset($_POST['_' . $this->name . '_update'])) { // select list or search box change
        if ($by_id) {
          $txt = $_POST[$this->name];
        }
        if (!$this->async) {
          $this->Ajax->activate('_page_body');
        } else {
          $this->Ajax->activate($this->name);
        }
      }
      if (isset($_POST[$search_button])) {
        if (!$this->async) {
          $this->Ajax->activate('_page_body');
        } else {
          $this->Ajax->activate($this->name);
        }
      }
      $search_button_in_post = $this->Input->post($search_button);
      $this->generateSQL($search_box, $search_button, $txt);
      // ------ make selector ----------
      $selector = $first_opt = '';
      $first_id = false;
      $found    = false;
      $lastcat  = null;
      $edit     = false;
      if ($result = $this->executeSQL()) {
        while ($row = static::$DB->fetch($result)) {
          $value = $row[0];
          $descr = $this->format == null ? $row[1] : call_user_func($this->format, $row);
          $sel   = '';
          if ($search_button_in_post && ($txt == $value)) {
            $this->selected_id[] = $value;
          }
          if (in_array((string)$value, $this->selected_id, true)) {
            $sel   = 'selected';
            $found = $value;
            $edit  = $this->editable && $row['editable'] && ($this->Input->post($search_box) == $value) ? $row[1] : false; // get non-formatted description
            if ($edit) {
              break; // selected field is editable - abandon list construction
            }
          }
          // show selected option even if inactive
          if ((!$this->show_inactive) && isset($row['inactive']) && @$row['inactive'] && $sel === '') {
            continue;
          } else {
            $optclass = (isset($row['inactive']) && $row['inactive']) ? "class='inactive'" : '';
          }
          if ($first_id === false) {
            $first_id = $value;
          }
          $cat = $row[$this->category];
          if ($this->category !== false && $cat != $lastcat) {
            if (isset($lastcat)) {
              $selector .= "</optgroup>";
            }
            $selector .= "<optgroup label='" . $cat . "'>\n";
            $lastcat = $cat;
          }
          $selector .= "<option $sel $optclass value='$value'>$descr</option>\n";
        }
        static::$DB->freeResult($result);
      }
      // Prepend special option.
      if ($this->spec_option !== false) { // if special option used - add it
        $first_id  = $this->spec_id;
        $first_opt = $this->spec_option;
        //	}
        //	if ($first_id !== false) {
        $sel      = $found === false ? 'selected' : '';
        $optclass = @$row['inactive'] ? "class='inactive'" : '';
        $selector = "<option $sel value='$first_id'>$first_opt</option>\n" . $selector;
      }
      if (isset($lastcat)) {
        $selector .= '</optgroup>';
      }
      if ($found === false) {
        $this->selected_id = array($first_id);
      }
      $_POST[$this->name] = $multi ? $this->selected_id : $this->selected_id[0];
      $selector           = "<select id='" . str_replace(
        ['[', ']'],
        ['-', ''],
        $this->name
      ) . "' " . ($multi ? "multiple" : '') . ($this->height !== false ? ' size="' . $this->height . '"' : '') . "$disabled name='$this->name" . ($multi ? '[]' :
        '') . "' class='$class' title='" . $this->sel_hint . "' " . $this->rel . ">" . $selector . "</select>\n";
      if ($by_id && ($search_box != false || $this->editable)) {
        // on first display show selector list
        if (isset($_POST[$search_box]) && $this->editable && $edit) {
          $selector = "<input type='hidden' name='$this->name' value='" . $_POST[$this->name] . "'>";
          if (isset($row['long_description'])) {
            $selector .= "<textarea name='{$this->name}_text' cols='{$this->max}' id='" . str_replace(
              ['[', ']'],
              ['.', ''],
              $this->name
            ) . "' " . $this->rel . " rows='2'>{$row['long_description']}</textarea></td>\n";
          } else {
            $selector .= "<input type='text' $disabled name='{$this->name}_text' id='{$this->name}_text' size='" . $this->editable . "' maxlength='" . $this->max . "' " . $this->rel . " value='$edit'>\n";
          }
          $this->JS->setFocus($this->name . '_text'); // prevent lost focus
        } else {
          if ($this->Input->post($search_submit ? $search_submit : "_{$this->name}_button")) {
            $this->JS->setFocus($this->name);
          }
        } // prevent lost focus
        if (!$this->editable) {
          $txt = $found;
        }
        $this->Ajax->addUpdate($this->name, $search_box, $txt ? $txt : '');
      }
      $sel_name = str_replace(['[', ']'], ['-', ''], $this->name);
      $this->Ajax->addUpdate($this->name, "_{$sel_name}_sel", $selector);
      // span for select list/input field update
      $selector = "<span id='_{$sel_name}_sel' class='combodiv'>" . $selector . "</span>\n";
      // if selectable or editable list is used - add select button
      if ($select_submit != false || $search_button) {
        $selector .= "<input $disabled type='submit' class='combo_select' style='display:none;' name='_" . $this->name . "_update' value=' ' title='Select'> ";
        //button class selects form reload/ajax selector update
      }
      // ------ make combo ----------
      $edit_entry = '';
      if ($search_box) {
        $edit_entry = "<input $disabled type='text' name='$search_box' id='$search_box' size='" . $this->size . "' maxlength='" . $this->max . "' value='$txt' class='$class' rel='$this->name' autocomplete='off' title='" . $this->box_hint . "'" . (!$by_id ?
          " style=display:none;" : '') . ">\n";
        if ($search_submit != false || $this->editable) {
          $edit_entry .= "<input $disabled type='submit' class='combo_submit' style='display:none;' name='" . ($search_submit ? $search_submit : "_{$this->name}_button") . "'
          value=' ' title='" . _("Set filter") . "'> ";
        }
        $this->JS->defaultFocus(($search_box && $by_id) ? $search_box : $this->name);
      }
      if ($search_box && $this->cells) {
        $str = ($edit_entry ? "<td>$edit_entry</td>" : '') . "<td>$selector</td>";
      } else {
        $str = $edit_entry . $selector;
      }
      return $str;
    }
    /**
     * @param $search_box
     * @param $search_button
     * @param $txt
     */
    public function generateSQL($search_box, $search_button, $txt) {
      $limit = '';
      if ($search_box) {
        // search related sql modifications
        $this->rel = "rel='$search_box'"; // set relation to list
        if ($this->search_submit) {
          if (isset($_POST[$search_button])) {
            $this->selected_id = []; // ignore selected_id while search
            if (!$this->async) {
              $this->Ajax->activate('_page_body');
            } else {
              $this->Ajax->activate($this->name);
            }
          }
          if ($txt == '') {
            if ($this->spec_option === false && $this->selected_id == []) {
              $limit = ' LIMIT 1';
            } else {
              $this->where[] = $this->valfield . "='" . $this->Input->post($this->name, null, $this->spec_id) . "'";
            }
          } else {
            if ($txt != '*') {
              $texts = explode(" ", trim($txt));
              foreach ($texts as $text) {
                if (empty($text)) {
                  continue;
                }
                $search_fields = $this->search;
                foreach ($search_fields as $i => $s) {
                  $search_fields[$i] = $s . ' LIKE ' . static::$DB->escape("%$text%");
                }
                $this->where[] = '(' . implode($search_fields, ' OR ') . ')';
              }
            }
          }
        }
      }
      // sql completion
      if (count($this->where)) {
        $where = strpos($this->sql, 'WHERE') == false ? ' WHERE ' : ' AND ';
        $where .= '(' . implode($this->where, ' AND ') . ')';
        $group_pos = strpos($this->sql, 'GROUP BY');
        if ($group_pos) {
          $group     = substr($this->sql, $group_pos);
          $this->sql = substr($this->sql, 0, $group_pos) . $where . ' ' . $group;
        } else {
          $this->sql .= $where;
        }
      }
      if ($this->order != false) {
        if (!is_array($this->order)) {
          $this->order = array($this->order);
        }
        $this->sql .= ' ORDER BY ' . implode(',', $this->order);
      }
      $this->sql .= $limit;
    }
    /**
     * @return null|\PDOStatement
     */
    private function executeSQL() {
      return static::$DB->query($this->sql);
    }
    /**
     * @param $result
     *
     * @return \ADV\Core\DB\Query\Result|Array
     */
    private function getNext($result) {
      return static::$DB->fetch($result);
    }
  }
