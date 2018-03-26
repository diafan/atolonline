/**
 * 
 * @package    DIAFAN.CMS
 * @author     diafan.ru
 * @version    6.0
 * @license    http://www.diafan.ru/license.html
 * @copyright  Copyright (c) 2003-2016 OOO «Диафан» (http://www.diafan.ru/)
 */

$(".js_btn_test").click(function (e) {
    e.preventDefault();

    diafan_ajax.init({
        data: {
            action: 'test',
            module: 'atolonline'
        },
        success: function (response) {
            if (response.data)
            {
                $("#test_check").text(prepare(response.data));
            }
        }
    });
});