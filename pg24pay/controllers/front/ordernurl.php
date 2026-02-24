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


include_once 'modules/pg24pay/core/pg24pay_nurl.php';

class Pg24payOrdernurlModuleFrontController extends ModuleFrontController
{
    
    public function initContent()
    {
        parent::initContent();
      
        if (isset($_POST['params'])){
            
            $nurl = new Pg24payNurl($_POST['params']);
            $cartId = $nurl->get24Id();
            $orderId = Order::getOrderByCartId((int)($cartId));
            
            if (Configuration::get('PAY24_LOG')=="1"){
                $income = "PAYMENT BY ORDER ". $orderId ." \n\r".print_r($_POST,true);
                Logger::addLog("pg24pay: " . $income, 1, null, "Order", $orderId);
            }

            if ($nurl->validateSign()){
                if ($nurl->result=="OK"){

					$orderObj = new Order($orderId);
					$orderObj->setInvoice(true);
					
                    $history = new OrderHistory();
                    $history->id_order = (int)$orderId;
                    $history->changeIdOrderState(Configuration::get('PAY24_OK'), (int)($orderId));
                    $history->addWithemail(true);
                    
                }
                else if ($nurl->result=="PENDING"){
                    $history = new OrderHistory();
                    $history->id_order = (int)$orderId;
                    $history->changeIdOrderState(Configuration::get('PAY24_PENDING'), (int)($orderId));
                    $history->add();
                    
                }
                else if ($nurl->result=="FAIL"){
                    $history = new OrderHistory();
                    $history->id_order = (int)$orderId;
                    $history->changeIdOrderState(Configuration::get('PAY24_FAIL'), (int)($orderId));
                    $history->add();
                    
                }
            }
            else{
                echo "BAD NURL SIGN!";
            }
        }
        else{
            echo "NO POST!";
        }
    }

}