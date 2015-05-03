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
 
class Mageho_Atos_Controller_Action extends Mage_Core_Controller_Front_Action
{
    protected $_api = null;
    protected $_config = null;
    protected $_invoice = null;
    protected $_invoiceFlag = false;
    protected $_order = null;
    protected $_atosResponse = null;
	
	/*
	 * Get checkout session
	 *
	 * @return Mage_Checkout_Model_Session
	 */
	public function getCheckoutSession()
	{
	    return Mage::getSingleton('checkout/session');	
	}

    /**
     * Retrieve shopping cart model object
     *
     * @return Mage_Checkout_Model_Cart
     */
    public function getCart()
    {
        return Mage::getSingleton('checkout/cart');
    }

   /*
	* Get customer session
	*
	* @return Mage_Customer_Model_Session
	*/
    public function getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }
    
   /*
	* Get Atos/Sips Standard config
	*
	* @return Quadra_Atos_Model_Config
	*/
	public function getConfig()
	{
	    return Mage::getSingleton('atos/config');
	}
	
	/*
     * Get singleton Atos session
     *
     * @return object Mageho_Atos_Model_Session
     */
	public function getAtosSession()
	{
	    return Mage::getSingleton('atos/session');	
	}
	
   /*
	* Get Atos Api Response Model
	*
	* @return Mageho_Atos_Model_Api_Response
	*/
    public function getApiResponse()
    {
        return Mage::getSingleton('atos/api_response');
    }
    
	/*
     * Get singleton with Atos standard
     *
     * @return object Mageho_Atos_Model_Method_Standard
     */
    public function getAtosPaymentStandard()
    {
        return Mage::getSingleton('atos/method_standard');
    }
    
    /**
     * Get singleton with Atos several
     *
     * @return object Mageho_Atos_Model_Method_Several
     */
    public function getAtosPaymentSeveral()
    {
        return Mage::getSingleton('atos/method_several');
    }
    	
	/**
     * @param array $request
     * @return object Mageho_Atos_Controller_Action $this
     */
    public function processIpnResponse($request)
    {
    	if (! isset($request['DATA'])) {
	    	Mage::getSingleton('atos/debug')->log(
	    		Mage::helper('atos')->__('An error occured: var $request has no data.')
	    	);
            
            $this->getAtosSession()->setRedirectMessage(
            	Mage::helper('atos')->__('An error occured: no data received.')
            );
                
            Mage::app()->getResponse()
            	->setHeader('HTTP/1.1', '503 Service Unavailable')
            	->sendResponse();
            exit;
        }
        
        $this->_atosResponse = $this->getApiResponse()->doResponse($request['DATA']);
    
		if ($this->_atosResponse['merchant_id'] != $this->getConfig()->merchant_id) {
			Mage::getSingleton('atos/debug')->log(
				Mage::helper('atos')->__("Configuration merchant id (%s) doesn't match merchant id (%s)", 
					$this->getConfig()->merchant_id, 
					$this->_atosResponse['merchant_id']
				)
			);
			
			$this->getAtosSession()->setRedirectMessage(
				Mage::helper('atos')->__('We encounter errors with this payment method')
			);
                
            Mage::app()->getResponse()
            	->setHeader('HTTP/1.1', '503 Service Unavailable')
            	->sendResponse();
            exit;
		}
	
	    if ($this->_atosResponse['code'] == '-1') {
	    	Mage::getSingleton('atos/debug')->log(
	    		Mage::helper('atos')->__("An error occured: error code %s", $this->_atosResponse['code'])
	    	);
			
			$this->getAtosSession()->setRedirectMessage(
				Mage::helper('atos')->__('We encounter errors with this payment method')
			);
                
            Mage::app()->getResponse()
            	->setHeader('HTTP/1.1', '503 Service Unavailable')
            	->sendResponse();
            exit;
	    }

		return $this;
    }

	public function getAtosResponse($key = null) 
	{
		if ($key != null) {
			if (isset($this->_atosResponse[$key])) {
				return $this->_atosResponse[$key];
			}
		}
		return $this->_atosResponse;
	}
	
	public function hasAtosResponse() 
	{
		return (bool) !empty($this->_atosResponse) && count($this->_atosResponse);	
	}
	
    /**
     * Load order
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _getOrder()
    {
        if (empty($this->_order)) {
            // Check order ID existence
            if (!array_key_exists('order_id', $this->_atosResponse)) {
                Mage::getSingleton('atos/debug')->log(
                	Mage::helper('atos')->__('No order Id found in response data.')
                );
                
                Mage::app()->getResponse()
                        ->setHeader('HTTP/1.1', '503 Service Unavailable')
                        ->sendResponse();
                exit;
            }
            // Load order
            $orderId = $this->_atosResponse['order_id'];
            $this->_order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
            if (!$this->_order->getId()) {
                Mage::getSingleton('atos/debug')->log(
                	Mage::helper('atos')->__('Wrong order Id: "%s".', $orderId)
                );
                
                Mage::app()->getResponse()
                        ->setHeader('HTTP/1.1', '503 Service Unavailable')
                        ->sendResponse();
                exit;
            }
        }
        return $this->_order;
    }
	
	/*
	 *
	 * Mise à jour de la commande selon la réponse du serveur bancaire
	 * Option BO : Création de la facture
	 * Envoie de l'email de confirmation de commande
	 * Sauvegarde de la transaction dans le BO Magento
	 *
	 * @param Mage_Sales_Model_Order $order
	 * @return
	 *
	 */
	protected function _processOrder()
	{
		// Get order to update
        $this->_getOrder();
		
		/* Retrieve payment method object */
		$payment = $this->_order->getPayment()->getMethodInstance();
		
        switch ($this->_atosResponse['response_code']) {
            // Success order
            case '00':
                // Update payment
                $this->_processOrderPayment();
                
				// Create invoice
                if ($this->_invoiceFlag) {
                    $this->_processOrderInvoice();
				}
				
				$this->_order->save();

				// Send order confirmation email
                if (!$this->_order->getEmailSent() && $this->_order->getCanSendNewEmailFlag()) {
                    try {
                        if (method_exists($this->_order, 'queueNewOrderEmail')) {
                            $this->_order->queueNewOrderEmail();
                        } else {
                            $this->_order->sendNewOrderEmail();
                        }
                    } catch (Exception $e) {
                        Mage::logException($e);
                    }
                }                
                // Send invoice email
                if ($this->_invoiceFlag) {
                    try {
                        $this->_invoice->sendEmail();
                    } catch (Exception $e) {
                        Mage::logException($e);
                    }
                }
                break;

            // Cancel order
            default:
            	$this->_processOrderPayment();
            
				$this->_order->cancel()
					->save();
                
                break;
        }
    }
	
	protected function _processOrderPayment()
	{
		try {
			$payment = $this->_order->getPayment();
			
			/*
			 *
			 * Payment
			 *
			 */
			$paymentDetails = array(
				'transaction_id' => $this->_atosResponse['transaction_id'],
				'cc_type' => $this->_atosResponse['payment_means'],
				'cc_trans_id' => $this->_atosResponse['transaction_id'],
				'additional_data' => serialize($this->_atosResponse)
			);
				
			if ($ccNumber = $this->getApiResponse()->getCcNumberEnc($this->_atosResponse['card_number'])) {
				$paymentDetails['cc_number_enc'] = $ccNumber;
				$paymentDetails['cc_exp_month'] = substr($this->_atosResponse['card_validity'], 4, 2);
				$paymentDetails['cc_exp_year'] = substr($this->_atosResponse['card_validity'], 0, 4);
			}
			if ($ccLast4 = $this->getApiResponse()->getCcLast4($this->_atosResponse['card_number'])) {
				$paymentDetails['cc_last_4'] = $ccLast4;
			}
			
	        foreach ($paymentDetails as $key => $value) {
	            $payment->setData($key, $value);
	        }
	        
	        $payment->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $this->_atosResponse);
			
			switch ($this->_atosResponse['response_code']) 
			{
				case '00':
			        if (!$this->_order->isCanceled() && $payment->getMethodInstance()->canAuthorize()) 
			        {
				        $payment->authorize(true, $this->_order->getBaseGrandTotal());
				        $payment->setAmountAuthorized($this->_order->getTotalDue());
				        
				        if ($this->_atosResponse['capture_mode'] == Mageho_Atos_Model_Config::PAYMENT_ACTION_CAPTURE ||
				        	$this->_atosResponse['capture_mode'] == Mageho_Atos_Model_Config::PAYMENT_ACTION_AUTHORIZE) {
					        $this->_invoiceFlag = true;
			            }
			        }
			        break;
			    default:
					$transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID);
					$transaction->save();
				    	
				    $payment->cancel();
			        break;
			}
			
			$payment->save();
	        $this->_order->save();
	        
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::app()->getResponse()
                    ->setHeader('HTTP/1.1', '503 Service Unavailable')
                    ->sendResponse();
            exit;
        }
		return $this;
	}
    
    protected function _processOrderInvoice() 
    {
        if ($this->_order->canInvoice()) {
		    try {
	            $this->_invoice = $this->_order->prepareInvoice();
	            $this->_invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
	            $this->_invoice->register();
	            
	            $transactionSave = Mage::getModel('core/resource_transaction')
	                    ->addObject($this->_invoice)
	                    ->addObject($this->_invoice->getOrder())
	                    ->save();
	                    
	            $this->_order->addStatusHistoryComment(
	            	Mage::helper('atos')->__('Invoice %s was created', $this->_invoice->getIncrementId())
	            );
	                    
	        } catch (Exception $e) {
	            Mage::logException($e);
	            Mage::app()->getResponse()
	                    ->setHeader('HTTP/1.1', '503 Service Unavailable')
	                    ->sendResponse();
	            exit;
	        }
	        
			return $this->_invoice->getIncrementId();
        }
    }
 }
