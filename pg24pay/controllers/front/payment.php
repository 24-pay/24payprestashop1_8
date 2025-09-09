<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of payment
 *
 * @author 24-pay
 */

include_once 'modules/pg24pay/core/pg24pay_order.php';
include_once 'modules/pg24pay/core/pg24pay_order_from_order.php';

class Pg24payPaymentModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
       
        $order = "";
        
        if (isset($_GET['from_order'])){
			
            $orderId = $_GET['from_order'];
            $order = $this->paymentFromOrder($orderId);
        }
        else{
			
            $order = $this->paymentFromCart();
        }
        
        if (Configuration::get('PAY24_DEBUG')==1){
            $action_url = "https://test.24-pay.eu/pay_gate/paygt";
        }
        else{
            $action_url = "https://admin.24-pay.eu/pay_gate/paygt";
        }
        
        $this->context->smarty->assign(array(
            'PAY24_ACTION' => $action_url,
            'PAY24_MID' => $order->mid,
            'PAY24_ESHOPID' => $order->eshopId,
            'PAY24_MSTXNID' => $order->msTxnId,
            'PAY24_AMOUNT' => $order->amount,
            'PAY24_CURRALPHACODE' => $order->currAlphaCode,
            'PAY24_LANGUAGE' => $order->language,
            'PAY24_CLIENTID' => $order->clientId,
            'PAY24_FIRSTNAME' => $order->firstName,
            'PAY24_FAMILYNAME' => $order->familyName,
            'PAY24_EMAIL' => $order->email,
            'PAY24_COUNTRY' => $order->country,
            'PAY24_NURL' => $order->nurl,
            'PAY24_RURL' => $order->rurl,
            'PAY24_TIMESTAMP' => $order->timestamp,
            'PAY24_SIGN' => $order->sign,
            'PAY24_NOTIFY' => Configuration::get('PAY24_NOTIFY'),
            'PAY24_NOTIFY_EMAIL' => Configuration::get('PAY24_NOTIFY_EMAIL'),
            'PAY24_NOTIFY_CLIENT' => Configuration::get('PAY24_NOTIFY_CLIENT'),
            'PAY24_SAVE_TRANSACTION_EMAIL' => Configuration::get('PAY24_SAVE_TRANSACTION_EMAIL'),
            'PAY24_CART' => Configuration::get('PAY24_CART'),
            'PAY24_CART_CONTENT' => $this->get_cart_json_base64($this->context->cart),
            'PAY24_MODULE_DEBUG' => Configuration::get('PAY24_MODULE_DEBUG'),
        ));
        
        $this->setTemplate('module:pg24pay/views/templates/front/payment.tpl');
    }
    
    private function paymentFromCart(){
		
        $order = new Pg24payOrder($this->context->cart, $this->context->link);
        $order->signRequest();
        return $order;
    }
    
    private function paymentFromOrder($orderId){
        $order = new Pg24payOrderFromOrder($orderId, $this->context->link);
        $order->signRequest();
        return $order;
    }

    private function get_cart_json_base64($cart)
    {
        if (!$cart || !($cart instanceof Cart)) {
            return null;
        }

        $deliveryName = '';
        $deliveryPrice = 0;

        // Carrier
        $carrier = new Carrier($cart->id_carrier, $this->context->language->id);
        if (Validate::isLoadedObject($carrier)) {
            $deliveryName  = $carrier->name;
            $deliveryPrice = $cart->getOrderTotal(true, Cart::ONLY_SHIPPING);
        }

        $items = [];
        $products = $cart->getProducts();

        foreach ($products as $product) {
            $items[] = [
                'itemName'        => $product['name'],
                'itemDescription' => isset($product['description_short']) ? strip_tags($product['description_short']) : '',
                'quantity'        => (int) $product['cart_quantity'],
                'itemPrice'       => number_format(
                    (float) $product['price_wt'],
                    2,
                    '.',
                    ''
                ),
            ];
        }

        $data = [
            'deliveryName'  => $deliveryName,
            'deliveryPrice' => number_format((float) $deliveryPrice, 2, '.', ''),
            'items'         => $items,
        ];

        return base64_encode(json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}