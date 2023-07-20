<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Pg24payInstall
 *
 * @author 24-pay
 */

class Pg24payInstall{

    private function insertParams(){
        Configuration::updateValue('PAY24_DEBUG', '1');
        Configuration::updateValue('PAY24_MID', 'demoOMED');
        Configuration::updateValue('PAY24_KEY', '1234567812345678123456781234567812345678123456781234567812345678');
        Configuration::updateValue('PAY24_NOTIFY', '0');
        Configuration::updateValue('PAY24_NOTIFY_EMAIL', '');
        Configuration::updateValue('PAY24_NOTIFY_CLIENT', '0');
        Configuration::updateValue('PAY24_SHOW_GOOGLE_PAY', '0');
        Configuration::updateValue('PAY24_SHOW_APPLE_PAY', '0');
        Configuration::updateValue('PAY24_ESHOP_ID', '11111111');
        Configuration::updateValue('PAY24_MODULE_DEBUG', '0');
        Configuration::updateValue('PAY24_REPAY', '1');
        Configuration::updateValue('PAY24_LOG', '1');
    }

    private function deleteParams(){
        Configuration::deleteByName('PAY24_DEBUG');
        Configuration::deleteByName('PAY24_MID');
        Configuration::deleteByName('PAY24_KEY');
        Configuration::deleteByName('PAY24_NOTIFY');
        Configuration::deleteByName('PAY24_SHOW_GOOGLE_PAY');
        Configuration::deleteByName('PAY24_SHOW_APPLE_PAY');
        Configuration::deleteByName('PAY24_NOTIFY_EMAIL');
        Configuration::deleteByName('PAY24_NOTIFY_CLIENT');
        Configuration::deleteByName('PAY24_ESHOP_ID');
        Configuration::deleteByName('PAY24_MODULE_DEBUG');
        Configuration::deleteByName('PAY24_REPAY');
        Configuration::deleteByName('PAY24_LOG');

    }

    public function install(){
        $this->insertParams();
        $this->insertOrderState();
    }

    public function uninstall(){
        $this->deleteParams();
    }

    public function insertOrderState(){
        // OK
        if (!Configuration::get('PAY24_OK')) {
            $OK_state = new OrderState();
            $OK_state->name = array();

            foreach (Language::getLanguages() as $language) {
                if (Tools::strtolower($language['iso_code']) == 'sk') {
                    $OK_state->name[$language['id_lang']] = '24Pay - Platba OK';
                } else {
                    $OK_state->name[$language['id_lang']] = '24Pay - Payment OK';
                }
            }
            $OK_state->module_name = "pg24pay";
            $OK_state->send_email = true;
            $OK_state->color = '#00CE52';
            $OK_state->hidden = false;
            $OK_state->delivery = false;
            $OK_state->logable = true;
            $OK_state->invoice = true;

            if ($OK_state->add()) {
                // ICON
                $this->copyIcon($OK_state->id);
                Configuration::updateValue('PAY24_OK',$OK_state->id);
            }
        }



        // FAIL
        if (!Configuration::get('PAY24_FAIL')) {
            $FAIL_state = new OrderState();
            $FAIL_state->name = array();

            foreach (Language::getLanguages() as $language) {
                if (Tools::strtolower($language['iso_code']) == 'sk') {
                    $FAIL_state->name[$language['id_lang']] = '24Pay - Platba NEÚSPEŠNÁ';
                } else {
                    $FAIL_state->name[$language['id_lang']] = '24Pay - Payment FAIL';
                }
            }
            $FAIL_state->module_name = "pg24pay";
            $FAIL_state->send_email = false;
            $FAIL_state->color = '#FF0000';
            $FAIL_state->hidden = false;
            $FAIL_state->delivery = false;
            $FAIL_state->logable = true;
            $FAIL_state->invoice = false;

            if ($FAIL_state->add()) {
                // ICON
                $this->copyIcon($FAIL_state->id);
                Configuration::updateValue('PAY24_FAIL',$FAIL_state->id);
            }
        }



        // PENDING
        if (!Configuration::get('PAY24_PENDING')) {
            $PENDING_state = new OrderState();
            $PENDING_state->name = array();

            foreach (Language::getLanguages() as $language) {
                if (Tools::strtolower($language['iso_code']) == 'sk') {
                    $PENDING_state->name[$language['id_lang']] = '24Pay - Platba ČAKAJÚCA';
                } else {
                    $PENDING_state->name[$language['id_lang']] = '24Pay - Payment PENDING';
                }
            }
            $PENDING_state->module_name = "pg24pay";
            $PENDING_state->send_email = false;
            $PENDING_state->color = '#FCC79F';
            $PENDING_state->hidden = false;
            $PENDING_state->delivery = false;
            $PENDING_state->logable = true;
            $PENDING_state->invoice = false;

            if ($PENDING_state->add()) {
                // ICON
                $this->copyIcon($PENDING_state->id);
                Configuration::updateValue('PAY24_PENDING',$PENDING_state->id);
            }
        }
    }

    public function copyIcon($stateId) {
        $twentyfourpayIcon = _PS_MODULE_DIR_.'pg24pay/logo.png';
        $newStateIcon = _PS_MODULE_DIR_.'pg24pay/../../img/os/' . (int) $stateId . '.gif';
        copy( $twentyfourpayIcon, $newStateIcon );
    }

}