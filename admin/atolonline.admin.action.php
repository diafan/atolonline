<?php

/**
 * Обработка POST-запросов в административной части модуля
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

class Atolonline_admin_action extends Action_admin {

    public function init() {
        if (empty($_POST["action"]))
            return false;

        switch ($_POST["action"]) {
            case 'test':
                $this->result['data'] = $this->diafan->_atolonline->test();

                break;
        }
    }

}
