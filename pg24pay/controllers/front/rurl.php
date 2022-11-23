<?php

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


class Pg24payRurlModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        
        $orderId = null;
        $orderRef = null;
        
        if (isset($_GET['MsTxnId'])){
            $cartId = $_GET['MsTxnId'];
            $orderId = Order::getOrderByCartId((int)($cartId));

			
            if ($orderId!=null){
                $order = new Order($orderId);
                $orderRef = $order->reference;
            }   
        }
        
        $this->context->smarty->assign(array(
            'PAY24_TEMP' => $_GET,
            'PAY24_REPAY' => Configuration::get('PAY24_REPAY'),
            'PAY24_ORDER' => $orderId,
            'PAY24_ORDER_REF' => $orderRef,

        ));
        
        $this->setTemplate('module:pg24pay/views/templates/front/rurl.tpl');
	}	
	

}