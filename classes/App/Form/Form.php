<?php

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\Form;

  use \ADV\App\User;
  use \ADV\Core\Ajax;
  use \ADV\Core\Num;
  use \ADV\Core\SelectBox;
  use \ADV\Core\HTML;
  use \ADV\Core\Input\Input;

  /**
   * @param bool   $multi
   * @param string $action
   * @param string $name
   */
  class Form implements \ArrayAccess, \RecursiveIterator, \JsonSerializable, \Countable
  {
    const NO_VALUES = 1;
    public $useDefaults = false;
    /** @var Ajax */
    protected $Ajax;
    /** @var Input */
    protected $Input;
    /** @var Field[] */
    protected $fields = [];
    protected $groups = [];
    protected $start;
    protected $end;
    protected $validators = [];
    protected $uniqueid;
    protected $current;
    protected $currentgroup;
    protected $nest;
    protected $name;

    /**
     * @param \ADV\Core\Input\Input $input
     * @param \ADV\Core\Ajax        $ajax
     * @param \ADV\Core\Session     $session
     */
    public function __construct(\ADV\Core\Input\Input $input = null, \ADV\Core\Ajax $ajax = null, \ADV\Core\Session $session = null) {
      $this->Ajax  = $ajax ? : Ajax::i();
      $this->Input = $input ? : Input::i();
      $this->group();
    }

    /**
     * @param Field $field
     *
     * @internal param $tag
     * @internal param $name
     * @internal param $value
     * @return Field
     */
    protected function addField(Field $field) {
      $name = $field['name'];
      if (is_array($this->currentgroup)) {
        $this->currentgroup[] = $field;
      }
      if (!$name) {
        return $field;
      }
      if ($this->Input->hasPost($name)) {
        $field->value($this->Input->post($name));
      }
      $this->fields[$field->id]     = $field;
      $this->validators[$field->id] =& $field->validator;
      $this->Ajax->addUpdate($name, $name, $field->value);
      return $field;
    }

    /**
     * @param $name
     *
     * @return \ADV\App\Form\Form
     */
    public function group($name = '_default') {
      if (!isset($this->groups[$name])) {
        $this->groups[$name] = [];
      }
      $this->currentgroup = & $this->groups[$name];
      return $this;
    }

    /**
     * @static
     *
     * @param string $name
     * @param string $action
     * @param bool   $multi
     * @param array  $attrs
     *
     * @internal param array $input_attr
     * @return \ADV\Core\HTML|string
     */
    public function start($name = '', $action = '', $multi = false, Array $attrs = []) {
      $name            = ($name && !$this->uniqueid) ? $this->name($name) : null;
      $attr['enctype'] = $multi ? 'multipart/form-data' : null;
      $attr['method']  = 'post';
      $attr['action']  = $action;
      $attr['name']    = $name;
      $attr            = array_merge($attr, $attrs);
      $this->start     = (new HTML)->form($name, $attr)->input(
                                   null, [
                                         'type'  => 'hidden',
                                         'value' => $name,
                                         'name'  => FORM_ID
                                         ]
      );
      return $this->start;
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function name($name) {
      $this->uniqueid = $this->nameToId($name . '_form');
      return $this->uniqueid;
    }

    /**
     * @return \ADV\Core\HTML|string
    @internal param int $breaks
     */
    public function end() {
      $this->end = "</form>";
      return $this->end;
    }

    /**
     * @param $heading
     *
     * @return Field
     */
    public function heading($heading) {
      $field = $this->addField(new Field('div', null));
      $field->value(null);
      $field->setContent($heading);
      return $field;
    }

    /**
     * @param $name
     *
     * @internal param null $value
     * @internal param bool $echo
     * @return Field
     */
    public function hidden($name) {
      $field         = $this->addField(new Field('input', $name));
      $field['type'] = 'hidden';
      return $field;
    }

    /**
     * @param       $name
     * @param array $attrs
     *
     * @internal param array $input_attr
     * @internal param null $value
     * @return \ADV\App\Form\Field
     */
    public function text($name, Array $attrs = []) {
      $field         = $this->addField(new Field('input', $name));
      $field['type'] = 'text';
      return $field->mergeAttr($attrs);
    }

    /**
     * @param       $name
     * @param array $attrs
     *
     * @internal param array $input_attr
     * @internal param null $value
     * @return \ADV\App\Form\Field
     */
    public function password($name, Array $attrs = []) {
      $field                = $this->addField(new Field('input', $name));
      $field->value('');
      $field['type']        = 'password';
      $field['placeholder'] = '';
      return $field->mergeAttr($attrs);
    }

    /**
     * @param       $name
     * @param array $attrs
     *
     * @internal param array $input_attr
     * @internal param $value
     * @return \ADV\App\Form\Field
     */
    public function textarea($name, Array $attrs = []) {
      $field = $this->addField(new Field('textarea', $name));
      return $field->mergeAttr($attrs);
    }

    /**
     * @param       $name
     * @param array $attrs
     *
     * @internal param array $input_attr
     * @internal param $value
     * @return Field
     */
    public function date($name, Array $attrs = []) {
      $field              = $this->addField(new Field('input', $name));
      $field['type']      = 'text';
      $field['maxlength'] = 10;
      $field['class']     = 'datepicker';
      return $field->mergeAttr($attrs);
    }

    /**
     * @param       $name
     * @param array $attrs
     *
     * @internal param array $input_attr
     * @internal param bool $value
     * @return Field
     */
    public function checkbox($name, Array $attrs = []) {
      $field = $this->addField(new Checkbox($name));
      return $field->mergeAttr($attrs);
    }

    /**
     * @param       $name
     * @param array $attrs
     *
     * @internal param array $inputparams
     * @internal param null $value
     * @return Field
     */
    public function percent($name, Array $attrs = []) {
      $attrs = array_merge(['class' => 'amount'], $attrs);
      return $this->number($name, User::_percent_dec(), $attrs)->append('%');
    }

    /**
     * @param       $name
     * @param int   $dec
     * @param array $attrs
     *
     * @internal param array $input_attr
     * @internal param null $value
     * @return \ADV\App\Form\Field
     */
    public function number($name, $dec = 0, Array $attrs = []) {
      $field             = $this->addField(new Field('input', $name));
      $field['data-dec'] = (int) $dec;
      $field['type']     = 'text';
      $this->Ajax->addAssign($name, $name, 'data-dec', $dec);
      $field->mergeAttr($attrs);
      $field['value'] = Num::_format($field['value'] ? : 0, $field['data-dec']);
      return $field;
    }

    /**
     * @param       $name
     * @param array $attrs
     *
     * @internal param array $input_attr
     * @internal param null $value
     * @internal param array $inputparams
     * @return Field
     */
    public function amount($name, Array $attrs = []) {
      $attrs = array_merge(['class' => 'amount'], $attrs);
      return $this->number($name, User::_price_dec(), $attrs)->prepend('$');
    }

    /**
     * @param $control
     *
     * @return \ADV\App\Form\Field
     */
    public function custom($control) {
      return $this->addField(new Custom($control));
    }

    /**
     * Universal sql combo generator
     * $sql must return selector values and selector texts in columns 0 & 1
     * Options are merged with default.
     *
     * @param       $name
     * @param       $selected_id
     * @param       $sql
     * @param       $valfield
     * @param       $namefield
     * @param array $options
     *
     * @return string
     */
    public function selectBox($name, $selected_id = null, $sql, $valfield, $namefield, $options = null) {
      $box = new SelectBox($name, $selected_id, $sql, $valfield, $namefield, $options);
      return $box->create();
    }

    /**
     * Universal array combo generator
     * $items is array of options 'value' => 'description'
     * Options is reduced set of combo_selector options and is merged with defaults.
     *
     * @param                 $name
     * @param                 $selected_id
     * @param   array         $items   Associative array [ value=>label ]
     * @param array|null      $options [  spec_option   => false, // option text or false<br>
     *                                 spec_id       => 0, // option id<br>
     *                                 select_submit => false, //submit on select: true/false<br>
     *                                 async         => true, // select update via ajax (true) vs _page_body reload<br>
     *                                 default       => null, // default value when $_POST is not set<br>
     *                                 multi         => false, // multiple select<br>
     *                                 sel_hint => null,<br>
     *                                 disabled => null,<br>
     *                                 ]
     *
     * @return Field
     */
    public function arraySelect($name, $items, $selected_id = null, Array $options = []) {
      $field = $this->addField(new Select($name, $items, $options));
      $field->initial($selected_id);
      if ($this->Input->post(FORM_ACTION) == CHANGED && $this->Input->post(FORM_CONTROL) == $name) {
        if ($this->uniqueid == $this->Input->post(FORM_ID)) {
          $field->async ? $this->Ajax->activate($name) : $this->Ajax->activate('_page_body');
        }
      }
      $this->Ajax->addUpdate($name, "_" . $name . "_sel", (string) $field);
      return $field;
    }

    /**
     * @param             $name
     * @param string|null $value
     * @param             $caption
     * @param array       $attrs
     *
     * @inte  rnal param array $input_attr Input attributes
     * @return Button
     */
    public function button($name, $value, $caption, $attrs = []) {
      $button = new Button($name, $value, $caption);
      if (is_array($this->currentgroup)) {
        $this->currentgroup[] = $button;
      }
      $this->fields[$button->id] = $button;
      return $button->mergeAttr($attrs);
    }

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
     * @param             $action
     * @param bool|string $caption
     * @param array       $attrs
     *
     * @internal param array $input_attr
     * @return \ADV\App\Form\Button
     */
    public function submit($action, $caption = null, $attrs = []) {
      if (is_array($caption)) {
        $attrs   = $caption;
        $caption = null;
      }
      if ($caption === null) {
        $caption = $action;
      }
      $button         = new Button(FORM_ACTION, $action, $caption);
      $button['type'] = 'submit';
      $button->id     = $this->nameToId($action);
      if (is_array($this->currentgroup)) {
        $this->currentgroup[] = $button;
      }
      $this->fields[$button->id] = $button;
      return $button->mergeAttr($attrs);
    }

    /**
     * @param      $name
     * @param Form $form
     */
    public function nest($name, Form $form) {
      $form->nest           = $name;
      $this->fields[$name]  = $form;
      $this->currentgroup[] = $form;
    }

    /**
     * @param $id
     */
    public function hide($id) {
      $this->fields[$this->nameToId($id)]->hide = true;
    }

    public function getID() {
      return $this->uniqueid;
    }

    /**
     * @param      $values
     * @param null $group
     *
     * @return void
     */
    public function setValues($values, $group = null) {
      $values = (array) $values;
      $fields = $group ? $this->groups[$group] : $this->fields;
      foreach ($values as $id => $value) {
        if (array_key_exists($id, $fields)) {
          if ($fields[$id]['type'] == 'password') {
            $value = '';
          }
          $fields[$id]->value($value);
        }
      }
    }

    /**
     * @param $values
     */
    protected function value($values) {
      $this->setValues($values);
    }

    /**
     * Helper function.
     * Returns true if selector $name is subject to update.
     *
     * @param $name
     *
     * @return bool
     */
    public function isListUpdated($name) {
      return isset($_POST['_' . $name . '_update']);
    }

    /**
     * @param $valids
     */
    public function runValidators($valids) {
      foreach ($_SESSION['forms'][$this->uniqueid]->validators as $function) {
        $valids->$function();
      }
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    protected function nameToId($name) {
      return str_replace(['[', ']'], ['-', ''], $name);
    }

    /**
     * @return array
     */
    public function jsonSerialize() {
      $return    = [];
      $use       = ($this->useDefaults) ? 'default' : 'value';
      $autofocus = false;
      foreach ($this->fields as $id => $field) {
        if ($field instanceof Button) {
          continue;
        }
        if ($field instanceof Form) {
          $return[$id] = $field->jsonSerialize();
          continue;
        }
        $value = ['value' => $field->$use];
        if ($field->hide === true) {
          $value['hidden'] = true;
        } elseif (!$autofocus && $field['autofocus'] === true) {
          $value['focus'] = true;
        }
        $return[$id] = $value;
      }
      $return[FORM_ID] = $this->uniqueid;
      return $return;
    }

    /**
     * @return array
     */
    public function __sleep() {
      return ['validators'];
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset <p>
     *                      An offset to check for.
     *                      </p>
     *
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     *       The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset) {
      if ($offset == '_start' || $offset == '_end') {
        return true;
      }
      return array_key_exists($offset, $this->fields) || array_key_exists($offset, $this->groups);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset <p>
     *                      The offset to retrieve.
     *                      </p>
     *
     * @return \ADV\App\Form\Field Can return all value types.
     */
    public function offsetGet($offset) {
      if ($offset == '_start') {
        if (empty($this->start)) {
          return $this->start();
        }
        return $this->start;
      }
      if ($offset == '_end') {
        if (empty($this->end)) {
          return $this->end();
        }
        return $this->end;
      }
      if (!isset($this->fields[$offset]) && isset($this->groups[$offset])) {
        return $this->groups[$offset];
      }
      return $this->fields[$offset];
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset <p>
     *                      The offset to assign the value to.
     *                      </p>
     * @param mixed $value  <p>
     *                      The value to set.
     *                      </p>
     *
     * @return void
     */
    public function offsetSet($offset, $value) {
      $this->fields[$offset] = $value;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset <p>
     *                      The offset to unset.
     *                      </p>
     *
     * @return void
     */
    public function offsetUnset($offset) {
      unset($this->fields[$offset]);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current() {
      return current($this->groups['_default']);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next() {
      $this->current = next($this->groups['_default']);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key() {
      return key($this->groups['_default']);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     *       Returns true on success or false on failure.
     */
    public function valid() {
      return $this->current !== false;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind() {
      reset($this->groups['_default']);
    }

    /**
     * @return string
     */
    public function __tostring() {
      $return = '';
      foreach ($this as $field) {
        if ($this->nest) {
          $field->name($this->nest . '[' . $field['name'] . ']');
        }
        $return .= $field;
      }
      return $return;
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Returns if an iterator can be created for the current entry.
     * @link http://php.net/manual/en/recursiveiterator.haschildren.php
     * @return bool true if the current entry can be iterated over, otherwise returns false.
     */
    public function hasChildren() {
      return ($this->current() instanceof Form);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Returns an iterator for the current entry.
     * @link http://php.net/manual/en/recursiveiterator.getchildren.php
     * @return RecursiveIterator An iterator for the current entry.
     */
    public function getChildren() {
      return $this->current();
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     *       The return value is cast to an integer.
     */
    public function count() {
      return count($this->fields);
    }
  }
