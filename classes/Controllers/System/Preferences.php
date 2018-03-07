<?php

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Controllers\System;

  use ADV\App\Dates;
  use ADV\Core\View;
  use ADV\Core\Event;
  use ADV\App\ADVAccounting;
  use ADV\Core\Config;
  use ADV\App\Form\Form;

  class Preferences extends \ADV\App\Controller\Action
  {
    protected $title = 'Preferences';
    protected $security = SA_SETUPDISPLAY;
    protected $Dates;
    protected function before() {
      $this->Dates = Dates::i();
      if (REQUEST_POST) {
        $this->User->update_prefs($_POST);
      }
    }
    protected function index() {
      $view = new View('preferences');
      $form = new Form();
      $view->set('form', $form);
      $form->group('decimals');
      $form->number('prices', 0)->label('Prices/Amounts:');
      $form->number('qty_dec', 0)->label('Quantities:');
      $form->number('exrate_dec', 0)->label('Exchange Rates:');
      $form->number('percent_dec', 0)->label('Percentages:');
      $form->group('dates');
      $form->arraySelect('date_format', $this->Dates->formats)->label('Dateformat');
      $form->arraySelect('date_sep', $this->Dates->separators)->label('Date Separator:');
      $form->arraySelect('tho_sep', Config::_get('separators_thousands'))->label('Thousand Separator:');
      $form->arraySelect('dec_sep', Config::_get('separators_decimal'))->label('Decimal Separator:');
      $form->group('other');
      $form->checkbox('show_hints')->label('Show Hints');
      $form->checkbox('show_gl')->label('Show GL Information:');
      $form->checkbox('show_codes')->label('Show Item Codes:');
      $form->arraySelect('theme', $this->getThemes())->label('Theme:');
      $form->arraySelect('page_size', Config::_get('print_paper_sizes'))->label('Page Size:');
      $form->arraySelect('startup_tab', $this->getApplications())->label('Start-up Tab:');
      $form->checkbox('rep_popup')->label('Use popup window to display reports:');
      $form->checkbox('graphic_links')->label('Use icons instead of text links:');
      $form->checkbox('query_size')->label('Query page size:');
      $form->checkbox('sticky_doc_date')->label('Remember last document date:');
      $form->setValues($this->User->prefs);
      $view->render();
    }
    /**
     * @return array
     */
    protected function getApplications() {
      $apps = ADVAccounting::i()->applications;
      $tabs = [];
      foreach ($apps as $app => $config) {
        if ($config['enabled']) {
          $tabs[$app] = $app;
        }
      }
      return $tabs;
    }
    /**
     * @return array
     */
    protected function getThemes() {
      $themes = [];
      try {
        $themedir = new \DirectoryIterator(ROOT_WEB . PATH_THEME);
        /** @var \DirectoryIterator $theme */
        foreach ($themedir as $theme) {
          if (!$theme->isDot() && $theme->isDir()) {
            $themes[$theme->getFilename()] = $theme->getFilename();
          }
        }
        ksort($themes);
      } catch (\UnexpectedValueException $e) {
        Event::error($e->getMessage());
      }
      return $themes;
    }
  }


