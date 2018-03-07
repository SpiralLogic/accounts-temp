<?php
    /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @date      30/08/12
     * @link      http://www.advancedgroup.com.au
     **/
    namespace ADV\App\Form;

    use ADV\Core\JS;

    /** **/
    class Button implements \ArrayAccess
    {
        use \ADV\Core\Traits\HTML;
        const NORMAL = "default";
        const MINI = "xs";
        const SMALL = "sm";
        const LARGE = "lg";
        const SUCCESS = "success";
        const DANGER = "danger";
        const PRIMARY = "primary";
        public $id;
        public $validator;
        protected $attr = [];
        protected $name;
        protected $caption = '';
        protected $preicon;
        protected $posticon;
        /**
         * @param $name
         * @param $value
         * @param $caption
         */
        public function __construct($name, $value, $caption) {
            $this->name = $this->attr['name'] = $name;
            $this->id                         = $this->nameToId();
            $this->attr['value']              = e($value);
            $this->attr['title']              = e(strip_tags($caption));
            $this->attr['class']              = 'btn btn-'.self::NORMAL;
            $this->caption                    = $caption;
        }
        /**
         * @param $type
         *
         * @return \ADV\App\Form\Button
         */
        public function type($type) {
            $this->attr['class'] .= ' btn-'. $type;
            return $this;
        }
        /**
         * @param $id
         *
         * @return Button
         */
        public function id($id) {
            $this->attr['id'] = $id;
            return $this;
        }
        /**
         * @param bool $hide
         *
         * @return Button
         */
        public function hide($hide = true) {
            $this->attr['style'] = $hide ? 'display:none;' : null;
            return $this;
        }
        /**
         * @param $warning
         *
         * @return Button
         */
        public function setWarning($warning) {
            JS::_beforeload("_validate['" . $this->attr['value'] . "']=function(){ return confirm('" . strtr($warning, array("\n" => '\\n')) . "');};");
            return $this;
        }
        /**
         * @param $icon
         *
         * @internal param $text
         * @return \ADV\App\Form\Button
         */
        public function preIcon($icon) {
            $this->preicon = $icon;
            return $this;
        }
        /**
         * @param $icon
         *
         * @return \ADV\App\Form\Button
         */
        public function postIcon($icon) {
            $this->posticon = $icon;
            return $this;
        }
        /**
         * @param $validator
         *
         * @return \ADV\App\Form\Button
         */
        public function setValidator($validator) {
            $this->validator = $validator;
            return $this;
        }
        /**
         * @param $attr
         *
         * @return Button
         */
        public function mergeAttr($attr) {
            $this->attr = array_merge($this->attr, (array) $attr);
            return $this;
        }
        /**
         * @return mixed
         */
        protected function nameToId() {
            return str_replace(['[', ']'], ['-', ''], $this->name);
        }
        /**

         */
        protected function formatIcons() {
            if ($this->preicon) {
                $this->caption = "<i class='" . $this->preicon . "' > </i> " . $this->caption;
            }
            if ($this->posticon) {
                $this->caption .= " <i class='" . $this->posticon . "' > </i>";
            }
        }
        /**
         * @return string
         */
        public function __toString() {
            $this->formatIcons();
            return $this->makeElement('button', $this->attr, $this->caption, true);
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
            return array_key_exists($offset, $this->attr);
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
         * @return mixed Can return all value types.
         */
        public function offsetGet($offset) {
            return $this->attr[$offset];
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
            $this->attr[$offset] = $value;
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
            unset($this->attr[$offset]);
        }
    }
