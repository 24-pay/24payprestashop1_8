<?php
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Pg24pay extends PaymentModule
{

    public function isUsingNewTranslationSystem()
    {
        return true;
    }

    public function __construct()
    {
        $this->name = 'pg24pay';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.2';
        $this->ps_versions_compliancy = array('min' => '1.7.6', 'max' => _PS_VERSION_);
        $this->author = '24pay';
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('24pay', [], 'Modules.Pg24pay.Pg24pay');
        $this->description = $this->trans('24pay official payment module', [], 'Modules.Pg24pay.Pg24pay');
        $this->confirmUninstall = $this->trans('Are you sure you want to delete your details?', [], 'Modules.Pg24pay.Pg24pay');


    }

    public function install(){
        try {

            if (!parent::install() || !$this->registerHook('payment') || !$this->registerHook('paymentOptions')
            || !$this->registerHook('displayRightColumn') || !$this->registerHook('displayLeftColumn') || !$this->registerHook('displayFooter')) {
                return false;
            }

            include_once _PS_MODULE_DIR_.$this->name.'/core/pg24pay_install.php';
            $pay24_install = new Pg24payInstall();
            $pay24_install->install();
            Logger::addLog("Install module pg24pay OK " , 1, null, null, null);

            return true;
        } catch (Exception $e) {
            echo 'Install module pg24pay caught exception: ',  $e->getMessage(), "\n";
            Logger::addLog("Install caught exception:: " . $e->getMessage(), 3, null, null, null);
            die();
        }
    }

    public function uninstall() {
        include_once _PS_MODULE_DIR_.$this->name.'/core/pg24pay_install.php';
        $pay24_install = new Pg24payInstall();
        $pay24_install->uninstall();
        return parent::uninstall();
    }

    public function hookDisplayRightColumn($params){
        return $this->fetchTemplate("right.tpl");
    }

    public function hookDisplayLeftColumn($params){
        return $this->fetchTemplate("left.tpl");
    }

    public function hookDisplayFooter($params){
        return $this->fetchTemplate("foot.tpl");
    }

	public function hookPaymentOptions()
	{
		    if (!$this->active) {
            return;
        }

        $newOption = new PaymentOption();
        $newOption->setModuleName($this->name)
        ->setCallToActionText($this->trans('Pay by 24pay', [], 'Modules.Pg24pay.Pg24pay'))
                ->setAction($this->context->link->getModuleLink($this->name, 'payment', array(), true))
                // ->setLogo(_MODULE_DIR_.'pg24pay/views/img/logo.png')
                ->setAdditionalInformation($this->trans('Pay by card or internet banking', [], 'Modules.Pg24pay.Pg24pay'));

        $paymentOptions = [$newOption];
        if(Configuration::get('PAY24_SHOW_GOOGLE_PAY') == "1" ) {
            $googlePay = new PaymentOption();
            $googlePay->setModuleName($this->name)
                ->setCallToActionText($this->trans('Pay by Google Pay', [], 'Modules.Pg24pay.Pg24pay'))
                ->setAction($this->context->link->getModuleLink($this->name, 'paymentGoogle', array(), true));
            $paymentOptions[] = $googlePay;
        }

        if(Configuration::get('PAY24_SHOW_APPLE_PAY') == "1" ) {
            $applePay = new PaymentOption();
            $applePay->setModuleName($this->name)
                ->setCallToActionText($this->trans('Pay by Apple Pay', [], 'Modules.Pg24pay.Pg24pay'))
                ->setAction($this->context->link->getModuleLink($this->name, 'paymentApple', array(), true));
            $paymentOptions[] = $applePay;
        }
        return $paymentOptions;
	}

    public function hookPayment($params) {

            if (!$this->active)
                return;

            $this->smarty->assign(array(
                    'this_path' => $this->_path,
                    'this_path_cheque' => $this->_path,
                    'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/'
            ));

            return $this->fetchTemplate('module:pg24pay/views/templates/front/payment.tpl');
    }

    public function getContent(){
		if (isset($_GET['action'])){
			if ($_GET['action']=='test'){
				$this->runTest();
			}
		}
		else{
			$this->context->smarty->assign(array(
				'PAY24_TEST' => '0',
			));
		}

        $this->baseConfig();

	    $output = $this->context->smarty->fetch('module:pg24pay/views/templates/admin/backoffice.tpl');

        if ($this->active == false) {
            return $output;
        }

        return $output;
    }

    private function baseConfig(){
        if (isset($_POST['submit24pay'])){
            if ($_POST['submit24pay']=="pg24pay_configuration"){
                Configuration::updateValue('PAY24_DEBUG', $_POST['PAY24_DEBUG']);
                Configuration::updateValue('PAY24_MID', $_POST['PAY24_MID']);
                Configuration::updateValue('PAY24_ESHOP_ID', $_POST['PAY24_ESHOP_ID']);
                Configuration::updateValue('PAY24_KEY', $_POST['PAY24_KEY']);
                Configuration::updateValue('PAY24_NOTIFY_CLIENT', $_POST['PAY24_NOTIFY_CLIENT']);
                Configuration::updateValue('PAY24_SHOW_GOOGLE_PAY', $_POST['PAY24_SHOW_GOOGLE_PAY']);
                Configuration::updateValue('PAY24_SHOW_APPLE_PAY', $_POST['PAY24_SHOW_APPLE_PAY']);
                Configuration::updateValue('PAY24_NOTIFY', $_POST['PAY24_NOTIFY']);
                Configuration::updateValue('PAY24_NOTIFY_EMAIL', $_POST['PAY24_NOTIFY_EMAIL']);
                Configuration::updateValue('PAY24_MODULE_DEBUG', $_POST['PAY24_MODULE_DEBUG']);
                Configuration::updateValue('PAY24_REPAY', $_POST['PAY24_REPAY']);
                Configuration::updateValue('PAY24_LOG', $_POST['PAY24_LOG']);
            }
        }

        $url  = 'index.php?controller=AdminModules&configure=pg24pay';
        $url .= '&token='.Tools::getAdminTokenLite('AdminModules');

        $this->context->smarty->assign(array(
            'PAY24_DEBUG' => Configuration::get('PAY24_DEBUG'),
            'PAY24_MID' => Configuration::get('PAY24_MID'),
            'PAY24_ESHOP_ID' => Configuration::get('PAY24_ESHOP_ID'),
            'PAY24_KEY' => Configuration::get('PAY24_KEY'),
            'PAY24_NOTIFY_CLIENT' => Configuration::get('PAY24_NOTIFY_CLIENT'),
            'PAY24_NOTIFY' => Configuration::get('PAY24_NOTIFY'),
            'PAY24_SHOW_GOOGLE_PAY' => Configuration::get('PAY24_SHOW_GOOGLE_PAY'),
            'PAY24_SHOW_APPLE_PAY' => Configuration::get('PAY24_SHOW_APPLE_PAY'),
            'PAY24_NOTIFY_EMAIL' => Configuration::get('PAY24_NOTIFY_EMAIL'),
            'PAY24_MODULE_DEBUG' => Configuration::get('PAY24_MODULE_DEBUG'),
            'PAY24_REPAY' => Configuration::get('PAY24_REPAY'),
            'PAY24_LOG' => Configuration::get('PAY24_LOG'),

            'PAY24_CONFIG_LINK' => $url,
        ));
    }

    private function runTest(){
        include_once _PS_MODULE_DIR_.$this->name.'/core/pg24pay_request.php';
            $requester = new Pg24payRequest();
            $signResult = $requester->checkSignGeneration();
            $gatesResult = $requester->checkAvailableGateways();
            $this->context->smarty->assign(array(
                'PAY24_TEST'=>'1',
                'PAY24_SIGN_RESULT'=>$signResult,
                'PAY24_GATES_RESULT'=>$gatesResult,
            ));
    }

    public function fetchTemplate($name)
    {
        $views = 'views/templates/';
        if (@filemtime(dirname(__FILE__).'/'.$name)) {
            return $this->display(__FILE__, $name);
        } elseif (@filemtime(dirname(__FILE__).'/'.$views.'hook/'.$name)) {
            return $this->display(__FILE__, $views.'hook/'.$name);
        } elseif (@filemtime(dirname(__FILE__).'/'.$views.'front/'.$name)) {
            return $this->display(__FILE__, $views.'front/'.$name);
        } elseif (@filemtime(dirname(__FILE__).'/'.$views.'admin/'.$name)) {
            return $this->display(__FILE__, $views.'admin/'.$name);
        }
        else
            return $this->display(__FILE__, $name);
    }
}