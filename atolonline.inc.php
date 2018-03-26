<?php

/**
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

class Atolonline_inc extends Model {

    const MODULE_NAME = 'atolonline';

    /**
     * Время хранения токена в секундах, по документации 24 часа
     */
    const TOKEN_CACHE_TIME = 86400;
    const API_VERSION = 4;
    
    
    const TEST_MODE = 'demo';
    const ERROR_RESPONSE = 'error';
    const HEADER_TOKEN = 'Token';

    /*
     * Вид оплаты. Возможные значения:
      1 – электронный;
      2 – предварительная оплата (аванс);
      3 – постоплата (кредит);
      4 – иная форма оплаты;
      5 – 9 – расширенные виды оплаты. Для каждого фискального типа оплаты
      можно указать расширенный вид оплаты.
     */
    const PAYMENT_TYPE = 1;

    public function test() {
        $return = array('result' => null, 'exception' => null);

        try {
            $c = DB::query_result("SELECT COUNT(*) FROM {shop_order}");
            $order_id = DB::query_result("SELECT id FROM {shop_order} LIMIT ".rand(0,$c).',1');
            
            $return['result'] = $this->sell($order_id);
            
        } catch (AtolonlineException $ex) {
            $return['exception'] = $ex->getMessage();
        }

        return var_export($return, true);
    }

    /**
     * Получение переменных из конфигурации модуля
     * @param string $name
     * @return string|bool
     */
    public function __get($name) {
        return $this->diafan->configmodules($name, self::MODULE_NAME);
    }

    /**
     * Создание переменных в конфигурации модуля
     * @param string $name
     * @param mixed $value
     * @return string
     */
    public function __set($name, $value) {
        return $this->diafan->configmodules($name, self::MODULE_NAME, false, false, $value);
    }

    public function __construct(&$diafan) {
        parent::__construct($diafan);

        Custom::inc('plugins/httprequest/httprequest.php');
    }

    /**
     * Проверка ответа на наличие ошибок
     * @param array $response
     * @return boolean
     * @throws AtolonlineException
     */
    private function error($response) {
        if (empty($response[self::ERROR_RESPONSE])) {
            return false;
        }

        throw new AtolonlineException($response[self::ERROR_RESPONSE]['text'], $response[self::ERROR_RESPONSE]['code']);
    }

    /**
     * Возвращает API URL
     * @param string $method
     * @return string
     */
    private function getUrl($method) {

        $path = array('possystem', 'v' . self::API_VERSION);
        if ('getToken' != $method) {
            $path[] = $this->group_code;
        }
        $path[] = $method;

        return 'https://' . (self::TEST_MODE == $this->mode ? 'testonline' : 'online') . '.atol.ru/' . implode('/', $path);
    }

    /**
     * Создает POST запрос к API
     * @param string $method
     * @param array $data
     * @return \DHttpRequest
     */
    private function request($method, $data) {

        $http = DHttpRequest::post($this->getUrl($method))->form(json_encode($data))->contentType(DHttpRequest::CONTENT_TYPE_JSON);

        if ('getToken' != $method) {
            $http->header(self::HEADER_TOKEN, $this->getToken());
        }

        return $http;
    }

    /**
     * Возвращает авторизационный токен
     * @return string
     * @throws AtolonlineException
     */
    private function getToken() {

        // Последнее время запроса токена
        if (false === $this->last || time() >= self::TOKEN_CACHE_TIME + intval($this->last)) {
            $this->last = time();
        }

        $cache_meta = array(
            // уникальное название
            "name" => "getToken",
            "time" => intval($this->last),
            "inn" => $this->inn,
            "login" => $this->login,
            "pass" => $this->pass
        );

        $token = false;

        if (!$token = $this->diafan->_cache->get($cache_meta, self::MODULE_NAME)) {

            try {

                $http = $this->request('getToken', array(
                    'login' => $this->login,
                    'pass' => $this->pass
                        )
                );

                $response = json_decode($http->body(), true);

                if (!$this->error($response)) {

                    $this->last = $cache_meta["time"] = strtotime($response["timestamp"]);
                    $token = $response['token'];

                    $this->diafan->_cache->save($token, $cache_meta, self::MODULE_NAME);
                }
            } catch (DHttpRequestException $ex) {
                $this->last = null;
                throw new AtolonlineException($ex->getMessage(), $ex->getCode());
            }
        }

        return $token;
    }

    /**
     * Получает e-mail пользователя, оформившего заказ
     * копия функции из shop/inc/shop.inc.order.php потому что она private
     * 
     * @param  integer $order_id
     * @return string
     */
    private function get_email($order_id) {
        $mail = DB::query_result("SELECT e.value FROM {shop_order_param_element} AS e INNER JOIN 
			{shop_order_param} AS p ON e.param_id=p.id AND p.trash='0' AND e.trash='0' 
			WHERE p.type='email' AND e.element_id=%d", $order_id);

        if (!$mail && $user_id = DB::query_result("SELECT user_id FROM {shop_order} WHERE id=%d AND trash='0' LIMIT 1", $order_id)) {
            $mail = DB::query_result("SELECT mail FROM {users} WHERE id=%d  AND trash='0' LIMIT 1", $user_id);
        }

        return $mail;
    }

    /**
     * Конвертирует в float результат работы функции number_format
     * @param string $number
     * @return float
     */
    private function parse_number($number) {
        $dec_point = $this->diafan->configmodules("format_price_2", "shop");
        return floatval(str_replace($dec_point, '.', preg_replace('/[^\d' . preg_quote($dec_point) . ']/', '', $number)));
    }

    /**
     * чек «Приход»
     * @param int $order_id нормер заказа
     * @return sting  Уникальный идентификатор чека
     */
    public function sell($order_id) {
        return $this->createReceipt($order_id, 'sell');
    }

    /**
     * чек «Возврат прихода»
     * @param int $order_id нормер заказа
     * @return sting  Уникальный идентификатор чека
     */
    public function sell_refund($order_id) {
        return $this->createReceipt($order_id, 'sell_refund');
    }

    /**
     * чек «Расход»
     * @param int $order_id нормер заказа
     * @return sting  Уникальный идентификатор чека
     */
    public function buy($order_id) {
        return $this->createReceipt($order_id, 'buy');
    }

    /**
     * чек «Возврат расхода»
     * @param int $order_id нормер заказа
     * @return sting  Уникальный идентификатор чека
     */
    public function buy_refund($order_id) {
        return $this->createReceipt($order_id, 'buy_refund');
    }

    /**
     * POST запрос для чеков расхода, прихода, возврат расхода и возврат прихода.
     * @param int $order_id
     * @param enum string $operation
     * @return sting Уникальный идентификатор чека
     * @throws AtolonlineException
     */
    private function createReceipt($order_id, $operation) {

        $client_email = $this->get_email($order_id);
        $info = $this->diafan->_shop->order_get($order_id);

        $request = array(
            "timestamp" => date("d.m.Y H:i:s"),
            "external_id" => strval($order_id).(self::TEST_MODE == $this->mode ? "_test" :''),
            "receipt" => array(
                "client" => array("email" => $client_email),
                "company" => array(
                    "email" => EMAIL_CONFIG,
                    "sno" => $this->sno,
                    "inn" => $this->inn,
                    "payment_address" => BASE_PATH_HREF
                ),
              
                "payments" => array(array(
                    "type" => self::PAYMENT_TYPE,
                    "sum" => $this->parse_number($info["summ"])
                )),
                "total" => $this->parse_number($info["summ"]),
            ),
        );

        if (!empty($info['tax'])) {
            $request["receipt"]['vats'] = array(array("type" => $this->vat, "sum" => $this->parse_number($info['tax'])));
        }



        foreach ($info['rows'] as $row) {
            $item = array(
                "name" => $row['name'],
                "price" => $this->parse_number($row['price']),
                "quantity" => intval($row['count']),
                "sum" => $this->parse_number($row['summ'])
            );

            $request["receipt"]["items"][] = $item;
        }

        try {
            $http = $this->request($operation, $request);
            $response = json_decode($http->body(), true);

            if (!$this->error($response)) {
                return $response['uuid'];
            }
        } catch (DHttpRequestException $ex) {
            throw new AtolonlineException($ex->getMessage(), $ex->getCode());
        }
    }

}

class AtolonlineException extends Exception {
    
}
