<?php

namespace PayOnline;

/**
 * Работа с платежной страницей payonline
 */
class PaymentPage {

    const BASE_URL = "https://secure.payonlinesystem.com";

    protected $language = 'en';

    protected $paymentMethod;

    protected $merchantId;

    protected $secretKey;

    protected $orderId;

    protected $amount;

    protected $currency;

    protected $validUntil;

    protected $orderDescription;

    protected $returnUrl;

    protected $failUrl;

    protected $data = [];

    public function setLanguage(Language $language) {
        $this->language = $language;
    }

    public function setPaymentMethod(PaymentMethod $method) {
        $this->paymentMethod = $method;
    }

    /**
     *
     * @param int $merchantId ID мерчанта
     * @param string $secretKey Приватный ключ
     */
    public function __construct($merchantId, $secretKey) {
        $this->merchantId = $merchantId;
        $this->secretKey = $secretKey;
    }

    /**
     * @param string|int $id ID заказа в нумерации магазина
     */
    public function setOrderId($id) {
        $this->orderId = $id;
    }

    /**
     * Установка цены
     * @param Amount $amount сумма
     * @param Currency $currency валюта
     */
    public function setPrice(Amount $amount, Currency $currency) {
        $this->amount = $amount;
        $this->currency = $currency;
    }

    /**
     * Установка описания заказа
     * @param string $string
     */
    public function setDescription(string $string) {
        $this->orderDescription = $string;
    }

    /**
     * Установка адреса перенаправления после завершения платежа
     * @param string $url абсолютный URL
     */
    public function setReturnUrl($url) {
        $this->returnUrl = $url;
    }

    /**
     * Установка адреса перенаправления в случае невозможности выполнить платеж
     * @param string $url абсолютный URL
     */
    public function setFailUrl($url) {
        $this->failUrl = $url;
    }

    /**
     * Установка крайнего срока оплаты счета (UTC+0)
     * @param \DateTime $dateTime
     */
    public function setValidDate(\DateTime $dateTime) {
        $this->validUntil = $dateTime->format('Y-m-d H:i:s');
    }

    /**
     * Установка доп данных
     * @param string $name Имя
     * @param string $value Значение
     */
    public function setData($name, $value) {
        $this->data[$name] = $value;
    }

        /**
     * Получение URL платежной формы
     * @return string
     */
    public function getPaymentUrl() {
        $data = $this->getParameters();
        return $this->getBasePaymentUrl()."?".http_build_query($data);
    }

    /**
     * Получение базового URL платежной формы, на который будут передваться параметры
     * @return string
     */
    public function getBasePaymentUrl() {
        $paymentMethod = empty($this->paymentMethod) ? new PaymentMethod(NULL) : $this->paymentMethod;
        return self::BASE_URL."/{$this->language}/payment/".$paymentMethod->getPaymentUrlSegment();
    }

    /**
     * Получение массива с параметрами для платежной формы
     * @return array
     */
    public function getParameters() {
        $data = [
            'MerchantId' => $this->merchantId,
            'OrderId' => $this->orderId,
            'Amount' => (string) $this->amount,
            'Currency' => (string) $this->currency
        ];

        if(!empty($this->validUntil)) {
            $data['ValidUntil'] = $this->validUntil;
        }

        if(!empty($this->orderDescription)) {
            $data['OrderDescription'] = $this->orderDescription;
        }

        if(!empty($this->returnUrl)) {
            $data['ReturnUrl'] = $this->returnUrl;
        }

        if(!empty($this->failUrl)) {
            $data['FailUrl'] = $this->failUrl;
        }

        if(!empty($this->data)) {
            $data = array_merge($data, $this->data);
        }

        $data['SecurityKey'] = $this->getSignature($data);
        return $data;
    }

    /**
     * Вычисление подписи
     * @param $data
     * @return string
     */
    protected function getSignature($data) {
        $fields = [
            'MerchantId',
            'OrderId',
            'Amount',
            'Currency',
            'ValidUntil',
            'OrderDescription'
        ];
        $string = '';
        foreach ($fields as $fieldName) {
            if(!empty($data[$fieldName])) {
                $string .= "{$fieldName}=".$data[$fieldName].'&';
            }
        }
        return md5($string."PrivateSecurityKey={$this->secretKey}");
    }
}
