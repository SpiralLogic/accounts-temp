<?php
    /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
    /*
         Print request redirector. This file is fired via print link or
         print button in reporting module.
       */
    if (isset($_GET['xls'])) {
        $filename    = $_GET['filename'];
        $unique_name = $_GET['unique'];
        $path        = PATH_COMPANY . 'pdf_files/';
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=$filename");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
        header("Pragma: public");
        echo file_get_contents($path . $unique_name);
        exit();
    } elseif (isset($_GET['xml'])) {
        $filename    = $_GET['filename'];
        $unique_name = $_GET['unique'];
        $path        = PATH_COMPANY . 'pdf_files/';
        header("content-type: text/xml");
        header("Content-Disposition: attachment; filename=$filename");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
        header("Pragma: public");
        echo file_get_contents($path . $unique_name);
        exit();
    }
    if (!isset($_POST['REP_ID']) && isset($_GET['REP_ID'])) { // print link clicked
        $def_pars = [0, 0, '', '', 0, '', '', 0]; //default values
        $rep      = $_POST['REP_ID'] = $_GET['REP_ID'];
        for ($i = 0; $i < 8; $i++) {
            $_POST['PARAM_' . $i] = isset($_GET['PARAM_' . $i]) ? $_GET['PARAM_' . $i] : $def_pars[$i];
        }
    }
    if (isset($_POST['REP_ID'])) {
        $rep_file = PATH_REPORTS . "rep{$_POST['REP_ID']}.php";
        if (file_exists($rep_file)) {
            require($rep_file);
        }
    }
    exit();
