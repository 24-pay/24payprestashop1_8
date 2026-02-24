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



class Pg24payNurlModuleFrontController extends ModuleFrontController
{

    public function initContent()
    {
        parent::initContent();

        if (isset($_POST['params'])){

            include_once 'modules/pg24pay/core/pg24pay_nurl.php';
            $nurl = new Pg24payNurl($_POST['params']);
            $pendingStatus = Configuration::get('PAY24_PENDING');
            $failStatus = Configuration::get('PAY24_FAIL');
            $cartId = $nurl->get24Id();
            $orderId = Order::getOrderByCartId((int)($cartId));
            $orderStatus = null;
            $orderObj = null;

            if($orderId != null)
            {
                $orderObj = new Order($orderId);
                $orderStatus = $orderObj->current_state;
            }

            if (Configuration::get('PAY24_LOG')=="1"){
                $income = "PAYMENT FROM CART ".$cartId."\n\r".print_r($_POST,true);
                Logger::addLog("pg24pay: " . $income, 1, null, "Cart", $cartId);
            }

            if ($nurl->validateSign()){

                // order already exists
                if ($orderId != null){

                    $income = "PAYMENT FROM CART ".$cartId."\n\r".print_r($_POST,true);
                    $income .= "\n\r\n\r\n\r INFORMATIONS ORDER ID: ".$orderId." STATUS: ".$orderStatus;

                    Logger::addLog("pg24pay: " . $income, 1, null, "Order", $orderId);

                    // Update order status if it's pending'
                    if($orderStatus == $pendingStatus || $orderStatus == $failStatus){

                        if ($nurl->result=="OK"){
                            if($orderObj == null){
                                $orderObj = new Order($orderId);
                            }
                            $orderObj->setInvoice(true);

                            $history = new OrderHistory();
                            $history->id_order = (int)$orderId;
                            $history->changeIdOrderState(Configuration::get('PAY24_OK'), (int)($orderId));
                            $history->addWithemail(true);
                        }
                        else if($nurl->result=="FAIL"){
                            $history = new OrderHistory();
                            $history->id_order = (int)$orderId;
                            $history->changeIdOrderState(Configuration::get('PAY24_FAIL'), $orderId);
                            $history->addWithemail(true);
                        }

                        echo "OK"; // Confirm that payment was successful
                    }
                    else{
                        // Order already processed
                        Logger::addLog("pg24pay: Order already processed, cartId: ".$cartId.", orderId: ".$orderId.", status: ".$orderStatus, 1, null, "Order", $orderId);
                        echo "OK"; // Stále odpovedz OK, aby platobný systém neopakoval notifikáciu
                    }
                }
                // Order doesn't exist'
                else{

                    if ($nurl->result=="OK" || $nurl->result=="PENDING"){
                        $order_id = $this->confirmOrder($cartId,$nurl->result);
                        Logger::addLog("pg24pay: New order created, cartId: ".$cartId.", orderId: ".$order_id.", result: ".$nurl->result, 1, null, "Order", $order_id);
                        echo "OK";
                    }
                    else if ($nurl->result=="FAIL" && Configuration::get('PAY24_REPAY')==1){
                        $order_id = $this->confirmOrder($cartId,$nurl->result);
                        Logger::addLog("pg24pay: Failed order created for repay, cartId: ".$cartId.", orderId: ".$order_id, 1, null, "Order", $order_id);
                        echo "OK";
                    }
                    else{
                        echo "PAYMENT FAILED";
                    }
                }
            }
            else{
                echo "BAD NURL SIGN!";
                Logger::addLog("pg24pay: Invalid signature, cartId: ".$cartId, 3, null, "Cart", $cartId);
            }
        }
        else{
            echo "NO POST!";
        }

        $this->setTemplate('module:pg24pay/views/templates/front/empty.tpl');
    }
	
	private function confirmOrder($cartId, $result){
		
		$cart = new Cart($cartId);
		if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active)
			Tools::redirect('index.php?controller=order&step=1');

		// Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
		$authorized = false;
		foreach (Module::getPaymentModules() as $module)
			if ($module['name'] == 'pg24pay')
			{
				$authorized = true;
				break;
			}
			
		$customer = new Customer($cart->id_customer);
		if (!Validate::isLoadedObject($customer))
			Tools::redirect('index.php?controller=order&step=1');
        $context = Context::getContext();

        $currency = new Currency($cart->id_currency);
        $context->cart = $cart;
        $context->customer = $customer;
        $context->currency = $currency;
        $context->language = new Language((int)$cart->id_lang);
        $context->shop = new Shop((int)$cart->id_shop);

        $total = (float)$cart->getOrderTotal(true, Cart::BOTH, null, null, false);
		$mailVars = array(
			'{bankwire_owner}' => Configuration::get('BANK_WIRE_OWNER'),
			'{bankwire_details}' => nl2br(Configuration::get('BANK_WIRE_DETAILS')),
			'{bankwire_address}' => nl2br(Configuration::get('BANK_WIRE_ADDRESS'))
		);
		
		if ($result == "OK")
			$this->module->validateOrder($cart->id, Configuration::get('PAY24_OK'), $total, $this->module->displayName, NULL, NULL, (int)$currency->id, false, $customer->secure_key);
		else if ($result == "FAIL"){
			$this->module->validateOrder($cart->id, Configuration::get('PAY24_FAIL'), $total, $this->module->displayName, NULL, $mailVars, (int)$currency->id, false, $customer->secure_key);
		}
		else if ($result == "PENDING")
			$this->module->validateOrder($cart->id, Configuration::get('PAY24_PENDING'), $total, $this->module->displayName, NULL, $mailVars, (int)$currency->id, false, $customer->secure_key);
		
		return $this->module->currentOrder;
	}
	
}