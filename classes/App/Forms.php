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

  use ADV\Core\Input\Input;
  use ADV\Core\JS;
  use ADV\Core\Num;
  use Bank_Currency;
  use ADV\Core\Config;
  use ADV\Core\Arr;
  use ADV\Core\DB\DB;
  use ADV\Core\Ajax;
  use ADV\Core\Cell;
  use ADV\Core\SelectBox;

  /**
   * @param bool   $multi
   * @param string $action
   * @param string $name
   */
  class Forms
  {
    /** @var \ADV\Core\DB\DB */
    static $DB;
    static $Ajax;
    /**
     * @static
     *
     * @param bool   $multi
     * @param string $action
     * @param string $name
     */
    public static function start($multi = false, $action = '', $name = '') {
      $multi  = $multi ? "enctype='multipart/form-data'" : '';
      $name   = $name ? "id='$name' name='$name'" : '';
      $action = $action ? : $_SERVER['DOCUMENT_URI'];
      echo "<form $multi method='post' action='$action' $name>";
    }
    /**
     * @param int $breaks
     */
    public static function end($breaks = 0) {
      str_repeat('<br>', $breaks);
      $focus = e(Input::_post('_focus'));
      echo "<input type='hidden' name='_focus' value='$focus'></form>";
    }
    /**
     * Seek for _POST variable with $prefix.
     * If var is found returns variable name with prefix stripped,
     * and null or -1 otherwise.
     *
     * @param      $prefix
     * @param bool $numeric
     *
     * @return int|null|string
     */
    public static function  findPostPrefix($prefix, $numeric = true) {
      foreach ($_POST as $postkey => $postval) {
        if (strpos($postkey, $prefix) === 0) {
          $id = substr($postkey, strlen($prefix));
          return $numeric ? (int) $id : $id;
        }
      }
      return $numeric ? -1 : null;
    }
    /**
     * Helper function.
     * Returns true if selector $name is subject to update.
     *
     * @param $name
     *
     * @return bool
     */
    public static function isListUpdated($name) {
      return isset($_POST['_' . $name . '_update']) || isset($_POST['_' . $name . '_button']);
    }
    /**
     * @param      $name
     * @param null $value
     * @param bool $echo
     *
     * @return string
     */
    public static function hidden($name, $value = null, $echo = true) {
      $value = e($value !== null ? $value : Input::_post($name));
      static::$Ajax->addUpdate($name, $name, $value);
      $ret = "<input type='hidden' id='$name' name='$name' value='$value'>";
      if (!$echo) {
        return $ret;
      }
      echo $ret;
      return true;
    }
    /**
     * Universal sql combo generator
     * $sql must return selector values and selector texts in columns 0 & 1
     * Options are merged with default.
     *
     * @param            $name
     * @param            $selected_id
     * @param            $sql
     * @param            $valfield
     * @param            $namefield
     * @param array|null $options
     *
     * @return string
     */
    public static function selectBox($name, $selected_id = null, $sql, $valfield, $namefield, $options = []) {
      $box = new SelectBox ($name, $selected_id, $sql, $valfield, $namefield, $options);
      return $box->create();
    }
    /**
     * Universal array combo generator
     * $items is array of options 'value' => 'description'
     * Options is reduced set of combo_selector options and is merged with defaults.
     *
     * @param            $name
     * @param            $selected_id
     * @param            $items
     * @param array|null $options
     *
     * @return string
     */
    public static function arraySelect($name, $selected_id = null, $items, $options = []) {
      $opts = array( // default options
        'spec_option'   => false, // option text or false
        'spec_id'       => 0, // option id
        'select_submit' => false, //submit on select: true/false
        'async'         => true, // select update via ajax (true) vs _page_body reload
        'default'       => '', // default value when $_POST is not set
        'multi'         => false, // multiple select
        // search box parameters
        'height'        => false, // number of lines in select box
        'sel_hint'      => null, //
        'class'         => '', //
        'disabled'      => false
      );
      // ------ merge options with defaults ----------
      $opts          = array_merge($opts, $options);
      $select_submit = $opts['select_submit'];
      $spec_id       = $opts['spec_id'];
      $spec_option   = $opts['spec_option'];
      $disabled      = $opts['disabled'] ? "disabled" : '';
      $multi         = $opts['multi'];
      if ($selected_id === null) {
        $selected_id = Input::_post($name, null, $opts['default']);
      }
      if (!is_array($selected_id)) {
        $selected_id = array($selected_id);
      } // code is generalized for multiple selection support
      if (isset($_POST['_' . $name . '_update'])) {
        if (!$opts['async']) {
          static::$Ajax->activate('_page_body');
        } else {
          static::$Ajax->activate($name);
        }
      }
      // ------ make selector ----------
      $selector = $first_opt = '';
      $first_id = false;
      $found    = false;
      //if($name=='SelectStockFromList') Event::error($sql);
      foreach ($items as $value => $descr) {
        $sel = '';
        if (in_array((string) $value, $selected_id)) {
          $sel   = 'selected';
          $found = $value;
        }
        if ($first_id === false) {
          $first_id = $value;
          //$first_opt = $descr;
        }
        $selector .= "<option $sel value='$value'>$descr</option>\n";
      }
      // Prepend special option.
      if ($spec_option !== false) { // if special option used - add it
        $first_id = $spec_id;
        //$first_opt = $spec_option;
        $sel      = $found === false ? 'selected' : '';
        $selector = "<option $sel value='$spec_id'>$spec_option</option>\n" . $selector;
      }
      if ($found === false) {
        $selected_id = array($first_id);
      }
      $_POST[$name] = $multi ? $selected_id : $selected_id[0];
      $selector     = "<select " . ($multi ? "multiple" : '') . ($opts['height'] !== false ? ' size="' . $opts['height'] . '"' :
          '') . "$disabled id='$name' name='$name" . ($multi ? '[]' : '') . "' class='" . $opts['class'] . " combo' title='" . $opts['sel_hint'] . "'>" . $selector . "</select>\n";
      $sel_name     = str_replace(['[', ']'], ['-', ''], $name);
      static::$Ajax->addUpdate($name, "_{$sel_name}_sel", $selector);
      $selector = "<span id='_{$sel_name}_sel' class='combodiv'>" . $selector . "</span>\n";
      if ($select_submit != false) { // if submit on change is used - add select button
        $_select_button = "<input %s type='submit' class='combo_select' name='%s' value=' ' title='" . _("Select") . "'> ";
        $selector .= sprintf($_select_button, $disabled, '_' . $name . '_update') . "\n";
      }
      //  JS::_defaultFocus($name);
      return $selector;
    }
    // SUBMITS //
    /**
     * Universal submit form button.
     * $atype - type of submit:
     * Normal submit:
     * false - normal button; optional icon
     * Ajax submit:
     * true - standard button; optional icon
     * 'default' - default form submit on Ctrl-Enter press; dflt ICON_OK icon
     * 'selector' - ditto with closing current popup editor window
     * 'cancel' - cancel form entry on Escape press; dflt ICON_CANCEL
     * 'process' - displays progress bar during call; optional icon
     * $atype can contain also multiply type selectors separated by space,
     * however make sense only combination of 'process' and one of defualt/selector/cancel
     *
     * @param           $name
     * @param           $value
     * @param bool      $echo
     * @param bool      $title
     * @param bool|null $atype
     * @param bool      $icon
     *
     * @return string
     */
    public static function submit($name, $value, $echo = true, $title = false, $atype = false, $icon = false) {
      $aspect = '';
      if ($atype === null) {
        $aspect = " style='display:none;'";
      } elseif (!is_bool($atype)) { // necessary: switch uses '=='
        $aspect = " data-aspect='$atype' ";
        $types  = explode(' ', $atype);
        foreach ($types as $type) {
          switch ($type) {
            case 'selector':
              $aspect = " data-aspect='selector' rel='$value'";
              $value  = _("Select");
              if ($icon === false) {
                $icon = ICON_SUBMIT;
              }
              break;
            case 'default':
              $atype = true;
              if ($icon === false) {
                $icon = ICON_SUBMIT;
              }
              break;
            case 'cancel':
              if ($icon === false) {
                $icon = ICON_ESCAPE;
              }
              break;
          }
        }
      }
      $caption = ($name == FORM_ACTION) ? $title : $value;
      //$id         = ($name == FORM_ACTION) ? '' : "id=\"$name\"";
      $submit_str = "<button class=\"" . (($atype === true || $atype === false) ? (($atype) ? 'ajaxsubmit' : 'inputsubmit') :
          $atype) . "\" type=\"submit\" " . $aspect . " name=\"$name\" value=\"$value\"" . ($title ? " title='$title'" : '') . ">" . Forms::setIcon(
                                                                                                                                          $icon
        ) . "<span>$caption</span>" . "</button>\n";
      if ($echo) {
        echo $submit_str;
        return true;
      } else {
        return $submit_str;
      }
    }
    /**
     * @param      $name
     * @param      $value
     * @param bool $echo
     * @param bool $title
     * @param bool $async
     * @param bool $icon
     */
    public static function submitCenter($name, $value, $echo = true, $title = false, $async = false, $icon = false) {
      if ($echo) {
        echo "<div class='center'>";
      }
      Forms::submit($name, $value, $echo, $title, $async, $icon);
      if ($echo) {
        echo "</div>";
      }
    }
    /**
     * @param      $name
     * @param      $value
     * @param bool $title
     * @param bool $async
     * @param bool $icon
     */
    public static function submitCenterBegin($name, $value, $title = false, $async = false, $icon = false) {
      echo "<div class='center'>";
      Forms::submit($name, $value, true, $title, $async, $icon);
      echo "&nbsp;";
    }
    /**
     * @param      $name
     * @param      $value
     * @param bool $title
     * @param bool $async
     * @param bool $icon
     */
    public static function submitCenterInsert($name, $value, $title = false, $async = false, $icon = false) {
      Forms::submit($name, $value, true, $title, $async, $icon);
      echo "&nbsp;";
    }
    /**
     * @param      $name
     * @param      $value
     * @param bool $title
     * @param bool $async
     * @param bool $icon
     */
    public static function submitCenterEnd($name, $value, $title = false, $async = false, $icon = false) {
      echo "&nbsp;";
      Forms::submit($name, $value, true, $title, $async, $icon);
      echo "</div>";
    }
    /**
     * For following controls:
     * 'both' - use both Ctrl-Enter and Escape hotkeys
     * 'cancel' - apply to MODE_RESET button
     *
     * @param bool $add
     * @param bool $title
     * @param bool $async
     * @param bool $clone
     */
    public static function submitAddUpdate($add = true, $title = false, $async = false, $clone = false) {
      $cancel = $async;
      if ($async === 'both') {
        $async  = 'default';
        $cancel = 'cancel';
      } else {
        if ($async === 'default') {
          $cancel = true;
        } else {
          if ($async === 'cancel') {
            $async = true;
          }
        }
      }
      if ($add) {
        Forms::submit(ADD_ITEM, _("Add new"), true, $title, $async);
      } else {
        Forms::submit(MODE_RESET, _("Cancel"), true, _('Cancel edition'), $cancel);
        Forms::submit(UPDATE_ITEM, _("Update"), true, _('Submit changes'), $async);
        if ($clone) {
          Forms::submit(MODE_CLONE, _("Clone"), true, _('Edit new record with current data'), $async);
        }
      }
    }
    /**
     * @param bool $add
     * @param bool $title
     * @param bool $async
     * @param bool $clone
     */
    public static function submitAddUpdateCenter($add = true, $title = false, $async = false, $clone = false) {
      echo "<div class='center'>";
      Forms::submitAddUpdate($add, $title, $async, $clone);
      echo "</div>";
    }
    /**
     * @param bool   $add
     * @param bool   $right
     * @param string $extra
     * @param bool   $title
     * @param bool   $async
     * @param bool   $clone
     */
    public static function submitAddUpdateRow($add = true, $right = true, $extra = "", $title = false, $async = false, $clone = false) {
      echo "<tr>";
      if ($right) {
        echo "<td>&nbsp;</td>\n";
      }
      echo "<td $extra>";
      Forms::submitAddUpdate($add, $title, $async, $clone);
      echo "</td></tr>\n";
    }
    /**
     * @param        $name
     * @param        $value
     * @param bool   $right
     * @param string $extra
     * @param bool   $title
     * @param bool   $async
     */
    public static function submitRow($name, $value, $right = true, $extra = "", $title = false, $async = false) {
      echo "<tr>";
      if ($right) {
        echo "<td>&nbsp;</td>\n";
      }
      Forms::submitCells($name, $value, $extra, $title, $async);
      echo "</tr>\n";
    }
    /**
     * @param      $name
     * @param      $value
     * @param bool $title
     */
    public static function submitReturn($name, $value, $title = false) {
      if (Input::_request('frame')) {
        Forms::submit($name, $value, true, $title, 'selector');
      }
    }
    /**
     * @param $name
     * @param $action
     * @param $msg
     */
    public static function submitConfirm($name, $action, $msg = null) {
      if (!$msg) {
        $msg = $action;
      } else {
        $name = $action;
      }
      JS::_beforeload("_validate.$name=function(){ return confirm('" . strtr($msg, array("\n" => '\\n')) . "');};");
    }
    /**
     * @param      $icon
     * @param bool $title
     *
     * @return string
     */
    public static function setIcon($icon, $title = false) {
      //$title = ($title) ? "title='$title'" : '';
      return "<i class='" . $icon . "' > </i> ";
    }
    /**
     * @param        $name
     * @param        $value
     * @param bool   $title
     * @param bool   $icon
     * @param string $aspect
     *
     * @return string
     */
    public static function button($name, $value, $title = false, $icon = false, $aspect = '') {
      // php silently changes dots,spaces,'[' and characters 128-159
      // to underscore in POST names, to maintain compatibility with register_globals
      $rel = '';
      if ($aspect == 'selector') {
        $rel   = " rel='$value'";
        $value = _("Select");
      }
      $caption = ($name == FORM_ACTION) ? $title : $value;
      $name    = htmlentities(strtr($name, array('.' => '=2E', ' ' => '=20', '=' => '=3D', '[' => '=5B')));
      if (User::_graphic_links() && $icon) {
        if ($value == _("Delete")) // Helper during implementation
        {
          $icon = ICON_DELETE;
        }
        return "<button type='submit' class='editbutton' id='" . $name . "' name='" . $name . "' value='1'" . ($title ? " title='$title'" : " title='$value'") . ($aspect ?
          " data-aspect='$aspect'" : '') . $rel . " />" . Forms::setIcon($icon) . "</button>\n";
      } else {
        return "<button type='submit' class='editbutton' id='" . $name . "' name='" . $name . "' value='$value'" . ($title ? " title='$title'" : '') . ($aspect ?
          " data-aspect='$aspect'" : '') . $rel . " >$caption</button>\n";
      }
    }
    /**
     * @param $name
     *
     * @return int
     */
    public static function hasPost($name) {
      if (!isset($_POST[$name])) {
        return 0;
      }
      return 1;
    }
    /**
     * @param      $label
     * @param      $name
     * @param null $value
     * @param bool $submit_on_change
     * @param bool $title
     *
     * @return string
     */
    public static function checkbox($label, $name, $value = null, $submit_on_change = false, $title = false) {
      $str = '';
      if ($label) {
        $str .= $label . " ";
      }
      if ($submit_on_change !== false) {
        if ($submit_on_change === true) {
          $submit_on_change = "JsHttpRequest.request(\"_{$name}_update\", this.form);";
        }
      }
      if ($value === null) {
        $value = Input::_post($name, null, 0);
      }
      $str .= "<input" . ($value == 1 ? ' checked' : '') . " type='checkbox' name='$name' id='$name' value='1'" . ($submit_on_change ? " onclick='$submit_on_change'" :
          '') . ($title ? " title='$title'" : '') . " >\n";
      static::$Ajax->addUpdate($name, $name, $value);
      return $str;
    }
    /**
     * @param      $label
     * @param      $name
     * @param null $value
     * @param bool $submit_on_change
     * @param bool $title
     */
    public static function check($label, $name, $value = null, $submit_on_change = false, $title = false) {
      echo Forms::checkbox($label, $name, $value, $submit_on_change, $title);
    }
    /**
     * @param      $label
     * @param      $name
     * @param null $value
     * @param bool $submit_on_change
     * @param bool $title
     */
    public static function checkRow($label, $name, $value = null, $submit_on_change = false, $title = false) {
      echo "<tr><td class='label'>$label</td>";
      Forms::checkCells(null, $name, $value, $submit_on_change, $title);
      echo "</tr>\n";
    }
    /**
     * @param        $label
     * @param        $name
     * @param        $value
     * @param bool   $size
     * @param        $max
     * @param null   $title
     * @param string $params
     * @param string $post_label
     */
    public static function textRow($label, $name, $value, $size = null, $max, $title = null, $params = "", $post_label = "") {
      echo "<tr><td class='label'><label for='$name'>$label</label></td>";
      Forms::textCells(null, $name, $value, $size, $max, $title, $params, $post_label);
      echo "</tr>\n";
    }
    /**
     * @param        $label
     * @param        $name
     * @param        $size
     * @param null   $max
     * @param null   $title
     * @param null   $value
     * @param null   $rowparams
     * @param null   $post_label
     * @param string $label_cell_params
     * @param bool   $submit_on_change
     *
     * @internal param null $params
     * @internal param string $params2
     */
    public static function textRowEx(
      $label,
      $name,
      $size,
      $max = null,
      $title = null,
      $value = null,
      $rowparams = null,
      $post_label = null,
      $label_cell_params = '',
      $submit_on_change = false
    ) {
      echo "<tr {$rowparams}><td class='label' {$label_cell_params}><label for='$name'>$label</label></td>";
      Forms::textCellsEx(null, $name, $size, $max, $value, $title, $rowparams, $post_label, $submit_on_change);
      echo "</tr>\n";
    }
    /**
     * @param        $label
     * @param        $name
     * @param        $value
     * @param        $size
     * @param        $max
     * @param null   $title
     * @param string $params
     * @param string $post_label
     */
    public static function emailRow($label, $name, $value, $size, $max, $title = null, $params = "", $post_label = "") {
      if (Input::_post($name)) {
        $label = "<a href='Mailto:" . $_POST[$name] . "'>$label</a>";
      }
      Forms::textRow($label, $name, $value, $size, $max, $title, $params, $post_label);
    }
    /**
     * @param      $label
     * @param      $name
     * @param      $size
     * @param null $max
     * @param null $title
     * @param null $value
     * @param null $params
     * @param null $post_label
     */
    public static function emailRowEx($label, $name, $size, $max = null, $title = null, $value = null, $params = null, $post_label = null) {
      if (Input::_post($name)) {
        $label = "<a href='Mailto:" . $_POST[$name] . "'>$label</a>";
      }
      Forms::textRowEx($label, $name, $size, $max, $title, $value, $params, $post_label);
    }
    /**
     * @param        $label
     * @param        $name
     * @param        $value
     * @param        $size
     * @param        $max
     * @param null   $title
     * @param string $params
     * @param string $post_label
     */
    public static function linkRow($label, $name, $value, $size, $max, $title = null, $params = "", $post_label = "") {
      $val = Input::_post($name);
      if ($val) {
        if (strpos($val, 'http://') === false) {
          $val = 'http://' . $val;
        }
        $label = "<a href='$val' target='_blank'>$label</a>";
      }
      Forms::textRow($label, $name, $value, $size, $max, $title, $params, $post_label);
    }
    /**
     * @param      $label
     * @param      $name
     * @param      $size
     * @param null $max
     * @param null $title
     * @param null $value
     * @param null $params
     * @param null $post_label
     */
    public static function linkRowEx($label, $name, $size, $max = null, $title = null, $value = null, $params = null, $post_label = null) {
      $val = Input::_post($name);
      if ($val) {
        if (strpos($val, 'http://') === false) {
          $val = 'http://' . $val;
        }
        $label = "<a href='$val' target='_blank'>$label</a>";
      }
      Forms::textRowEx($label, $name, $size, $max, $title, $value, $params, $post_label);
    }
    /**
     * @param      $label
     * @param      $name
     * @param null $title
     * @param null $check
     * @param int  $inc_days
     * @param int  $inc_months
     * @param int  $inc_years
     * @param null $params
     * @param bool $submit_on_change
     */
    public static function dateRow($label, $name, $title = null, $check = null, $inc_days = 0, $inc_months = 0, $inc_years = 0, $params = null, $submit_on_change = false) {
      echo "<tr><td class='label'><label for='$name'> $label</label></td>";
      Forms::dateCells(null, $name, $title, $check, $inc_days, $inc_months, $inc_years, $params, $submit_on_change);
      echo "</tr>\n";
    }
    /**
     * @param $label
     * @param $name
     * @param $value
     */
    public static function passwordRow($label, $name, $value) {
      echo "<tr><td class='label'><label for='$name'>$label</label></td>";
      Cell::label("<input type='password' class='med' name='$name' id='$name' value='$value' />");
      echo "</tr>\n";
    }
    /**
     * @param        $label
     * @param        $name
     * @param string $id
     */
    public static function fileRow($label, $name, $id = "") {
      echo "<tr><td class='label'>$label</td>";
      Forms::fileCells(null, $name, $id);
      echo "</tr>\n";
    }
    /**
     * @param      $label
     * @param      $name
     * @param null $title
     * @param null $init
     * @param bool $submit_on_change
     */
    public static function refRow($label, $name, $title = null, $init = null, $submit_on_change = false) {
      echo "<tr><td class='label'><label for='$name'> $label</label></td>";
      Forms::refCells(null, $name, $title, $init, null, $submit_on_change);
      echo "</tr>\n";
    }
    /**
     * @param        $label
     * @param        $name
     * @param null   $init
     * @param string $cellparams
     * @param string $inputparams
     */
    public static function percentRow($label, $name, $init = null, $cellparams = '', $inputparams = '') {
      if (!isset($_POST[$name]) || $_POST[$name] == "") {
        $_POST[$name] = ($init === null) ? '' : $init;
      }
      Forms::SmallAmountRow($label, $name, $_POST[$name], null, "%", User::_percent_dec(), 0, $inputparams);
    }
    /**
     * @param        $label
     * @param        $name
     * @param null   $init
     * @param null   $params
     * @param null   $post_label
     * @param null   $dec
     * @param string $inputparams
     */
    public static function AmountRow($label, $name, $init = null, $params = null, $post_label = null, $dec = null, $inputparams = '') {
      echo "<tr>";
      Forms::amountCells($label, $name, $init, $params, $post_label, $dec, $inputparams);
      echo "</tr>\n";
    }
    /**
     * @param        $label
     * @param        $name
     * @param null   $init
     * @param null   $params
     * @param null   $post_label
     * @param null   $dec
     * @param int    $leftfill
     * @param string $inputparams
     */
    public static function SmallAmountRow($label, $name, $init = null, $params = null, $post_label = null, $dec = null, $leftfill = 0, $inputparams = '') {
      echo "<tr>";
      Forms::amountCellsSmall($label, $name, $init, $params, $post_label, $dec, $inputparams);
      if ($leftfill != 0) {
        echo "<td colspan=$leftfill></td>";
      }
      echo "</tr>\n";
    }
    /**
     * @param      $label
     * @param      $name
     * @param null $init
     * @param null $params
     * @param null $post_label
     * @param null $dec
     */
    public static function qtyRow($label, $name, $init = null, $params = null, $post_label = null, $dec = null) {
      if (!isset($dec)) {
        $dec = User::_qty_dec();
      }
      echo "<tr>";
      Forms::amountCells($label, $name, $init, $params, $post_label, $dec);
      echo "</tr>\n";
    }
    /**
     * @param      $label
     * @param      $name
     * @param null $init
     * @param null $params
     * @param null $post_label
     * @param null $dec
     */
    public static function qtyRowSmall($label, $name, $init = null, $params = null, $post_label = null, $dec = null) {
      if (!isset($dec)) {
        $dec = User::_qty_dec();
      }
      echo "<tr>";
      Forms::amountCellsSmall($label, $name, $init, $params, $post_label, $dec, null, true);
      echo "</tr>\n";
    }
    /**
     * @param        $label
     * @param        $name
     * @param        $value
     * @param        $cols
     * @param        $rows
     * @param null   $title
     * @param string $params
     * @param string $labelparams
     */
    public static function textareaRow($label, $name, $value, $cols, $rows, $title = null, $params = "", $labelparams = "") {
      echo "<tr><td class='label' $labelparams><label for='$name'>$label</label></td>";
      Forms::textareaCells(null, $name, $value, $cols, $rows, $title, $params);
      echo "</tr>\n";
    }
    /**
     * Displays controls for optional display of inactive records
     *
     * @param $th
     */
    public static function inactiveControlRow($th) {
      echo "<tr><td colspan=" . (count($th)) . ">" . "<div style='float:left;'>" . Forms::checkbox(null, 'show_inactive', null, true) . _(
          "Show also Inactive"
        ) . "</div><div style='float:right;'>" . Forms::submit('Update', _('Update'), false, '', null) . "</div></td></tr>";
    }
    /**
     * Inserts additional column header when display of inactive records is on.
     *
     * @param $th
     */
    public static function inactiveControlCol(&$th) {
      if (Input::_hasPost('show_inactive')) {
        Arr::insert($th, count($th) - 2, _("Inactive"));
      }
      if (Input::_post('_show_inactive_update')) {
        Ajax::_activate('_page_body');
      }
    }
    /**
     * @param        $name
     * @param null   $selected_id
     * @param string $name_yes
     * @param string $name_no
     * @param bool   $submit_on_change
     *
     * @return string
     */
    public static function yesnoList($name, $selected_id = null, $name_yes = "", $name_no = "", $submit_on_change = false) {
      $items      = [];
      $items['0'] = strlen($name_no) ? $name_no : _("No");
      $items['1'] = strlen($name_yes) ? $name_yes : _("Yes");
      return Forms::arraySelect(
                  $name, $selected_id, $items, array(
                                                    'select_submit' => $submit_on_change,
                                                    'async'         => false
                                               )
      );
    }
    /**
     * @param        $label
     * @param        $name
     * @param null   $selected_id
     * @param string $name_yes
     * @param string $name_no
     * @param bool   $submit_on_change
     */
    public static function yesnoListRow($label, $name, $selected_id = null, $name_yes = "", $name_no = "", $submit_on_change = false) {
      echo "<tr><td class='label'>$label</td>";
      Forms::yesnoListCells(null, $name, $selected_id, $name_yes, $name_no, $submit_on_change);
      echo "</tr>\n";
    }
    /**
     * @param $label
     * @param $name
     */
    public static function recordStatusListRow($label, $name) {
      Forms::yesnoListRow($label, $name, null, _('Inactive'), _('Active'));
    }
    /**
     * @param      $name
     * @param      $selected
     * @param      $from
     * @param      $to
     * @param bool $no_option
     *
     * @return string
     */
    public static function numberList($name, $selected, $from, $to, $no_option = false) {
      $items = [];
      for ($i = $from; $i <= $to; $i++) {
        $items[$i] = "$i";
      }
      return Forms::arraySelect(
                  $name, $selected, $items, array(
                                                 'spec_option' => $no_option,
                                                 'spec_id'     => ALL_NUMERIC
                                            )
      );
    }
    /**
     * @param      $label
     * @param      $name
     * @param      $selected
     * @param      $from
     * @param      $to
     * @param bool $no_option
     */
    public static function numberListRow($label, $name, $selected, $from, $to, $no_option = false) {
      echo "<tr><td class='label'>$label</td>";
      Forms::numberListCells(null, $name, $selected, $from, $to, $no_option);
      echo "</tr>\n";
    }
    /**
     * @param      $label
     * @param      $name
     * @param null $value
     */
    public static function dateFormatsListRow($label, $name, $value = null) {
      echo "<tr><td class='label'>$label</td>\n<td>";
      echo Forms::arraySelect($name, $value, Config::_get('date.formats'));
      echo "</td></tr>\n";
    }
    /**
     * @param      $label
     * @param      $name
     * @param null $value
     */
    public static function dateSepsListRow($label, $name, $value = null) {
      echo "<tr><td class='label'>$label</td>\n<td>";
      echo Forms::arraySelect($name, array_search($value, Config::_get('date.separators')), Config::_get('date.separators'));
      echo "</td></tr>\n";
    }
    /**
     * @param      $label
     * @param      $name
     * @param null $value
     */
    public static function thoSepsListRow($label, $name, $value = null) {
      echo "<tr><td class='label'>$label</td>\n<td>";
      echo Forms::arraySelect($name, array_search($value, Config::_get('separators_thousands')), Config::_get('separators_thousands'));
      echo "</td></tr>\n";
    }
    /**
     * @param      $label
     * @param      $name
     * @param null $value
     */
    public static function decSepsListRow($label, $name, $value = null) {
      echo "<tr><td class='label'>$label</td>\n<td>";
      echo Forms::arraySelect($name, array_search($value, Config::_get('separators_decimal')), Config::_get('separators_decimal'));
      echo "</td></tr>\n";
    }
    /**
     * @param $row
     *
     * @return string
     */
    public static function dateFormat($row) {
      return Dates::_sqlToDate($row['reconciled']);
    }
    /**
     * @param $row
     *
     * @return string
     */
    public static function addCurrFormat($row) {
      static $company_currency;
      if ($company_currency == null) {
        $company_currency = Bank_Currency::for_company();
      }
      return $row[1] . ($row[2] == $company_currency ? '' : ("&nbsp;-&nbsp;" . $row[2]));
    }
    /**
     * @param $row
     *
     * @return string
     */
    public static function stockItemsFormat($row) {
      return (User::_show_codes() ? ($row[0] . "&nbsp;-&nbsp;") : "") . $row[1];
    }
    /**
     * @param $row
     *
     * @return string
     */
    public static function templateItemsFormat($row) {
      return ($row[0] . "&nbsp;- &nbsp;" . _("Amount") . "&nbsp;" . $row[1]);
    }
    /**
     * @param $row
     *
     * @return string
     */
    public static function fiscalYearFormat($row) {
      return Dates::_sqlToDate($row[1]) . "&nbsp;-&nbsp;" . Dates::_sqlToDate($row[2]) . "&nbsp;&nbsp;" . ($row[3] ? _('Closed') : _('Active')) . "</option>\n";
    }
    /**
     * @param $row
     *
     * @return string
     */
    public static function accountFormat($row) {
      return $row[0] . "&nbsp;&nbsp;&nbsp;&nbsp;" . $row[1];
    }
    /**
     * Prep Value
     * Prepares the value for display in the form
     *
     * @param string
     *
     * @return string
     */
    public static function prep_value($value) {
      $value = Security::htmlentities($value);
      $value = str_replace(array("'", '"'), array("&#39;", "&quot;"), $value);
      return $value;
    }
    /**
     * @param        $label
     * @param        $name
     * @param null   $value
     * @param bool   $submit_on_change
     * @param bool   $title
     * @param string $params
     */
    public static function checkCells($label, $name, $value = null, $submit_on_change = false, $title = false, $params = '') {
      echo "<td $params>";
      if ($label != null) {
        echo "<label for=\"$name\"> $label</label>";
      }
      Forms::check(null, $name, $value, $submit_on_change, $title);
      echo "</td>";
    }
    /**
     * When show_inactive page option is set
     * displays value of inactive field as checkbox cell.
     * Also updates database record after status change.
     *
     * @param $id
     * @param $value
     * @param $table
     * @param $key
     */
    public static function inactiveControlCell($id, $value, $table, $key) {
      $name  = "Inactive" . $id;
      $value = $value ? 1 : 0;
      if (Input::_hasPost('show_inactive')) {
        if (isset($_POST['LInact'][$id]) && (Input::_post('_Inactive' . $id . '_update') || Input::_post('Update')) && (Input::_hasPost('Inactive' . $id) != $value)
        ) {
          DB::_updateRecordStatus($id, !$value, $table, $key);
        }
        echo "<td class='center'>";
        echo Forms::checkbox(null, $name, $value, true, '', "class='center'") . Forms::hidden("LInact[$id]", $value, false);
        echo '</td>';
      }
    }
    /**
     * @param        $name
     * @param        $value
     * @param bool   $title
     * @param bool   $icon
     * @param string $aspect
     */
    public static function buttonCell($name, $value, $title = false, $icon = false, $aspect = '') {
      echo "<td class='center'>";
      echo Forms::button($name, $value, $title, $icon, $aspect);
      echo "</td>";
    }
    /**
     * @param      $line_no
     * @param      $value
     * @param bool $title
     *
     * @internal param $name
     */
    public static function buttonDeleteCell($line_no, $value, $title = false) {
      if (strpos($line_no, 'Delete') === 0 || strpos($line_no, 'BDel') === 0) {
        Forms::buttonCell($line_no, $value, $title, ICON_DELETE);
      } else {
        Forms::buttonCell(FORM_ACTION, Orders::DELETE_LINE . $line_no, $value, ICON_DELETE);
      }
    }
    /**
     * @param      $line_no
     * @param      $value
     * @param bool $title
     *
     * @internal param $name
     */
    public static function buttonEditCell($line_no, $value, $title = false) {
      if (strpos($line_no, 'Edit') === 0 || strpos($line_no, 'BEdit') === 0) {
        Forms::buttonCell($line_no, $value, $title, ICON_EDIT);
      } else {
        Forms::buttonCell(FORM_ACTION, Orders::EDIT_LINE . $line_no, $value, ICON_EDIT);
      }
    }
    /**
     * @param      $name
     * @param      $value
     * @param bool $title
     */
    public static function buttonSelectCell($name, $value, $title = false) {
      Forms::buttonCell($name, $value, $title, ICON_ADD, 'selector');
    }
    /**
     * @param        $label
     * @param        $name
     * @param string $id
     */
    public static function fileCells($label, $name, $id = "") {
      if ($id != "") {
        $id = "id='$id'";
      }
      Cell::labelled($label, "<input type='file' name='$name' $id />");
    }
    /**
     * Since ADV 2.2 $init parameter is superseded by $check.
     * When $check!=null current date is displayed in red when set to other
     * than current date.
     *
     * @param            $label
     * @param            $name
     * @param null       $title
     * @param null       $check
     * @param int        $inc_days
     * @param int        $inc_months
     * @param int        $inc_years
     * @param bool|null  $submit_on_change
     * @param array|bool $options
     *
     * @internal param null $params
     */
    public static function dateCells(
      $label,
      $name,
      $title = null,
      $check = null,
      $inc_days = 0,
      $inc_months = 0,
      $inc_years = 0,
      //$params = null,
      $submit_on_change = false,
      $options = []
    ) {
      if (!isset($_POST[$name]) || $_POST[$name] == "") {
        if ($inc_years == 1001) {
          $_POST[$name] = null;
        } else {
          $dd = Dates::_today();
          if ($inc_days != 0) {
            $dd = Dates::_addDays($dd, $inc_days);
          }
          if ($inc_months != 0) {
            $dd = Dates::_addMonths($dd, $inc_months);
          }
          if ($inc_years != 0) {
            $dd = Dates::_addYears($dd, $inc_years);
          }
          $_POST[$name] = $dd;
        }
      }
      $post_label = "";
      if ($label != null) {
        echo "<td class='label'><label for=\"$name\"> $label</label></td>";
      }
      echo "<td >";
      $class  = $submit_on_change ? 'searchbox datepicker' : 'datepicker';
      $aspect = $check ? ' data-aspect="cdate"' : '';
      if ($check && (Input::_post($name) != Dates::_today())) {
        $aspect .= ' style="color:#FF0000"';
      }
      echo "<input id='$name' type='text' name='$name' class='$class' $aspect maxlength='10' value=\"" . $_POST[$name] . "\"" . ($title ? " title='$title'" :
          '') . " > $post_label";
      echo "</td>\n";
      Ajax::_addUpdate($name, $name, $_POST[$name]);
    }
    /**
     * @param        $label
     * @param        $name
     * @param null   $value
     * @param string $size
     * @param string $max
     * @param bool   $title
     * @param string $labparams
     * @param string $post_label
     * @param string $inparams
     */
    public static function textCells($label, $name, $value = null, $size = "", $max = "", $title = false, $labparams = "", $post_label = "", $inparams = "") {
      $placeholder = '';
      if ($label != null) {
        echo "<td class='label'><label for=\"$name\"> $label</label></td>";
        $placeholder = " placeholder='$label'";
      }
      echo "<td >";
      if ($value === null) {
        $value = Input::_post($name);
      }
      if ($size && is_numeric($size)) {
        $size = " size='$size'";
      } elseif (is_string($size)) {
        $size = " class='$size'";
      }
      echo "<input $inparams type=\"text\" name=\"$name\" $placeholder id=\"$name\" $size maxlength=\"$max\" value=\"$value\"" . ($title ? " title='$title'" : '') . ">";
      if ($post_label != "") {
        echo " " . $post_label;
      }
      echo "</td>\n";
      Ajax::_addUpdate($name, $name, $value);
    }
    /**
     * @param        $label
     * @param        $name
     * @param        $size
     * @param null   $max
     * @param null   $init
     * @param null   $title
     * @param null   $params
     * @param null   $post_label
     * @param bool   $submit_on_change
     * @param string $inparams
     *
     * @internal param null $labparams
     */
    public static function textCellsEx(
      $label,
      $name,
      $size = null,
      $max = null,
      $init = null,
      $title = null,
      $params = null,
      $post_label = null,
      $submit_on_change = false,
      $inparams = ""
    ) {
      JS::_defaultFocus($name);
      if (!isset($_POST[$name]) || $_POST[$name] == "") {
        if ($init !== null) {
          $_POST[$name] = $init;
        } else {
          $_POST[$name] = "";
        }
      }
      if ($label != null) {
        echo "<td class='label' $params> <label for=\"$name\"> $label</label></td><td>";
      } else {
        echo "<td>";
      }
      if (!isset($max)) {
        $max = $size;
      }
      $class = '';
      if ($size && is_numeric($size)) {
        $size = " size='$size'";
      } elseif (is_string($size)) {
        $class = $size;
      }
      $class = 'class="' . $class . ($submit_on_change ? ' searchbox' : '') . '"';
      $id    = $name ? "id=\"$name\"" : '';
      $value = 'value="' . $_POST[$name] . '"';
      Ajax::_addUpdate($name, $name, $_POST[$name]);
      $name = $name ? "name=\"$name\"" : '';
      echo "<input $class type=\"text\" $name $id $inparams $size maxlength=\"$max\" $value " . ($title ? " title='$title'" : '') . " >";
      if ($post_label) {
        echo " " . $post_label;
      }
      echo "</td>\n";
    }
    /**
     * @param        $name
     * @param        $value
     * @param string $extra
     * @param bool   $title
     * @param bool   $async
     */
    public static function submitCells($name, $value, $extra = "", $title = false, $async = false) {
      echo "<td $extra>";
      Forms::submit($name, $value, true, $title, $async);
      echo "</td>\n";
    }
    /**
     * @param      $label
     * @param      $name
     * @param null $title
     * @param null $init
     * @param null $params
     * @param bool $submit_on_change
     */
    public static function refCells($label, $name, $title = null, $init = null, $params = null, $submit_on_change = false) {
      Forms::textCellsEx($label, null, 'small', 18, $init, $title, $params, Forms::hidden($name, $init, false), $submit_on_change, ' disabled');
    }
    /**
     * @param      $label
     * @param      $name
     * @param null $title
     * @param null $init
     * @param null $params
     * @param bool $submit_on_change
     */
    public static function refCellsSearch($label, $name, $title = null, $init = null, $params = null, $submit_on_change = false) {
      Forms::textCellsEx($label, $name, 'small', 18, $init, $title, $params, '', $submit_on_change, ' placeholder="Reference"');
    }
    /**
     * @param        $label
     * @param        $name
     * @param null   $init
     * @param string $inputparams
     */
    public static function percentCells($label, $name, $init = null, $inputparams = '') {
      if (!isset($_POST[$name]) || $_POST[$name] == "") {
        $_POST[$name] = ($init === null) ? 0 : $init;
      }
      Forms::amountCellsSmall($label, $name, null, null, "%", User::_percent_dec(), $inputparams);
    }
    /**
     * @param        $label
     * @param        $name
     * @param        $size
     * @param null   $max
     * @param null   $init
     * @param null   $params
     * @param null   $post_label
     * @param null   $dec
     * @param null   $id
     * @param string $inputparams
     * @param bool   $negatives
     */
    public static function amountCellsEx(
      $label,
      $name,
      $size = 10,
      $max = null,
      $init = null,
      $params = null,
      $post_label = null,
      $dec = null,
      $id = null,
      $inputparams = '',
      $negatives = false
    ) {
      if ($label) {
        $params = $params ? : " class='label'";
        Cell::label($label, $params);
        echo "<td>";
      } else {
        echo "<td class='alignright nowrap' >";
      }
      if ($init === null) {
        $init = Input::_post($name, Input::NUMERIC);
      }
      $init                   = $_POST[$name] = Num::_priceDecimal($init, $dec);
      $input_attr['name']     = $name;
      $input_attr['value']    = $init;
      $input_attr['data-dec'] = $dec;
      $input_attr['class']    = ($name == 'freight') ? 'freight ' : 'amount ';
      if ($size && is_numeric($size)) {
        $input_attr['size'] = $size;
      } elseif (is_string($size)) {
        $input_attr['class'] .= $size;
      }
      $input_attr['maxlength'] = $max ? : $size;
      $input_attr['id']        = $id ? : $name;
      $input_attr['type']      = 'text';
      foreach ($input_attr as $k => $v) {
        if ($v === null) {
          continue;
        }
        $inputparams .= " $k='$v'";
      }
      $pre_label = '';
      if (is_array($post_label)) {
        $pre_label  = $post_label[0];
        $post_label = null;
      }
      if ($post_label) {
        echo "<div class='input-append'>";
      } elseif ($pre_label) {
        echo "<div class='input-prepend'>";
      }
      if ($pre_label) {
        echo "<span class='add-on' id='_{$name}_label'>$pre_label</span><input $inputparams></div>";
      } elseif ($post_label) {
        echo "<input $inputparams><span class='add-on' id='_{$name}_label'>$post_label</span></div>";
        static::$Ajax->addUpdate($name, '_' . $name . '_label', $post_label);
      } else {
        echo "<input $inputparams>";
      }
      echo "</td>\n";
      static::$Ajax->addUpdate($name, $name, $init);
      static::$Ajax->addAssign($name, $name, 'data-dec', $dec);
    }
    /**
     * @param        $label
     * @param        $name
     * @param null   $init
     * @param null   $params
     * @param null   $post_label
     * @param null   $dec
     * @param null   $id
     * @param string $inputparams
     */
    public static function amountCells($label, $name, $init = null, $params = null, $post_label = null, $dec = null, $id = null, $inputparams = '') {
      Forms::amountCellsEx($label, $name, null, 15, $init, $params, $post_label, $dec, $id, $inputparams);
    }
    /**
     * JAM Allow entered unit prices to be fractional
     *
     * @param      $label
     * @param      $name
     * @param null $init
     * @param null $params
     * @param null $post_label
     * @param null $dec
     */
    public static function unitAmountCells($label, $name, $init = null, $params = null, $post_label = null, $dec = null) {
      if (!isset($dec)) {
        $dec = User::_price_dec() + 2;
      }
      Forms::amountCellsEx($label, $name, null, 15, $init, $params, $post_label, $dec + 2);
    }
    /**
     * @param        $label
     * @param        $name
     * @param null   $init
     * @param null   $params
     * @param null   $post_label
     * @param null   $dec
     * @param string $inputparams
     * @param bool   $negatives
     */
    public static function amountCellsSmall($label, $name, $init = null, $params = null, $post_label = null, $dec = null, $inputparams = '', $negatives = false) {
      Forms::amountCellsEx($label, $name, 'small', 12, $init, $params, $post_label, $dec, null, $inputparams, $negatives);
    }
    /**
     * @param      $label
     * @param      $name
     * @param null $init
     * @param null $params
     * @param null $post_label
     * @param null $dec
     */
    public static function qtyCellsSmall($label, $name, $init = null, $params = null, $post_label = null, $dec = null) {
      if (!isset($dec)) {
        $dec = User::_qty_dec();
      }
      Forms::amountCellsEx($label, $name, 'small', 12, $init, $params, $post_label, $dec, null, null, true);
    }
    /**
     * @param      $label
     * @param      $name
     * @param      $selected
     * @param      $from
     * @param      $to
     * @param bool $no_option
     */
    public static function numberListCells($label, $name, $selected, $from, $to, $no_option = false) {
      if ($label != null) {
        Cell::label($label);
      }
      echo "<td>\n";
      echo Forms::numberList($name, $selected, $from, $to, $no_option);
      echo "</td>\n";
    }
    /**
     * @param        $label
     * @param        $name
     * @param null   $selected_id
     * @param string $name_yes
     * @param string $name_no
     * @param bool   $submit_on_change
     */
    public static function yesnoListCells($label, $name, $selected_id = null, $name_yes = "", $name_no = "", $submit_on_change = false) {
      if ($label != null) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo Forms::yesnoList($name, $selected_id, $name_yes, $name_no, $submit_on_change);
      echo "</td>\n";
    }
    /**
     * @param        $label
     * @param        $name
     * @param        $value
     * @param        $cols
     * @param        $rows
     * @param null   $title
     * @param string $params
     */
    public static function textareaCells($label, $name, $value, $cols, $rows, $title = null, $params = "") {
      if ($label != null) {
        echo "<td $params>$label</td>\n";
        $params = '';
      }
      if ($value === null) {
        $value = (!isset($_POST[$name]) ? "" : $_POST[$name]);
      }
      if ($cols && is_numeric($cols)) {
        $cols = "cols='" . ($cols + 2) . "'";
      } elseif (is_string($cols)) {
        $cols = "class='$cols'";
      }
      echo "<td $params><textarea id='$name' name='$name' $cols rows='$rows'" . ($title ? " title='$title'" : '') . ">$value</textarea></td>\n";
      static::$Ajax->addUpdate($name, $name, $value);
    }
    /**
     * @param        $label
     * @param        $name
     * @param null   $init
     * @param null   $params
     * @param null   $post_label
     * @param null   $dec
     * @param string $inputparams
     */
    public static function qtyCells($label, $name, $init = null, $params = null, $post_label = null, $dec = null, $inputparams = '') {
      if ($dec === null) {
        $dec = User::_qty_dec();
      }
      if ($dec === false) {
        $dec = null;
      }
      Forms::amountCellsEx($label, $name, null, 15, $init, $params, $post_label, $dec, null, $inputparams = '', true);
    }
  }

  Forms::$Ajax = Ajax::i();
  Forms::$DB   = \ADV\Core\DB\DB::i();
