<?php

class ControllerPaymentWayforpay extends Controller
{
    public function index()
    {
        $w4p = new WayForPay();
        $key = $this->config->get('wayforpay_secretkey');
        $w4p->setSecretKey($key);

        $order_id = $this->session->data['order_id'];

        $this->load->model('checkout/order');
        $order = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $serviceUrl = $this->config->get('wayforpay_serviceUrl');
        $returnUrl = $this->config->get('wayforpay_returnUrl');

        $amount = $this->currency->format(
            $order['total'],
            $order['currency_code'],
            $order['currency_value'],
            false
        );

        $fields = array(
            'orderReference'                => $order_id . WayForPay::ORDER_SEPARATOR . time(),
            'merchantAccount'               => $this->config->get('wayforpay_merchant'),
            'orderDate'                     => strtotime($order['date_added']),
            'merchantAuthType'              => 'simpleSignature',
            'merchantDomainName'            => $_SERVER['HTTP_HOST'],
            'merchantTransactionSecureType' => 'AUTO',
            'amount'                        => round($amount, 2),
            'currency'                      => $order['currency_code'],
            'serviceUrl'                    => $serviceUrl,
            'returnUrl'                     => $returnUrl,
            'language'                      => $this->config->get('wayforpay_language')
        );

        $productNames = array();
        $productQty = array();
        $productPrices = array();
        $this->load->model('account/order');
        $products = $this->model_account_order->getOrderProducts($order_id);
        foreach ($products as $product) {
            $productNames[] = str_replace(array("'", '"', '&#39;', '&'), '', htmlspecialchars_decode($product['name']));
            $productPrices[] = round($this->currency->format(
                $product['price'],
                $order['currency_code'],
                $order['currency_value'],
                false
            ), 2);
            $productQty[] = $product['quantity'];
        }

        $fields['productName'] = $productNames;
        $fields['productPrice'] = $productPrices;
        $fields['productCount'] = $productQty;

        /**
         * Check phone
         */
        $phone = str_replace(array('+', ' ', '(', ')'), array('', '', '', ''), $order['telephone']);
        if (strlen($phone) == 10) {
            $phone = '38' . $phone;
        } elseif (strlen($phone) == 11) {
            $phone = '3' . $phone;
        }

        $fields['clientFirstName'] = $order['payment_firstname'];
        $fields['clientLastName'] = $order['payment_lastname'];
        $fields['clientEmail'] = $order['email'];
        $fields['clientPhone'] = $phone;
        $fields['clientCity'] = $order['payment_city'];
        $fields['clientAddress'] = $order['payment_address_1'] . ' ' . $order['payment_address_2'];
        $fields['clientCountry'] = $order['payment_iso_code_3'];

        $fields['merchantSignature'] = $w4p->getRequestSignature($fields);

        $this->data['fields'] = $fields;
        $this->data['action'] = WayForPay::URL;
        $this->data['button_confirm'] = $this->language->get('button_confirm');
        $this->data['continue'] = $this->url->link('checkout/success');


        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/wayforpay.tpl')) {
            $this->template = $this->config->get('config_template') . '/template/payment/wayforpay.tpl';
        } else {
            $this->template = 'default/template/payment/wayforpay.tpl';
        }

        $this->render();

    }

    public function confirm()
    {

        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        if (!$order_info) return;

        $order_id = $this->session->data['order_id'];

        if ($order_info['order_status_id'] == 0) {
            $this->model_checkout_order->confirm($order_id, $this->config->get('wayforpay_order_status_progress_id'), 'WayForPay');

            return;
        }

        if ($order_info['order_status_id'] != $this->config->get('wayforpay_order_status_progress_id')) {
            $this->model_checkout_order->update($order_id, $this->config->get('wayforpay_order_status_progress_id'), 'WayForPay', true);
        }
    }

    public function response()
    {

        $w4p = new WayForPay();
        $key = $this->config->get('wayforpay_secretkey');
        $w4p->setSecretKey($key);

        $paymentInfo = $w4p->isPaymentValid($_POST);

        if ($paymentInfo === true) {
            list($order_id,) = explode(WayForPay::ORDER_SEPARATOR, $_POST['orderReference']);

            $message = '';

            $this->load->model('checkout/order');

            /**
             * check current order status if no eq wayforpay_order_status_id then confirm
             */
            $orderInfo = $this->model_checkout_order->getOrder($order_id);
            if (
                $orderInfo &&
                $orderInfo['order_status_id'] == $this->config->get('wayforpay_order_status_id')
            ) {
                //nothing
            } else {
                $this->model_checkout_order->confirm($order_id, $this->config->get('wayforpay_order_status_id'));
            }
            
            $this->redirect($this->url->link('checkout/success'));
        } else {

            $this->document->setTitle('Pay via WayForPay');
            $this->data['heading_title'] = 'Payment failed';
            $this->data['text_payment_failed'] = $paymentInfo;

            if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/wayforpay_failure.tpl')) {
                $this->template = $this->config->get('config_template') . '/template/payment/wayforpay_failure.tpl';
            } else {
                $this->template = 'default/template/payment/wayforpay_failure.tpl';
            }

            $this->children = array(
                'common/column_left',
                'common/column_right',
                'common/content_top',
                'common/content_bottom',
                'common/footer',
                'common/header'
            );
            $this->response->setOutput($this->render(true));
        }
    }

    public function callback()
    {

        $data = json_decode(file_get_contents("php://input"), true);

        $w4p = new WayForPay();
        $key = $this->config->get('wayforpay_secretkey');
        $w4p->setSecretKey($key);

        $paymentInfo = $w4p->isPaymentValid($data);

        if ($paymentInfo === true) {
            list($order_id,) = explode(WayForPay::ORDER_SEPARATOR, $data['orderReference']);

            $message = '';

            $this->load->model('checkout/order');

            /**
             * check current order status if no eq wayforpay_order_status_id then confirm
             */
            $orderInfo = $this->model_checkout_order->getOrder($order_id);
            if (
                $orderInfo &&
                $orderInfo['order_status_id'] == $this->config->get('wayforpay_order_status_id')
            ) {
                //nothing
            } else {
                $this->model_checkout_order->confirm($order_id, $this->config->get('wayforpay_order_status_id'));
            }
            $this->model_checkout_order->confirm($order_id, $this->config->get('wayforpay_order_status_id'));

            echo $w4p->getAnswerToGateWay($data);
        } else {
            echo $paymentInfo;
        }
        exit();
    }
}

class WayForPay
{
    const ORDER_APPROVED = 'Approved';
    const ORDER_HOLD_APPROVED = 'WaitingAuthComplete';

    const ORDER_SEPARATOR = '#';

    const SIGNATURE_SEPARATOR = ';';

    const URL = "https://secure.wayforpay.com/pay/";

    protected $secret_key = '';
    protected $keysForResponseSignature = array(
        'merchantAccount',
        'orderReference',
        'amount',
        'currency',
        'authCode',
        'cardPan',
        'transactionStatus',
        'reasonCode'
    );

    /** @var array */
    protected $keysForSignature = array(
        'merchantAccount',
        'merchantDomainName',
        'orderReference',
        'orderDate',
        'amount',
        'currency',
        'productName',
        'productCount',
        'productPrice'
    );


    /**
     * @param $option
     * @param $keys
     *
     * @return string
     */
    public function getSignature($option, $keys)
    {
        $hash = array();
        foreach ($keys as $dataKey) {
            if (!isset($option[$dataKey])) {
                continue;
            }
            if (is_array($option[$dataKey])) {
                foreach ($option[$dataKey] as $v) {
                    $hash[] = $v;
                }
            } else {
                $hash [] = $option[$dataKey];
            }
        }

        $hash = implode(self::SIGNATURE_SEPARATOR, $hash);

        return hash_hmac('md5', $hash, $this->getSecretKey());
    }


    /**
     * @param $options
     *
     * @return string
     */
    public function getRequestSignature($options)
    {
        return $this->getSignature($options, $this->keysForSignature);
    }

    /**
     * @param $options
     *
     * @return string
     */
    public function getResponseSignature($options)
    {
        return $this->getSignature($options, $this->keysForResponseSignature);
    }


    /**
     * @param array $data
     *
     * @return string
     */
    public function getAnswerToGateWay($data)
    {
        $time = time();
        $responseToGateway = array(
            'orderReference' => $data['orderReference'],
            'status'         => 'accept',
            'time'           => $time
        );
        $sign = array();
        foreach ($responseToGateway as $dataKey => $dataValue) {
            $sign [] = $dataValue;
        }
        $sign = implode(self::SIGNATURE_SEPARATOR, $sign);
        $sign = hash_hmac('md5', $sign, $this->getSecretKey());
        $responseToGateway['signature'] = $sign;

        return json_encode($responseToGateway);
    }

    /**
     * @param $response
     *
     * @return bool|string
     */
    public function isPaymentValid($response)
    {

        if (!isset($response['merchantSignature']) && isset($response['reason'])) {
            return $response['reason'];
        }
        $sign = $this->getResponseSignature($response);
        if ($sign != $response['merchantSignature']) {
            return 'An error has occurred during payment';
        }

        if (
            $response['transactionStatus'] == self::ORDER_APPROVED ||
            $response['transactionStatus'] == self::ORDER_HOLD_APPROVED
           ) {
            return true;
        }

        return false;
    }

    public function setSecretKey($key)
    {
        $this->secret_key = $key;
    }

    public function getSecretKey()
    {
        return $this->secret_key;
    }
}
