<?php

/**
 * Установка модуля
 *
 * @package    DIAFAN.CMS
 * @author     diafan.ru
 * @version    6.0
 * @license    http://www.diafan.ru/license.html
 * @copyright  Copyright (c) 2003-2016 OOO «Диафан» (http://www.diafan.ru/)
 */
if (!defined('DIAFAN')) {
    $path = __FILE__;
    $i = 0;
    while (!file_exists($path . '/includes/404.php')) {
        if ($i == 10)
            exit;
        $i++;
        $path = dirname($path);
    }
    include $path . '/includes/404.php';
}

class Atolonline_install extends Install {

    /**
     * @var string название
     */
    public $title = "АТОЛ Онлайн";

    /**
     * @var array записи в таблице {modules}
     */
    public $modules = array(
        array(
            "name" => "atolonline",
            "admin" => true,
            "site" => true,
            "site_page" => false,
        ),
    );

    /**
     * @var array меню административной части
     */
    public $admin = array(
        array(
            "name" => "АТОЛ Онлайн",
            "rewrite" => "atolonline",
            "group_id" => "2",
            "act" => true,
            "children" => array(
                array(
                    "name" => "АТОЛ Онлайн",
                    "rewrite" => "atolonline",
                    "act" => true,
                ),
            )
        ),
    );


}
