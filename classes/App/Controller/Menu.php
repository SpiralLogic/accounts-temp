<?php
    /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @date      22/09/12
     * @href      http://www.advancedgroup.com.au
     **/
    namespace ADV\App\Controller;

    use ADV\App\Display;
    use ADV\Core\Arr;
    use ADV\App\Application\Func;
    use ADV\Core\View;

    /** **/
    abstract class Menu extends Base
    {
        protected $modules = [];
        /** @var */
        public $id;
        /** @var */
        public $name;
        /** @var */
        public $help_context;
        /** @var bool * */
        public $enabled = true;
        /**
         * @internal param $id
         * @internal param $name
         * @internal param bool $enabled
         */
        public function __construct($session, $user)
        {
            $this->User = $user;
            $this->id   = strtolower($this->name);
            $this->name = $this->help_context ? : $this->name;
            $this->setTitle($this->name);
        }
        abstract protected function before();
        /**
         * @param      $name
         *
         * @internal param null $icon
         * @return $this
         */
        public function add_module($name)
        {
            $this->modules[$name]    = ['right' => [], 'left' => []];
            $this->rightAppFunctions =& $this->modules[$name]['right'];
            $this->leftAppFunctions  =& $this->modules[$name]['left'];
            return $this;
        }
        /**
         * @return array
         */
        public function getModules()
        {
            $this->before();
            $modules = [];
            foreach ($this->modules as $name => $module) {
                $functions = [];
                Arr::append($functions, $module['left']);
                Arr::append($functions, $module['right']);
                foreach ($functions as &$func) {
                    $func['label'] = str_replace('&', '', $func['label']);
                }
                $modules[] = ['title' => $name, 'modules' => $functions];
            }
            return $modules;
        }
        /**
         * @param bool $embed
         *
         * @return mixed|void
         */
        public function run($embed = false)
        {
            $this->before();
            $this->index();
        }
        protected function index()
        {
            $this->Page->init(_($this->help_context = "Main Menu"), SA_OPEN, false, true);
            foreach ($this->modules as $name => $module) {
                $app            = new View('application');
                $app['colspan'] = (count($module['right']) > 0) ? 2 : 1;
                $app['name']    = $name;
                foreach ([$module['left'], $module['right']] as $modules) {
                    $mods = [];
                    foreach ($modules as $func) {
                        $mod['access'] = $this->User->hasAccess($func['access']);
                        $mod['label']  = $func['label'];
                        if ($mod['access']) {
                            $accesskey        = Display::access_string($func['label']);
                            $mod['url']       = $func['href'];
                            $mod['text']      = $accesskey[0];
                            $mod['accesskey'] = $accesskey[1];
                        } else {
                            $mod['anchor'] = Display::access_string($func['label'], true);
                        }
                        $mods[] = $mod;
                    }
                    $app->set((!$app['lmods']) ? 'lmods' : 'rmods', $mods);
                }
                $app->render();
            }
            $this->Page->end_page();
        }
        /**
         * @param        $label
         * @param string $href
         * @param string $access
         *
         * @return Func
         */
        public function addLeftFunction($label, $href = "", $access = SA_OPEN)
        {
            $appfunction              = ['label' => $label, 'href' => $href, 'access' => $access];
            $this->leftAppFunctions[] = $appfunction;
            return $appfunction;
        }
        /**
         * @param        $label
         * @param string $href
         * @param string $access
         *
         * @return Func
         */
        public function addRightFunction($label, $href = "", $access = SA_OPEN)
        {
            $appfunction               = ['label' => $label, 'href' => $href, 'access' => $access];
            $this->rightAppFunctions[] = $appfunction;
            return $appfunction;
        }
        /** @var null * */
        public $icon;
        /** @var array * */
        public $leftAppFunctions = [];
        /** @var array * */
        public $rightAppFunctions = [];
    }
