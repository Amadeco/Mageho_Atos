<?php
/*
 * Mageho
 * Ilan PARMENTIER
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0) that is available
 * through the world-wide-web at this URL: http://www.opensource.org/licenses/OSL-3.0
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to contact@mageho.com so we can send you a copy immediately.
 *
 * @category     Mageho
 * @package     Mageho_Atos
 * @author       Mageho, Ilan PARMENTIER <contact@mageho.com>
 * @copyright   Copyright (c) 2015 Mageho (http://www.mageho.com)
 * @license      http://www.opensource.org/licenses/OSL-3.0  Open Software License (OSL 3.0)
 */

class Mageho_Atos_StandardController extends Mageho_Atos_Controller_Action
{
    public function redirectAction()
    {
    	$this->getAtosSession()->setQuoteId($this->getCheckoutSession()->getLastQuoteId());
        $this->loadLayout();
        		
		Mage::dispatchEvent('atos_controller_standard_redirect_render_before', array(
			'checkout_session' => $this->getCheckoutSession(), 
			'request' => $this->getRequest(),
			'layout' => $this->getLayout()
		));
        
		$this->renderLayout();
		
        $this->getCheckoutSession()->unsQuoteId();
        $this->getCheckoutSession()->unsRedirectUrl();
    }

	public function normalAction() 
	{
        $this->processIpnResponse($_REQUEST);
		
		$atosApiResponse = $this->getApiResponse();
		$response = $this->getAtosResponse();
		
		$order = Mage::getModel('sales/order');
		if ($response['order_id']) {
			$order->loadByIncrementId($response['order_id']);
		}
		
		switch ($response['response_code'])
		{
		    case '00':
<<<<<<< HEAD
                if ($order->getId())  {
                    $order->addStatusHistoryComment(Mage::helper('atos')->__('Customer returned successfully from payment platform.'))
=======
                if ($order->getId()) 
                {
					if (!$status = $this->getAtosPaymentStandard()->getConfig()->order_status_payment_accepted) {
						$status = $order->getStatus();
					}
					
                    $order->addStatusToHistory($status, Mage::helper('atos')->__('Customer returned successfully from payment platform.'))
>>>>>>> FETCH_HEAD
                          ->save();
                }
                
				$this->getAtosSession()->unsAtosStandardPaymentMeans();
				$this->getCheckoutSession()->getQuote()->setIsActive(false)->save();
				
				// Set redirect URL
                $response['redirect_url'] = 'checkout/onepage/success';
			    break;
			    
			default:
				$error = $atosApiResponse->getResponseLabel($response);
						
				switch ($response['cvv_response_code']) 
				{
					case '4E':
					case '': // specific cvv_response_code for AMEX and FINAREF credit card
						$error.= ' - '  . $atosApiResponse->getCvvResponseLabel($response);
						break;
					default:
						$error.= ' - '  . $atosApiResponse->getBankResponseLabel($response);
						break;
				}
						 
				$this->getAtosSession()->setRedirectMessage($error);
				
                // Set redirect URL
				if ($this->getConfig()->redirect) {
					$response['redirect_url'] = '*/*/failure';
				} else {
		        	$response['redirect_url'] = 'checkout/cart';
				}
				break;
		}
				
		Mage::dispatchEvent('atos_controller_standard_normal', array(
			'atos_response' => $response,
			'atos_session' => $this->getAtosSession(),
			'checkout_session' => $this->getCheckoutSession(),
			'order' => $order->getId() ? $order : NULL,
			'request' => $this->getRequest()
		));
		
        $this->_redirect($response['redirect_url'], array('_secure' => true));
	}
	
	public function cancelAction()
	{
		$this->processIpnResponse($_REQUEST);
		
		$atosApiResponse = $this->getApiResponse();
		$response = $this->getAtosResponse();
		
		// Set redirect URL
		if ($this->getConfig()->redirect) {
			$response['redirect_url'] = '*/*/failure';
		} else {
        	$response['redirect_url'] = 'checkout/cart';
		}
		
		$error = $atosApiResponse->getResponseLabel($response);
		
		switch ($response['response_code']) 
		{
			case '17':
				$error.= Mage::helper('atos')->__('Choose an another payment method or contact us by phone at %s to validate your order.', Mage::getStoreConfig('general/store_information/phone'));
					
				$this->getAtosSession()
					->setRedirectTitle(Mage::helper('atos')->__('Payment has been canceled with success.'))
					->setRedirectMessage($error);
					
				break;
			default:
				switch ($response['cvv_response_code']) 
				{
					case '4E':
					case '': // specific cvv_response_code for AMEX and FINAREF credit card
						$error.= ' - '  . $atosApiResponse->getCvvResponseLabel($response);
						break;
					default:
						$error.= ' - '  . $atosApiResponse->getBankResponseLabel($response);
						break;
				}
								 
				$this->getAtosSession()
					->setRedirectTitle(Mage::helper('atos')->__('Your order has been refused'))
					->setRedirectMessage($error);
						
				break;
		}
		
		$order = Mage::getModel('sales/order');
		if ($response['order_id']) 
		{
			$order->loadByIncrementId($response['order_id'])
				->cancel()
				->addStatusHistoryComment($error)
				->save();
			
	    	$cart = $this->getCart();
	    	if (! $cart->getQuote()->getItemsCount()) {
				Mage::helper('atos')->reorder($order);
			}
		}
		
		Mage::dispatchEvent('atos_controller_standard_cancel', array(
			'atos_response' => $response,
			'atos_session' => $this->getAtosSession(),
			'checkout_session' => $this->getCheckoutSession(),
			'order' => $order->getId() ? $order : NULL,
			'request' => $this->getRequest()
		));
		
		$this->_redirect($response['redirect_url'], array('_secure' => true));
	}
	
	/*
	 * When has error in treatment
	 */
    public function failureAction()
    {
    	$cart = $this->getCart();
    	if (! $cart->getQuote()->getItemsCount()) {
    		$this->_redirect('/');
    		return;
        }
    
        $this->loadLayout();
        $this->_initLayoutMessages('checkout/session');
        $this->_initLayoutMessages('catalog/session');

        $paymentMeans = $this->getAtosSession()->getAtosStandardPaymentMeans();
        
   		// Set redirect URL
        $response['redirect_url'] = 'checkout/cart';
        $response['button_url'] = Mage::getUrl('atos/standard/redirect', array('_secure' => true));
        $response['button_text'] = Mage::helper('atos')->__('Pay My Order');
        
   		Mage::dispatchEvent('atos_controller_standard_failure', array(
	   		'atos_response' => $response,
			'atos_session' => $this->getAtosSession(),
			'checkout_session' => $this->getCheckoutSession(),
		));
        
        if ($blockAtosPaymentFailure = $this->getLayout()->getBlock('atos.payment.failure')) {
	        $blockAtosPaymentFailure->setTitle($this->getAtosSession()->getRedirectTitle())
	        	->setMessage($this->getAtosSession()->getRedirectMessage());
	        
	        $blockAtosPaymentFailure->setButtonUrl($response['button_url'])
	        	->setButtonText($response['button_text']);
        }
		
		if (! $this->getConfig()->redirect) {
        	$this->_redirect($response['redirect_url'], array('_secure' => true));
        }
        
        $this->getAtosSession()->unsetAll();
        $this->getAtosSession()->setAtosStandardPaymentMeans($paymentMeans);
        
        $this->renderLayout();
    }
}
