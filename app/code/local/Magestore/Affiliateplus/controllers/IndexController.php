<?php

class Magestore_Affiliateplus_IndexController extends Mage_Core_Controller_Front_Action {

    /**
     * get Account helper
     *
     * @return Magestore_Affiliateplus_Helper_Account
     */
    protected function _getAccountHelper() {
        return Mage::helper('affiliateplus/account');
    }

    /**
     * get Affiliateplus helper
     *
     * @return Magestore_Affiliateplus_Helper_Data
     */
    protected function _getHelper() {
        return Mage::helper('affiliateplus');
    }

    /**
     * getConfigHelper
     *
     * @return Magestore_Affiliateplus_Helper_Config
     */
    protected function _getConfigHelper() {
        return Mage::helper('affiliateplus/config');
    }

    /**
     * get Affiliate Payment Helper
     *
     * @return Magestore_Affiliateplus_Helper_Payment
     */
    protected function _getPaymentHelper() {
        return Mage::helper('affiliateplus/payment');
    }

    /**
     * get Core Session
     *
     * @return Mage_Core_Model_Session
     */
    protected function _getCoreSession() {
        return Mage::getSingleton('core/session') ;
    }

    public function indexAction() {
        // Changed By Adam 28/07/2014
        if(!Mage::helper('affiliateplus')->isAffiliateModuleEnabled()) {
            return $this->_redirectUrl(Mage::getBaseUrl());
        }
        
        if (!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)) {
            return;
        }
        if ($this->_getAccountHelper()->isRegistered() && $this->_getAccountHelper()->accountNotLogin()) {
            if ($this->_getAccountHelper()->getAccount()->getApproved() == 1)
                $this->_getCoreSession()->addError($this->_getHelper()->__('Your affiliate account is currently disabled. Please contact us to resolve this issue.'));
            elseif (!$this->_getCoreSession()->getData('has_been_signup'))
                $this->_getCoreSession()->addNotice($this->_getHelper()->__('Your affiliate registration is awaiting for approval. Please be patient.'));
        }
        $this->loadLayout();
        $page = Mage::getSingleton('cms/page');
        if ($page->getId())
            $this->getLayout()->getBlock('head')->setTitle($page->getContentHeading());
        else
            $this->getLayout()->getBlock('head')->setTitle(Mage::helper('affiliateplus')->__('Affiliate Home'));
        $this->renderLayout();
    }

    public function materialsAction() {
        // Changed By Adam 28/07/2014
        if(!Mage::helper('affiliateplus')->isAffiliateModuleEnabled()) return $this->_redirectUrl(Mage::getBaseUrl());
        
        if (!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)) {
            return;
        }
        if ($this->_getConfigHelper()->disableMaterials())
            return $this->_redirect('*/*/');
        $this->loadLayout();
        $page = Mage::getSingleton('cms/page');
        if ($page->getId())
            $this->getLayout()->getBlock('head')->setTitle($page->getContentHeading());
        else
            $this->getLayout()->getBlock('head')->setTitle(Mage::helper('affiliateplus')->__('Referring Materials'));
        $this->renderLayout();
    }

    public function listTransactionAction() {
        // Changed By Adam 28/07/2014
        if(!Mage::helper('affiliateplus')->isAffiliateModuleEnabled()) return $this->_redirectUrl(Mage::getBaseUrl());
        
        if (!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)) {
            return;
        }
        if ($this->_getAccountHelper()->accountNotLogin())
            return $this->_redirect('affiliateplus/account/login');
        $this->loadLayout();
        //$this->getLayout()->getBlock('head')->setTitle($this->__('Commissions'));
        $this->renderLayout();
    }

    public function paymentsAction() {
        // Changed By Adam 28/07/2014
        if(!Mage::helper('affiliateplus')->isAffiliateModuleEnabled()) return $this->_redirectUrl(Mage::getBaseUrl());
        
        if (!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)) {
            return;
        }
        if ($this->_getAccountHelper()->accountNotLogin())
            return $this->_redirect('affiliateplus/account/login');
        $this->loadLayout();
        $title = $this->__('Withdrawals');
        if ($this->_getAccountHelper()->disableWithdrawal()) {
            $title = $this->__('Store Credits');
        }
        $this->getLayout()->getBlock('head')->setTitle($title);
        $this->renderLayout();
    }

    public function viewPaymentAction() {
        // Changed By Adam 28/07/2014
        if(!Mage::helper('affiliateplus')->isAffiliateModuleEnabled()) return $this->_redirectUrl(Mage::getBaseUrl());
        
        if (!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)) {
            return;
        }
        if ($this->_getAccountHelper()->accountNotLogin())
            return $this->_redirect('affiliateplus/account/login');

        $paymentId = $this->getRequest()->getParam('id');
        $payment = Mage::getModel('affiliateplus/payment')->load($paymentId);
        if ($payment->getAccountId() != Mage::getSingleton('affiliateplus/session')->getAccount()->getId()) {
            $this->_getCoreSession()->addError($this->__('Withdrawal not found!'));
            return $this->_redirect('affiliateplus/index/payments');
        }
        Mage::register('view_payment_data', $payment);
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setTitle($this->__('View Invoice'));
        $this->renderLayout();
    }

    public function paymentFormAction() {
        // Changed By Adam 28/07/2014
        if(!Mage::helper('affiliateplus')->isAffiliateModuleEnabled()) return $this->_redirectUrl(Mage::getBaseUrl());
        
        if (!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)) {
            return;
        }
        if ($this->getRequest()->getParam('from_request_page')) {
            $account = Mage::getSingleton('affiliateplus/session')->getAccount();
            if (Mage::getModel('affiliateplus/payment')->setAccountId($account->getId())->hasWaitingPayment()) {
                $this->_getCoreSession()->addError($this->__('You are having a pending request!'));
                return $this->_redirect('affiliateplus/index/payments');
            }
        }
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
        }

        $amount = Mage::getSingleton('affiliateplus/session')->getPaymentAmount();
        $payment = Mage::getSingleton('affiliateplus/session')->getPayment();
        if ($amount)
            $payment->setAmount($amount);
        if ($this->_getAccountHelper()->accountNotLogin())
            return $this->_redirect('affiliateplus/account/login');
        if ($this->_getAccountHelper()->disableWithdrawal()) {
            if (!$this->_getAccountHelper()->disableStoreCredit()) {
                return $this->_redirect('affiliateplus/index/payments');
            }
            return $this->_redirect('affiliateplus/index/listTransaction');
        }
        if (!$this->_getAccountHelper()->isEnoughBalance()) {
            $baseCurrency = Mage::app()->getStore()->getBaseCurrency();
            $this->_getCoreSession()->addNotice($this->__('The minimum balance required to request withdrawal is %s'
                            , $baseCurrency->format($this->_getConfigHelper()->getPaymentConfig('payment_release'), array(), false)));
            return $this->_redirect('affiliateplus/index/listTransaction');
        }
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setTitle($this->__('Request Payment'));
        $this->renderLayout();
    }

    public function requestPaymentAction() {
        // Changed By Adam 28/07/2014
        if(!Mage::helper('affiliateplus')->isAffiliateModuleEnabled()) return $this->_redirectUrl(Mage::getBaseUrl());
        
        if (!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)) {
            return;
        }
        if ($this->_getAccountHelper()->accountNotLogin())
            return $this->_redirect('affiliateplus/account/login');
        //if(!$this->getRequest()->isPost())
        //return $this->_redirect('affiliateplus/index/paymentForm');
        if ($this->_getAccountHelper()->disableWithdrawal()) {
            $this->_getCoreSession()->addError($this->__('Request withdrawal not allowed at this time.'));
            if (!$this->_getAccountHelper()->disableStoreCredit()) {
                return $this->_redirect('affiliateplus/index/payments');
            }
            return $this->_redirect('affiliateplus/index/listTransaction');
        }

        $paymentCodes = $this->_getPaymentHelper()->getAvailablePaymentCode();

        if (!count($paymentCodes)) {
            $this->_getCoreSession()->addError($this->__('There is no payment method on file for your account. Please update your details or contact us to solve the problem.'));
            return $this->_redirect('affiliateplus/index/payments');
        } elseif (count($paymentCodes) == 1) {
            $paymentCode = $this->getRequest()->getParam('payment_method');
            if (!$paymentCode)
                $paymentCode = current($paymentCodes);
        } else
            $paymentCode = $this->getRequest()->getParam('payment_method');

        if (!$paymentCode) {
            $this->_getCoreSession()->addNotice($this->__('Please chose an available payment method!'));
            return $this->_redirect('affiliateplus/index/paymentForm', $this->getRequest()->getPost());
        }

        if (!in_array($paymentCode, $paymentCodes)) {
            $this->_getCoreSession()->addError($this->__('This payment method is not available, please choose an alternative payment method.'));
            return $this->_redirect('affiliateplus/index/paymentForm', $this->getRequest()->getPost());
        }
        $account = $this->_getAccountHelper()->getAccount();
        $store = Mage::app()->getStore();

        $amount = $this->getRequest()->getParam('amount');
        $amount = $amount / $store->convertPrice(1);
        if ($amount < $this->_getConfigHelper()->getPaymentConfig('payment_release')) {
            $this->_getCoreSession()->addNotice($this->__('The minimum balance required to request withdrawal is %s'
                            , Mage::helper('core')->currency($this->_getConfigHelper()->getPaymentConfig('payment_release'), true, false)));
            return $this->_redirect('affiliateplus/index/paymentForm');
        }

        if ($amountInclTax = $this->getRequest()->getParam('amount_incl_tax')) {
            if ($amountInclTax > $amount && $amountInclTax > $account->getBalance()) {
                $this->_getCoreSession()->addError($this->__('The withdrawal requested cannot exceed your current balance (%s).'
                                , Mage::helper('core')->currency($account->getBalance(), true, false)));
                return $this->_redirect('affiliateplus/index/paymentForm');
            }
        }
        if ($amount > $account->getBalance()) {
            $this->_getCoreSession()->addError($this->__('The withdrawal requested cannot exceed your current balance (%s).'
                            , Mage::helper('core')->currency($account->getBalance(), true, false)));

            return $this->_redirect('affiliateplus/index/paymentForm');
        }

        $payment = Mage::getModel('affiliateplus/payment')
                ->setPaymentMethod($paymentCode)
                ->setAmount($amount)
                ->setAccountId($account->getId())
                ->setAccountName($account->getName())
                ->setAccountEmail($account->getEmail())
                ->setRequestTime(now())
                ->setStatus(1)
                ->setIsRequest(1)
                ->setIsPayerFee(0);
        if ($this->_getConfigHelper()->getPaymentConfig('who_pay_fees') == 'payer' && $paymentCode == 'paypal')
            $payment->setIsPayerFee(1);

        if ($payment->hasWaitingPayment()) {
            $this->_getCoreSession()->addError($this->__('You are having a pending request!'));
            return $this->_redirect('affiliateplus/index/payments');
        }

        if ($this->_getConfigHelper()->getSharingConfig('balance') == 'store')
            $payment->setStoreIds($store->getId());

        $paymentMethod = $payment->getPayment();

        $paymentObj = new Varien_Object(array(
            'payment_code' => $paymentCode,
            'required' => true,
        ));
        Mage::dispatchEvent("affiliateplus_request_payment_action_$paymentCode", array(
            'payment_obj' => $paymentObj,
            'payment' => $payment,
            'payment_method' => $paymentMethod,
            'request' => $this->getRequest(),
        ));
        $paymentCode = $paymentObj->getPaymentCode();

        if ($paymentCode == 'paypal') {
            $paypalEmail = $this->getRequest()->getParam('paypal_email');

            //Change paypal email for affiliate account
            if ($paypalEmail && $paypalEmail != $account->getPaypalEmail()) {
                $accountModel = Mage::getModel('affiliateplus/account')
                        ->setStoreId($store->getId())
                        ->load($account->getId());
                try {
                    $accountModel->setPaypalEmail($paypalEmail)
                            ->setId($account->getId())
                            ->save();
                } catch (Exception $e) {
                    
                }
            }

            $paypalEmail = $paypalEmail ? $paypalEmail : $account->getPaypalEmail();
            if ($paypalEmail) {
                $paymentMethod->setEmail($paypalEmail);
                $paymentObj->setRequired(false);
            }
        }

        if ($paymentObj->getRequired()) {
            $this->_getCoreSession()->addNotice($this->__('Please fill out all required fields in the form below.'));
            return $this->_redirect('affiliateplus/index/paymentForm', $this->getRequest()->getPost());
        }

        // Save request payment for affiliate account
        try {
            $payment->save();
            $paymentMethod->savePaymentMethodInfo();
            $payment->sendMailRequestPaymentToSales();
            $this->_getCoreSession()->addSuccess($this->__('Your request has been sent to admin for approval.'));
        } catch (Exception $e) {
            $this->_getCoreSession()->addError($e->getMessage());
        }

        return $this->_redirect('affiliateplus/index/payments');
    }

    /**
     * Add by blanka 29/11/2012
     * 
     */
    public function confirmRequestAction() {
        // Changed By Adam 28/07/2014
        if(!Mage::helper('affiliateplus')->isAffiliateModuleEnabled()) return $this->_redirectUrl(Mage::getBaseUrl());
        
        if (!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)) {
            return;
        }

        $session = Mage::getSingleton('affiliateplus/session');
        $account = $session->getAccount();
        $params = $this->getRequest()->getPost();
        if (!count($params))
            $params = $this->getRequest()->getParams();
        if (Mage::getModel('affiliateplus/payment')->setAccountId($account->getId())->hasWaitingPayment()) {
            $this->_getCoreSession()->addError($this->__('You are having a pending request!'));
            return $this->_redirect('affiliateplus/index/payments');
        }

        if ($this->_getAccountHelper()->accountNotLogin())
            return $this->_redirect('affiliateplus/account/login');
        if (!isset($params['payment_method'])) {
            $storeId = Mage::app()->getStore()->getId();
            $paymentMethods = Mage::helper('affiliateplus/payment')->getAvailablePayment($storeId);

            /* Changed By Adam 23/10/2013 - hainh update 24-04-2014 */
            if (count($paymentMethods) == 1) {
                foreach ($paymentMethods as $code => $value) {
                    $params['payment_method'] = $code;
                }
            } else {
                $params['payment_method'] = 'paypal';
            }

            if ($params['payment_method'] == 'paypal') {
                if (isset($params['paypal_email']) && $params['paypal_email'])
                    $params['email'] = $params['paypal_email'];
                else
                    $params['email'] = $account->getPaypalEmail();
            }else if ($params['payment_method'] == 'moneybooker') {

                if (isset($params['moneybooker_email']) && $params['moneybooker_email'])
                    $params['email'] = $params['moneybooker_email'];
                else
                    $params['email'] = $account->getMoneybookerEmail();
            }
        }else {
            $params['payment_method'] = $params['payment_method'];
            if ($params['payment_method'] == 'paypal') {
                if (isset($params['paypal_email']) && $params['paypal_email'])
                    $params['email'] = $params['paypal_email'];
                else
                    $params['email'] = $account->getPaypalEmail();
            }else if ($params['payment_method'] == 'moneybooker') {
                if (isset($params['moneybooker_email']) && $params['moneybooker_email'])
                    $params['email'] = $params['moneybooker_email'];
                else
                    $params['email'] = $account->getMoneybookerEmail();
            }
        }

        /* check email verify */

        if (isset($params['payment_method']) && $params['payment_method']) {
            $require = Mage::helper('affiliateplus/payment')->isRequireAuthentication($params['payment_method']);
            if ($require) {
                if (isset($params['email']) && $params['email']) {
                    $verify = Mage::getModel('affiliateplus/payment_verify')->loadExist($account->getId(), $params['email'], $params['payment_method']);
                    if (!$verify->isVerified()) {
                        $this->_getCoreSession()->addError('The email is not authenticated. Please verify authentication code.');
                        $url = Mage::getUrl('affiliates/index/paymentForm');
                        return $this->_redirectUrl($url);
                    }
                }
            }
        }
        /* end */
        $paramObject = new Varien_Object(array('params' => $params));
        Mage::dispatchEvent('affiliateplus_payment_prepare_data', array(
            'payment_data' => $paramObject,
            'file' => $_FILES
        ));
        $params = $paramObject->getParams();
        $payment = Mage::getModel('affiliateplus/payment');
        $payment->setData($params);

        Mage::register('confirm_payment_data', $payment);
        $session->setPayment($payment);
        $session->setPaymentMethod($payment->getPaymentMethod());
        if ($payment->getAmount())
            $session->setPaymentAmount($payment->getAmount());
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setTitle($this->__('Confirm'));
        $this->renderLayout();
    }

    /* End edit */

    public function referrersAction() {
        // Changed By Adam 28/07/2014
        if(!Mage::helper('affiliateplus')->isAffiliateModuleEnabled()) return $this->_redirectUrl(Mage::getBaseUrl());
        
        if (!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)) {
            return;
        }
        if ($this->_getAccountHelper()->accountNotLogin())
            return $this->_redirect('affiliateplus/account/login');
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setTitle($this->__('Traffics'));
        $this->renderLayout();
    }

    public function listCategoriesAction() {
        // Changed By Adam 28/07/2014
        if(!Mage::helper('affiliateplus')->isAffiliateModuleEnabled()) return $this->_redirectUrl(Mage::getBaseUrl());
        
        if (!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)) {
            return;
        }
        $this->_redirectUrl(Mage::getBaseUrl());
    }

    public function cancelPaymentAction() {
        // Changed By Adam 28/07/2014
        if(!Mage::helper('affiliateplus')->isAffiliateModuleEnabled()) return $this->_redirectUrl(Mage::getBaseUrl());
        
        if (!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)) {
            return;
        }
        if ($this->_getAccountHelper()->accountNotLogin())
            return $this->_redirect('affiliateplus/account/login');
        $id = $this->getRequest()->getParam('id');
        $payment = Mage::getModel('affiliateplus/payment')->load($id);
        $account = $this->_getAccountHelper()->getAccount();

        $limitDays = intval($this->_getConfigHelper()->getPaymentConfig('cancel_days'));
        $canCancel = $limitDays ? (time() - strtotime($payment->getRequestTime()) <= $limitDays * 86400) : true;
        if (($payment->getStatus() <= 2) && ($account->getId() == $payment->getAccountId()) && $canCancel)
            try {
                $payment->setStatus(4)->save();
                $this->_getCoreSession()->addSuccess($this->__('Your request has been cancelled successfully!'));
            } catch (Exception $e) {
                $this->_getCoreSession()->addError($e->getMessage());
            }
        $url = Mage::getUrl('affiliates/index/payments');
        return $this->_redirectReferer($url);
//        return $this->_redirectUrl($url);
    }

    public function verifyPaymentAction() {
        // Changed By Adam 28/07/2014
        if(!Mage::helper('affiliateplus')->isAffiliateModuleEnabled()) return $this->_redirectUrl(Mage::getBaseUrl());
        
        $this->loadLayout();
        $block = $this->getLayout()->createBlock('affiliateplus/payment_verify');
        $block->setTemplate('affiliateplus/payment/verify.phtml');
        $method = $this->getRequest()->getParam('method');
        $email = $this->getRequest()->getParam('email');

        $account = Mage::getSingleton('affiliateplus/session')->getAccount();
        if ($email) {
            try {
                $account = Mage::getModel('affiliateplus/account')->load($account->getId());
                $account->setData($method . '_email', $email)
                        ->save();
            } catch (Exception $e) {
                
            }
        }
        $verify = Mage::getModel('affiliateplus/payment_verify')->loadExist($account->getId(), $email, $method);
        if (!$verify->getId()) {
            $verify->setData('payment_method', $method);
            $verify->setData('account_id', $account->getId());
            $verify->setData('field', $email);

            $code = $verify->sendMailAuthentication($email, $method);
            if ($code) {
                $verify->setData('info', $code);
                try {
                    $verify->save();
                } catch (Exception $e) {
                    
                }
            } else {
                $block->setError('1');
            }
        }
        $this->getResponse()->setBody($block->toHtml());
    }

    public function verifyCodeAction() {
        // Changed By Adam 28/07/2014
        if(!Mage::helper('affiliateplus')->isAffiliateModuleEnabled()) return $this->_redirectUrl(Mage::getBaseUrl());
        
        $request = $this->getRequest();
        $method = $request->getParam('payment_method');
        $email = $request->getParam('email');
        $amount = $request->getParam('amount');
        $from = $request->getParam('from');
        $action = 'confirmRequest';
        if ($from == 'email')
            $action = 'paymentForm';
        $code = $request->getParam('authentication_code');
        $account = Mage::getSingleton('affiliateplus/session')->getAccount();
        if (!$account->getId()) {
            $accountId = $request->getParam('account_id');
        } else
            $accountId = $account->getId();
        $verified = Mage::getModel('affiliateplus/payment_verify')->verify($accountId, $email, $method, $code);
        if ($verified) {
            $this->_getCoreSession()->addSuccess('Your email was successfully verified!');
            if ($method == 'paypal')
                $url = Mage::getUrl('affiliates/index/' . $action, array('payment_method' => $method, 'amount' => $amount, 'paypal_email' => $email));
            else
                $url = Mage::getUrl('affiliates/index/' . $action, array('payment_method' => $method, 'amount' => $amount, 'moneybooker_email' => $email));
            return $this->_redirectUrl($url);
        }else {
            $this->_getCoreSession()->addError('Your email was unsuccessfully verified!');
            $url = Mage::getUrl('affiliates/index/paymentForm', array('payment_method' => $method));
            return $this->_redirectUrl($url);
        }
    }

    public function checkVerifyAction() {
        // Changed By Adam 28/07/2014
        if(!Mage::helper('affiliateplus')->isAffiliateModuleEnabled()) return $this->_redirectUrl(Mage::getBaseUrl());
        
        $request = $this->getRequest();
        $method = $request->getParam('payment_method');
        $email = $request->getParam('email');
        $require = Mage::helper('affiliateplus/payment')->isRequireAuthentication($method);
        if ($require) {
            $account = Mage::getSingleton('affiliateplus/session')->getAccount();

            $verify = Mage::getModel('affiliateplus/payment_verify')->loadExist($account->getId(), $email, $method);
            if ($verify->isVerified()) {
                $this->getResponse()->setBody('1');
            } else {
                $this->getResponse()->setBody('');
            }
        } else {
            $this->getResponse()->setBody('1');
        }
    }

    public function sendVerifyEmailAction() {
        // Changed By Adam 28/07/2014
        if(!Mage::helper('affiliateplus')->isAffiliateModuleEnabled()) return $this->_redirectUrl(Mage::getBaseUrl());
        
        $account = Mage::getSingleton('affiliateplus/session')->getAccount();
        $request = $this->getRequest();
        $method = $request->getParam('payment_method');
        $email = $request->getParam('email');
        $verify = Mage::getModel('affiliateplus/payment_verify')->loadExist($account->getId(), $email, $method);
        try {
            $code = $verify->sendMailAuthentication($email, $method);
            if ($code)
                $this->getResponse()->setBody('1');
        } catch (Exception $e) {
            $this->getResponse()->setBody('');
        }
    }

    /**
     * @return mixed
     */
    public function substoreAction() {
        // Changed By Adam 28/07/2014
        if(!Mage::helper('affiliateplus')->isAffiliateModuleEnabled())
            return $this->_redirectUrl(Mage::getBaseUrl());
        if (Mage::helper('affiliateplus/account')->accountNotLogin())
            return $this->_redirect('affiliateplus/account/login');

        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setTitle(Mage::helper('affiliateplus')->__('Sub Store Url'));
        $this->renderLayout();
    }

    /**
     *	Changed by Adam (27/08/2016): show list lifetimecustomer in frontend
     */
    public function lifetimecustomerAction() {
        if (!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)) {
            return;
        }

        if(!Mage::helper('affiliateplus')->isAffiliateModuleEnabled()) {
            return $this->_redirectUrl(Mage::getBaseUrl());
        }
        if(Mage::helper('affiliateplus/account')->accountNotLogin()) {
            return $this->_redirect('affiliates/account/login');
        }

        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setTitle(Mage::helper('affiliateplus')->__('List Lifetime Customer'));
        $this->renderLayout();
    }

    /*Changed By Adam (27/08/2016): change commission to affiliate manually*/
    public function listUpdateBanlanceTransactionAction() {
        if(!Mage::helper('affiliateplus')->isAffiliateModuleEnabled()) return $this->_redirectUrl(Mage::getBaseUrl());
        if(!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)){ return; }
        if ($this->_getAccountHelper()->accountNotLogin())
            return $this->_redirect('affiliateplus/account/login');
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setTitle($this->__('Update Balance Transactions'));
        $this->renderLayout();
    }

    /**
     * Added By Adam (31/08/2016): display product list in the substore
     */
    public function viewAction() {
        if(!Mage::helper('affiliateplus')->isAffiliateModuleEnabled()) return $this->_redirectUrl(Mage::getBaseUrl());

        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setTitle($this->__('Affiliate Page Shop'));
        $this->renderLayout();
    }




    /**
     * Added By Adam (31/08/2016): check if the product is already assign to an affiliate.
     */
    public function addproductAction() {
        $accountId = $this->getRequest()->getParam('accountid');
        $productId = $this->getRequest()->getParam('productid');
        $error = true;
        if(!Mage::helper('affiliateplus')->checkExistAccountProduct($accountId, $productId)){
            try{
                $model = Mage::getModel('affiliateplus/accountproduct')
                    ->setAccountId($accountId)
                    ->setProductId($productId)
                    ->save();
                $error = false;
//                $message = Mage::helper('affiliateplus')->__('The product has been added to your substore successfully.');
            } catch(Exception $e) {
                $error = true;
//                $message = Mage::helper('affiliateplus')->__('You cannot add this product to your substore');
            }
        } else {
            $error = true;
//            $message = Mage::helper('affiliateplus')->__('You cannot add this product to your substore');
        }
        if($error) {
            $html = "<div class='error-msg'>" . $this->__('You cannot add this product to your substore') . "</div>";
        } else {
            $html = "<div class='success-msg'>" . $this->__('The product has been added to your substore successfully.') . "</div>";
        }
        $this->getResponse()->setBody($html);
    }
}
