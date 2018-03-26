<?php

/**
 * Настройки модуля
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

class Atolonline_admin extends Frame_admin {

    /**
     * @var array поля в базе данных для редактирования
     */
    public $variables = array(
        'config' => array(
            'hr1' => array(
                'type' => 'title',
                'name' => 'Настройки безопасности',
            ),
            'login' => array(
                'type' => 'text',
                'name' => 'Логин',
            ),
            'pass' => array(
                'type' => 'password',
                'name' => 'Пароль',
            ),
            'hr2' => array(
                'type' => 'title',
                'name' => 'Информация об организации',
            ),
            'inn' => array(
                'type' => 'text',
                'name' => 'ИНН',
                'multilang' => false,
            ),
            'group_code' => array(
                'type' => 'text',
                'name' => 'Идентификатор группы ККТ',
                'multilang' => false,
            ),
            'vat' => array(
                'type' => 'select',
                'name' => 'Настройки ставок НДС',
                'select' => array(
                    'vat18' => 'ставка НДС 18%',
                    'vat10' => 'ставка НДС 10%',
                    'vat118' => 'ставка НДС расч. 18/118',
                    'vat110' => 'ставка НДС расч. 10/110',
                    'vat0' => 'ставка НДС 0%',
                    'none' => 'НДС не облагается'
                ),
            ),
            'sno' => array(
                'type' => 'select',
                'name' => 'Система налогообложения',
                'select' => array(
                    'osn' => 'Общая, ОСН',
                    'usn_income' => 'Упрощенная доход, УСН доход',
                    'usn_income_outcome' => 'Упрощенная доход минус расход, УСН доход - расход',
                    'envd' => 'Единый налог на вмененный доход, ЕНВД',
                    'esn' => 'Единый сельскохозяйственный налог, ЕСН',
                    'patent' => 'Патентная система налогообложения, Патент'
                )
            ),
            'hr3' => 'hr',
            'mode' => array(
                'type' => 'select',
                'name' => 'Режим работы кассы',
                'select' => array(
                    'demo' => 'тестовый',
                    'work' => 'боевой'
                )
            ),
            'test' => array(
                'type' => 'function',
            ),
        )
    );

    /**
     * @var array настройки модуля
     */
    public $config = array(
        'config', // файл настроек модуля
    );

    public function edit_config_variable_test() {
        echo '<div class="unit">'
        . '<p><button class="btn js_btn_test" type="button">' . $this->diafan->_('Создать проверочный чек') . '</button></p>'
        . '<p><pre id="test_check"></pre></p>'
        . '</div>';
    }

}
