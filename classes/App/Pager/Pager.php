<?php

    /**
     * PHP version 5.4
     * @category  PHP
     * @package   adv.accounts.app
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
    namespace ADV\App\Pager;

    use ADV\Core\View;
    use ADV\App\Form\Button;
    use ADV\Core\DIC;
    use ADV\Core\DB\DB;
    use ADV\App\Dates;
    use ADV\Core\JS;
    use ADV\Core\Input\Input;
    use ADV\Core\Num;
    use ADV\App\Forms;
    use ADV\Core\Ajax;

    /**
     * Controler part of database table pager with column sort.
     * To display actual html object call $table->display($name) inside
     * any form.

     */
    class Pager implements \Countable
    {
        const SQL            = 1;
        const ARR            = 2;
        const NEXT           = 'next';
        const PREV           = 'prev';
        const LAST           = 'last';
        const FIRST          = 'first';
        const TYPE_BOOL      = 'bool';
        const TYPE_TIME      = 'time';
        const TYPE_DATE      = 'date';
        const TYPE_DATESTAMP = 'dstamp';
        const TYPE_TIMESTAMP = 'tstamp';
        const TYPE_PERCENT   = 'percent';
        const TYPE_AMOUNT    = 'amount';
        const TYPE_QTY       = 'qty';
        const TYPE_EMAIL     = 'email';
        const TYPE_RATE      = 'rate';
        const TYPE_INACTIVE  = 'inactive';
        const TYPE_ID        = 'id';
        const TYPE_SKIP      = 'skip';
        const TYPE_GROUP     = 'group';
        const TYPE_HIDDEN    = 'hidden';
        const TYPE_FUNCTION  = 'fun';
        /** @var \ADV\Core\DB\DB */
        static $DB;
        /** @var Input */
        static $Input;
        /** @var JS */
        static $JS;
        /** @var Dates */
        static $Dates;
        public $rowFunction;
        public $showInactive = null;
        public $class = 'padded grid ';
        /** @var string table width (default '80%') */
        public $width = "80%";
        /** @var */
        protected $sql;
        /**@var */
        protected $name;
        /** column definitions (head, type, order) */
        protected $columns = [];
        protected $rowGroup = [];
        /** @var array */
        protected $data = [];
        /** @var */
        protected $curr_page = 1;
        /** @var */
        protected $max_page = 1;
        /** @var */
        protected $last_page;
        /** @var */
        protected $prev_page;
        /** @var */
        protected $next_page;
        /** @var */
        protected $first_page;
        /** @var int|? */
        protected $page_length = 1;
        /** @var */
        protected $rec_count = 0;
        /** @var */
        protected $select;
        /** @var */
        protected $where;
        /** @var */
        protected $from;
        /** @var */
        protected $group;
        /** @var */
        protected $order;
        /** @var */
        protected $extra_where;
        /** @var bool */
        protected $ready;
        protected $type;
        protected $dataset = [];
        protected $currentRowGroup = null;
        protected $fieldnames;
        /**
         * @param      $name
         * @param      $sql
         * @param      $coldef
         */
        public function __construct($name, $sql, $coldef = null) {
            $this->name = $name;
            $this->setData($sql);
            if ($coldef === null) {
                $this->setColumns((array) $sql);
            } else {
                $this->setData($sql);
                $this->setColumns((array) $coldef);
            }
        }
        /**
         * @static
         *
         * @param $name
         * @param $coldef
         *
         * @return Pager $this
         */
        public static function newPager($name, $coldef) {
            $c = \ADV\Core\DIC::i();
            if (!isset($_SESSION['pager'])) {
                $_SESSION['pager'] = [];
            }
            if (isset($_SESSION['pager'][$name])) {
                $pager = $_SESSION['pager'][$name];
            }
            if (!isset($pager)) {
                $pager = new static($name, $coldef);
            }
            if (count($coldef) != count($pager)) {
                $pager->refresh();
            }
            static::$Input = $c->offsetGet('Input');
            static::$JS    = $c->offsetGet('JS');
            static::$Dates = $c->offsetGet('Dates');
            static::$DB    = $c->offsetGet('DB');
            /** @var \ADV\App\User $user */
            $user                     = $c->offsetGet('User');
            $pager->page_length       = $user->prefs->query_size;
            $_SESSION['pager'][$name] = $pager;
            $pager->restoreColumnFunction($coldef);
            if (static::$Input->post(FORM_ACTION) == 'showInactive') {
                $pager->showInactive = (static::$Input->post(FORM_VALUE, Input::NUMERIC) == 1);
            }
            return $pager;
        }
        /**
         * @param $sql
         * Parse base sql select query.
         */
        public function setData($sql) {
            if (is_array($sql)) {
                $this->sql       = $sql;
                $this->type      = self::ARR;
                $this->rec_count = count($this->sql);
                $this->max_page  = $this->page_length ? ceil($this->rec_count / $this->page_length) : 0;
                $this->ready     = false;
                return;
            }
            if ($sql != $this->sql) {
                $this->sql   = $sql;
                $this->type  = self::SQL;
                $this->ready = false;
                $parts       = preg_split('/\sORDER\s*BY\s/si', $sql, 2);
                if (count($parts) == 2) {
                    $sql         = $parts[0];
                    $this->order = $parts[1];
                }
                $parts       = preg_split('/\sGROUP\s*BY\s/si', $sql, 2);
                $this->group = null;
                if (count($parts) == 2) {
                    $sql         = $parts[0];
                    $this->group = $parts[1];
                }
                $parts = preg_split('/\sWHERE\s/si', $sql, 2);
                if (count($parts) == 2) {
                    $sql         = $parts[0];
                    $this->where = $parts[1];
                }
                $parts = preg_split('/\sFROM\s/si', $sql, 2);
                if (count($parts) == 2) {
                    $sql        = $parts[0];
                    $this->from = $parts[1];
                }
                $this->select = $sql;
            }
        }
        /**
         * @param null $sql
         */
        public function refresh($sql = null) {
            if ($sql) {
                $this->setData($sql);
            }
            $this->ready = false;
        }
        /**
         * @static
         * @internal param \ADV\App\Pager\Pager $pager
         * @return bool
         */
        public function display() {
            $this->selectRecords();
            Ajax::_start_div("_{$this->name}_span");
            $view = new View('ui/pager');
            $view->set('headers', $this->generateHeaders());
            $view->set('class', $this->class . ' width' . rtrim($this->width, '%'));
            $view->set('inactive', $this->showInactive !== null);
            $this->generateNav($view);
            $this->currentRowGroup = null;
            $this->fieldnames      = array_keys(reset($this->data));
            $rows                  = [];
            foreach ($this->data as $row) {
                if ($this->rowGroup) {
                    $fields = $this->fieldnames;
                    $field  = $fields[$this->rowGroup[0][0] - 1];
                    if ($this->currentRowGroup != $row[$field]) {
                        $this->currentRowGroup = $row[$field];
                        $row['group']          = $row[$field];
                        $row['colspan']        = count($this->columns);
                    }
                }
                if (is_callable($this->rowFunction)) {
                    $row['attrs'] = call_user_func($this->rowFunction, $row);
                }
                $row['cells'] = $this->displayRow($row);
                $rows[]       = $row;
            }
            $view->set('rows', $rows);
            $view->render();
            Ajax::_end_div();
            return true;
        }
        /**
         * @param $name
         */
        public static function kill($name) {
            unset($_SESSION['pager'][$name]);
        }
        /**
         * @return array
         */
        public function __sleep() {
            unset($this->rowFunction);
            foreach ($this->columns as &$col) {
                if (isset($col['fun'])) {
                    $col['fun'] = null;
                }
            }
            return array_keys((array) $this);
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
            return count($this->columns);
        }
        /**
         * @return string
         */
        public function __tostring() {
            ob_start();
            $this->display();
            return ob_get_clean();
        }
        /** Initialization after changing record set
         * @return bool
         */
        protected function checkState() {
            if ($this->ready == false) {
                if ($this->type == self::SQL) {
                    $sql    = $this->generateSQL(true);
                    $result = static::$DB->_query($sql, 'Error reading record set');
                    if ($result == false) {
                        return false;
                    }
                    $row             = static::$DB->_fetchRow($result);
                    $this->rec_count = $row[0];
                    $this->max_page  = $this->page_length ? ceil($this->rec_count / $this->page_length) : 0;
                    $this->setPage(self::FIRST, false);
                } elseif ($this->type == self::ARR) {
                    $this->rec_count  = count($this->sql);
                    $ord              = $this->rowGroup;
                    $this->fieldnames = array_keys($this->sql[0]);
                    $this->dataset    = [];
                    foreach ($this->sql as $key => $row) {
                        $row[]           = $key;
                        $this->dataset[] = $row;
                    }
                    foreach ($this->columns as $key => $col) {
                        if (isset($col['ord'])) {
                            if ($col['ord']) {
                                // offset by one because want to 1 indexed and columns is 0 indexed
                                $ord[] = [$key + 1, $col['ord']];
                            }
                        }
                    }
                    if ($ord) {
                        $ordcount = count($ord);
                        for ($i = 0; $i < $ordcount; $i++) {
                            $index = $ord[$i];
                            if ($index[1] == 'none' && $i < $ordcount) {
                                continue;
                            }
                            foreach ($this->dataset as $key => $row) {
                                if ($index[1] == 'none') {
                                    $args[$this->fieldnames[$index[0] - 1]][$key] = end($row);
                                    continue;
                                }
                                //minus 1 because fields are 0 indexed
                                $args[$this->fieldnames[$index[0] - 1]][$key] = $row[$this->fieldnames[$index[0] - 1]];
                            }
                            $args[] = ($index[1] == 'desc' ? SORT_DESC : SORT_ASC);
                        }
                        $args[] =& $this->dataset;
                        call_user_func_array('array_multisort', $args);
                    }
                    $this->max_page   = $this->page_length ? ceil($this->rec_count / $this->page_length) : 0;
                    $this->curr_page  = $this->curr_page ? : 1;
                    $this->next_page  = ($this->curr_page < $this->max_page) ? $this->curr_page + 1 : null;
                    $this->prev_page  = ($this->curr_page > 1) ? ($this->curr_page - 1) : null;
                    $this->last_page  = ($this->curr_page < $this->max_page) ? $this->max_page : null;
                    $this->first_page = ($this->curr_page != 1) ? 1 : null;
                }
                $this->ready = true;
            }
            return true;
        }
        /**
         * Set column definitions
         * types: inactive|skip|insert
         *
         * @param $columns
         */
        protected function setColumns($columns) {
            foreach ($columns as $colindex => $coldef) {
                if (is_string($colindex) && is_string($coldef)) {
                    $c = ['head' => $colindex, 'type' => $coldef];
                } elseif (is_string($colindex) && is_array($coldef)) {
                    $coldef ['head'] = $colindex;
                    $c               = $coldef;
                } elseif (is_array($coldef)) {
                    $coldef['head'] = '';
                    $c              = $coldef;
                } else {
                    $c = ['head' => $coldef, 'type' => 'text'];
                }
                if (!isset($c['type'])) {
                    $c['type'] = 'text';
                }
                switch ($c['type']) {
                    case self::TYPE_INACTIVE:
                        if ($this->showInactive === null) {
                            $this->showInactive = false;
                        }
                        break;
                    case self::TYPE_GROUP:
                        $this->rowGroup[] = [count($this->columns) + 1, 'asc'];
                        break;
                    case 'insert':
                    default:
                        break;
                    case self::TYPE_SKIP: // skip the column (no header)
                    case self::TYPE_HIDDEN: // skip the column (no header)
                        unset($c['head']);
                        break;
                }
                if (isset($c['fun'])) {
                    $c['funkey'] = $colindex;
                }
                $this->columns[] = $c;
            }
        }
        /**
         * @param      $to
         * Calculates page numbers for html controls.
         * @param bool $query
         *
         * @return void
         */
        protected function setPage($to, $query = true) {
            switch ($to) {
                case self::NEXT:
                    $page = $this->curr_page + 1;
                    break;
                case self::PREV:
                    $page = $this->curr_page - 1;
                    break;
                case self::LAST:
                    $page = $this->last_page;
                    break;
                default:
                    if (is_numeric($to)) {
                        $page = $to;
                        break;
                    }
                case self::FIRST:
                    $page = 1;
                    break;
            }
            $page             = ($page < 1) ? 1 : $page;
            $max              = $this->max_page;
            $page             = ($page > $max) ? $max : $page;
            $this->curr_page  = $page;
            $this->next_page  = ($page < $max) ? $page + 1 : null;
            $this->prev_page  = ($page > 1) ? ($page - 1) : null;
            $this->last_page  = ($page < $max) ? $max : null;
            $this->first_page = ($page != 1) ? 1 : null;
            if ($query) {
                $this->query();
            }
        }
        /**
         * @param null $where
         *
         * @return mixed
         * Set additional constraint on record set
         */
        protected function setWhere($where = null) {
            if ($where) {
                if (!is_array($where)) {
                    $where = array($where);
                }
                if (count($where) == count($this->extra_where) && !count(array_diff($this->extra_where, $where))
                ) {
                    return;
                }
            }
            $this->extra_where = $where;
            $this->ready       = false;
        }
        /**
         * @param $name - base name for pager controls and $_SESSION object name
         *              -----------------------------F------------------------------------------------
         *              Creates new \ADV\App\Pager\Pager $_SESSION object on first page call.
         *              Retrieves from $_SESSION var on subsequent $_POST calls
         *              $sql  - base sql for data inquiry. Order of fields implies
         *              pager columns order.
         *              $coldef - array of column definitions. Example definitions
         *              Column with title 'User name' and default text format:
         *              'User name'
         *              Skipped field from sql query. Data for the field is not displayed:
         *              'dummy' => 'skip'
         *              Column without title, data retrieved form row data with function func():
         *              array('fun'=>'func')
         *              Inserted column with title 'Some', formated with function rowfun().
         *              formated as date:
         *              'Some' => array('type'=>'date, 'insert'=>true, 'fun'=>'rowfun')
         *              Column with name 'Another', formatted as date,
         *              sortable with ascending start order (available orders: asc,desc, '').
         *              'Another' => array('type'=>'date', 'ord'=>'asc')
         *              All available column format types you will find in DB_Pager_view.php file.
         *              If query result has more fields than count($coldef), rest of data is ignored
         *              during display, but can be used in format handlers for 'spec' and 'insert'
         *              type columns.
         *              Force pager initialization.

         */
        /**
         * @return bool
         * Query database
         */
        protected function query() {
            Ajax::_activate("_{$this->name}_span");
            if (!$this->checkState()) {
                return false;
            }
            if ($this->type == self::SQL) {
                $this->data = [];
                if ($this->rec_count == 0) {
                    return true;
                }
                $sql    = $this->generateSQL(false);
                $result = static::$DB->_query($sql, 'Error browsing database: ' . $sql);
                if (!$result) {
                    return false;
                }
                $this->data = static::$DB->_fetchAll();
            } elseif ($this->type == self::ARR) {
                $offset = ($this->curr_page - 1) * $this->page_length;
                if ($offset + $this->page_length >= $this->rec_count) {
                    $offset = $this->rec_count - $this->page_length;
                }
                $this->data = array_slice($this->dataset, $offset, $this->page_length);
            }
            $dbfield_names = array_keys($this->data[0]);
            $cnt           = min(count($dbfield_names), count($this->columns));
            for ($c = $i = 0; $c < $cnt; $c++) {
                if (!(isset($this->columns[$c]['insert']) && $this->columns[$c]['insert'])) {
                    //	if (!@($this->columns[$c]['type']=='skip'))
                    $this->columns[$c]['name'] = $dbfield_names[$c];
                    if (isset($this->columns[$c]['type']) && !($this->columns[$c]['type'] == 'insert')) {
                        $i++;
                    }
                }
            }
            return true;
        }
        /**
         * Set current page in response to user control.
         */
        protected function selectRecords() {
            $pagetype = Forms::findPostPrefix($this->name . '_page_', false);
            $sort     = Forms::findPostPrefix($this->name . '_sort_', false);
            if (!$pagetype) {
                $page = $_GET['p'];
            } else {
                $page = $_POST[$this->name . '_page_' . $pagetype];
            }
            if ($page) {
                $this->setPage($page);
            }
            if ($pagetype) {
                if ($pagetype == self::NEXT && !$this->next_page || $pagetype == self::FIRST && !$this->first_page) {
                    static::$JS->setFocus($this->name . '_page_prev_top');
                }
                if ($pagetype == self::PREV || $pagetype == self::LAST && !$this->last_page) {
                    static::$JS->setFocus(['el' => $this->name . '_page_next_bottom', 'pos' => 'bottom']);
                }
            } elseif ($sort !== null) {
                $this->sortRecords($sort);
            } else {
                $this->query();
            }
        }
        /**
         * @param $col
         *
         * @return bool
         * Change sort column direction
         * in order asc->desc->none->asc
         */
        protected function sortRecords($col) {
            if (is_null($col)) {
                return false;
            }
            $current_order = Input::_post($this->name . '_sort_' . $col);
            switch ($current_order) {
                case 'asc':
                    $ord = 'desc';
                    break;
                case 'desc':
                    $ord = 'none';
                    break;
                case '':
                case 'none':
                    $ord = 'asc';
                    break;
                default:
                    return false;
            }
            $this->columns[$col]['ord'] = $ord;
            $this->ready                = false;
            $this->setPage(self::FIRST);
            return true;
        }
        /**
         * @param bool $count
         *
         * @return string
         * Generate db query from base sql
         * $count==false - for current page data retrieval
         * $count==true  - for total records count

         */
        protected function generateSQL($count = false) {
            $select = $this->select;
            $from   = $this->from;
            $where  = $this->where;
            $group  = $this->group;
            $order  = $this->order;
            if (count($this->extra_where)) {
                $where .= ($where == '' ? '' : ' AND ') . implode($this->extra_where, ' AND ');
            }
            if ($where) {
                $where = " WHERE ($where)";
            }
            if ($count) {
                $group = $group == '' ? "*" : "DISTINCT $group";
                return "SELECT COUNT($group) FROM $from $where";
            }
            $sql = "$select FROM $from $where";
            if ($group) {
                $sql .= " GROUP BY $group";
            }
            $ord = [];
            foreach ($this->rowGroup as $group) {
                $ord[] = $group[0] . ' ' . $group[1];
            }
            foreach ($this->columns as $key => $col) {
                if (isset($col['ord'])) {
                    if ($col['ord'] && $col['ord'] != 'none') {
                        $name  = isset($col['name']) ? $col['name'] : $key + 1;
                        $ord[] = $name . ' ' . $col['ord'];
                    }
                }
            }
            if (count($ord)) {
                $sql .= " ORDER BY " . implode($ord, ',');
            } elseif ($order) {
                $sql .= " ORDER BY $order";
            } // original base query order
            $page_length = $this->page_length;
            $offset      = ($this->curr_page - 1) * $page_length;
            $sql .= " LIMIT $offset, $page_length";
            return $sql;
        }
        /** @return array */
        protected function generateHeaders() {
            $headers  = [];
            $inactive = !Input::_post('show_inactive');
            foreach ($this->columns as $num_col => $col) {
                if (isset($col['head']) || $inactive) {
                    if (in_array($col['type'], [self::TYPE_SKIP, self::TYPE_GROUP, self::TYPE_HIDDEN]) || $col['type'] == self::TYPE_INACTIVE && $this->showInactive === false) {
                        continue;
                    }
                    if (!isset($col['ord'])) {
                        $headers[] = $col['head'];
                        continue;
                    }
                    switch ($col['ord']) {
                        case 'desc':
                            $icon = " <i class='" . ICON_DESC . "'> </i>";
                            break;
                        case 'asc':
                            $icon = " <i class='" . ICON_ASC . "'> </i>";
                            break;
                        default:
                            $icon = '';
                    }
                    $headers[] = $this->formatNavigation('', $this->name . '_sort_' . $num_col, $col['ord'], true, $col['head'] . $icon);
                }
            }
            return $headers;
        }
        /**
         * @param \ADV\Core\View $view
         */
        protected function generateNav(View $view) {
            $navigation = [
                [self::FIRST, 1, $this->first_page, 'fast-backward'],
                [self::PREV, $this->curr_page - 1, $this->prev_page, 'backward'],
                [self::NEXT, $this->curr_page + 1, $this->next_page, 'forward'],
                [self::LAST, $this->max_page, $this->last_page, 'fast-forward'],
            ];
            $navbuttons = [];
            if ($this->showInactive !== null) {
                $view['checked'] = ($this->showInactive) ? 'checked' : '';
                Ajax::_activate("_{$this->name}_span");
            }
            $view['colspan'] = count($this->columns);
            if ($this->rec_count) {
                foreach ($navigation as $v) {
                    $navbuttons[] = $this->formatNavigation('top', $this->name . '_page_' . $v[0], $v[1], $v[2], "<i class='icon-" . $v[3] . "'> </i>");
                }
                $view->set('navbuttons', $navbuttons);
                $from = ($this->curr_page - 1) * $this->page_length + 1;
                $to   = $from + $this->page_length - 1;
                if ($to > $this->rec_count) {
                    $to = $this->rec_count;
                }
                $all             = $this->rec_count;
                $view['records'] = "Records $from-$to of $all";
            } else {
                $view['records'] = "No Records";
            }
            $navbuttons = [];
            foreach ($navigation as $v) {
                $navbuttons[] = $this->formatNavigation('bottom', $this->name . '_page_' . $v[0], $v[1], $v[2], "<i class='icon-" . $v[3] . "'> </i>");
            }
            $view->set('navbuttonsbottom', $navbuttons);
        }
        /**
         * @param      $row
         * @param null $columns
         *
         * @internal param \ADV\App\Form\Form|null $form
         * @return mixed
         */
        protected function displayRow($row, $columns = null) {
            $columns = $columns ? : $this->columns;
            $cells   = [];
            foreach ($columns as $col) {
                $coltype = isset($col['type']) ? $col['type'] : '';
                $content = isset($col['name']) ? $row[$col['name']] : '';
                $attrs   = '';
                if (isset($col['fun'])) { // use data input function if defined
                    $fun = $col['fun'];
                    if (is_callable($fun)) {
                        $content = call_user_func($fun, $row, $col['useName'] ? $col['name'] : $content);
                    } elseif (is_callable([$this, $fun])) {
                        $content = $this->$fun($row, $content);
                    } else {
                        $content = '';
                    }
                }
                $class = isset($col['class']) ? $col['class'] : null;
                switch ($coltype) { // format columnhsdaasdg
                    case self::TYPE_BOOL:
                        $content = $content ? 'Yes' : 'No';
                        $attrs   = " class='$class width40'";
                        break;
                    case self::TYPE_TIME:
                        $attrs = " class='$class width40'";
                        break;
                    case self::TYPE_DATE:
                        $content = static::$Dates->sqlToDate($content);
                        $attrs   = " class='$class center nowrap'";
                        break;
                    case self::TYPE_DATESTAMP: // time stamp displayed as date
                        $content = static::$Dates->sqlToDate(substr($content, 0, 10));
                        $attrs   = " class='$class center nowrap'";
                        break;
                    case self::TYPE_TIMESTAMP: // time stamp - FIX useformat
                        $content = static::$Dates->sqlToDate(substr($content, 0, 10)) . ' ' . substr($content, 10);
                        $attrs   = "class='$class center'";
                        break;
                    case self::TYPE_PERCENT:
                        $content = Num::_percentFormat($content * 100) . '%';
                        $attrs   = ' class="alignright nowrap"';
                        break;
                    case self::TYPE_AMOUNT:
                        if ($content !== '') {
                            $content = Num::_priceFormat($content);
                            $attrs   = "class='amount' ";
                        }
                        break;
                    case self::TYPE_QTY:
                        if ($content !== '') {
                            $dec     = isset($col['dec']) ? $col['dec'] : 0;
                            $content = Num::_format(Num::_round($content, $dec), $dec);
                            $attrs   = ' class="alignright nowrap"';
                        }
                        break;
                    case self::TYPE_EMAIL:
                        $content = "<a href='mailto:$content'>$content</a>";
                        $attrs   = isset($col['align']) ? "class='$class " . $col['align'] . "'" : '';
                        break;
                    case self::TYPE_RATE:
                        $content = Num::_exrateFormat($content);
                        $attrs   = "class='$class center'";
                        break;
                    case self::TYPE_INACTIVE:
                        if ($this->showInactive === true) {
                            $checked = $row[self::TYPE_INACTIVE] ? 'checked' : '';
                            $content = '<input ' . $checked . ' type="checkbox" name="_action" value="' . INACTIVE . $row[self::TYPE_ID] . '" onclick="JsHttpRequest.request(this)">';
                            $attrs   = ' class="center"';
                        } else {
                            continue 2;
                        }
                        break;
                    case self::TYPE_ID:
                        if (isset($col['align'])) {
                            $attrs = " class='$class " . $col['align'] . " pagerclick' data-id='" . $row[self::TYPE_ID] . "'";
                        } else {
                            $attrs = " class='$class pagerclick' data-id='" . $row[self::TYPE_ID] . "'";
                        }
                        break;
                    default:
                        $attrs = isset($col['align']) ? " class='$class align" . $col['align'] . "'" : ($class ? "class='$class'" : "");
                        break;
                    case self::TYPE_SKIP: // column not displayed
                    case self::TYPE_GROUP: // column not displayed.
                    case self::TYPE_HIDDEN: // column not displayed.
                        continue 2;
                }
                $cells[] = ['cell' => $content, 'attrs' => $attrs];
            }
            return $cells;
        }
        /**
         * @static
         *
         * @param          $id
         * @param          $name
         * @param          $value
         * @param bool     $enabled
         * @param null     $title
         *
         * @internal param \ADV\Core\HTML $html
         * @return string
         */
        protected function formatNavigation($id, $name, $value, $enabled = true, $title = null) {
            $button = new Button($name, $value, $title);
            $attrs  = ['disabled' => (bool) !$enabled, 'class' => 'navibutton', 'type' => 'submit',];
            $id     = $id ? $name . '_' . $id : $name;
            return $button->mergeAttr($attrs)->id($id);
        }
        /**
         * @param $coldef
         */
        protected function restoreColumnFunction($coldef) {
            foreach ($this->columns as &$column) {
                if (isset($column['funkey'])) {
                    $column['fun'] = $coldef[$column['funkey']]['fun'];
                }
            }
        }
    }

