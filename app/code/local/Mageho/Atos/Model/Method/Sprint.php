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
 * @copyright   Copyright (c) 2015  Mageho (http://www.mageho.com)
 * @license      http://www.opensource.org/licenses/OSL-3.0  Open Software License (OSL 3.0)
 */

class Mageho_Atos_Model_Method_Sprint extends Mageho_Atos_Model_Method_Abstract
{
    protected $_code  = Mageho_Atos_Model_Config::METHOD_ATOS_SIPS_PAYMENT_SPRINT;
    protected $_formBlockType = 'atos/sprint_form';
	protected $_infoBlockType = 'atos/sprint_info';
	
    /**
     * Payment Method features
     * @var bool
     */
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canUseForMultishipping = false;
    protected $_isInitializeNeeded = true;
    protected $_canUseInternal = true;
    	
	/**
     * Check whether payment method can be used
     * @param Mage_Sales_Model_Quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
	    /*
	    if (Mage::app()->getStore()->getCurrentCurrencyCode() != 'EUR') {
		    return false;
	    }
	    */
        if (/*parent::isAvailable($quote) && */$this->getConfig()->isMethodAvailable()) {
            return true;
        }
        
        return false;
    }
    
	public function canUseForCurrency($currencyCode)
	{
	    return ($currencyCode == 'EUR');
	}
	
    public function callRequest()
    {
		$params = array(
			'object' => $this,
			'amount' => $this->_getAmount(),
			'order_id' => $this->_getOrderId(),
			'currency_code' => $this->getConfig()->getCurrencyCode($this->_getQuote()->getQuoteCurrencyCode()),
			'customer_id' => $this->_getCustomerId(),
			'customer_email' => $this->_getCustomerEmail(),
			'customer_ip_address' => $this->_getCustomerIpAddress(),
			'payment_means' => $this->getPaymentMeans(),
			'normal_return_url' => $this->_getNormalReturnUrl(),
			'cancel_return_url' => $this->_getCancelReturnUrl(),
			'automatic_response_url' => $this->_getAutomaticResponseUrl(),
			'cmd' => $this->getConfig()->getDatafield()
		);
		
		$request = new Mageho_Atos_Model_Api_Request($params);
		
        if ($request->getError()) {
			$this->_error = true;
	        $this->_html = $request->getDebug();
		} else {
			$this->_error = false;
	        $this->_url = $request->getUrl();
			$this->_html = $request->getHtml();
		}
    }
    
    public function getAmount()
    {
	    return $this->_getAmount();
    }
	
    /**
     * Get Payment Means
     *
     * @return string
     */
	public function getPaymentMeans()
	{
	    return 'SOLUTIONSPRINTSECURE,2';
	}
	
    /**
     * Get normal return URL
     *
     * @return string
     */
    protected function _getNormalReturnUrl()
    {
        return Mage::getUrl('atos/payment_sprint/normal', array('_secure' => true));
    }
    
    /**
     * Get cancel return URL
     *
     * @return string
     */
    protected function _getCancelReturnUrl()
    {
        return Mage::getUrl('atos/payment_sprint/cancel', array('_secure' => true));
    }
    
    /**
     * Get automatic response URL
     *
     * @return string
     */
    protected function _getAutomaticResponseUrl()
    {
        return Mage::getUrl('atos/payment_sprint/automatic', array('_secure' => true));
    }
    
    /**
     * Return Order place redirect url
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('atos/payment_sprint/redirect', array('_secure' => true));
    }
}