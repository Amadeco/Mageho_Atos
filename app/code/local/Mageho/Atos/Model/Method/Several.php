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

class Mageho_Atos_Model_Method_Several extends Mageho_Atos_Model_Method_Abstract
{
    protected $_code  = Mageho_Atos_Model_Config::METHOD_ATOS_SIPS_PAYMENT_SEVERAL;
    protected $_formBlockType = 'atos/several_form';
	protected $_infoBlockType = 'atos/several_info';
	
	/**
     * Payment Method features
     * @var bool
     */
    protected $_canAuthorize = true;
    protected $_isInitializeNeeded = true;
    protected $_canUseForMultishipping = false;
    protected $_canUseInternal = false;
	
	/**
     * Check whether payment method can be used
     * @param Mage_Sales_Model_Quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        if (/*parent::isAvailable($quote) && */$this->getConfig()->isMethodAvailable()) {
            return true;
        }
        
        return false;
    }
    
	public function callRequest()
    {
		$fields = array(
			'NB_PAYMENT' => $this->_getNbPayment(),
			'PERIOD' => 30,
			'INITIAL_AMOUNT' => $this->_getFirstAmount()
		);
		
		$params = array(
			'object' => $this,
			'amount' => $this->_getAmount(),
			'order_id' => $this->_getOrderId(),
			'currency_code' => $this->getConfig()->getCurrencyCode($this->_getQuote()->getQuoteCurrencyCode()),
			'customer_id' => $this->_getCustomerId(),
			'customer_email' => $this->_getCustomerEmail(),
			'customer_ip_address' => $this->_getCustomerIpAddress(),
			'payment_means' => implode(',2,', $this->getPaymentMeans()) . ',2',
			'normal_return_url' => $this->_getNormalReturnUrl(),
			'cancel_return_url' => $this->_getCancelReturnUrl(),
			'automatic_response_url' => $this->_getAutomaticResponseUrl(),
			'templatefile' => $this->getConfig()->templatefile,
			'capture_mode' => 'PAYMENT_N',
			'capture_day' => '0',
			'cmd' => $this->getConfig()->getDatafield($fields)
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
	
	public function getPaymentMeans()
	{
	    return explode(',', $this->getConfig()->payment_means);
	}
	
    /**
     *  Return URL for customer response
     *
     *  @return	  string Return customer URL
     */
    public function _getNormalReturnUrl()
    {
        return Mage::getUrl('atos/payment_several/normal', array('_secure' => true));
    }
		 
    /**
     *  Return URL for cancel payment
	 *
     *  @return	  string Return cancel URL
     */
    public function _getCancelReturnUrl()
    {
        return Mage::getUrl('atos/payment_several/cancel', array('_secure' => true));
    }
	
    /**
     *  Return URL for automatic response
     *
     *  @return	  string Return automatic URL
     */
    public function _getAutomaticResponseUrl()
    {
        return Mage::getUrl('atos/payment_several/automatic', array('_secure' => true));
    }

    /**
     *  Return Order Place Redirect URL
     *
     *  @return	  string Order Redirect URL
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('atos/payment_several/redirect', array('_secure' => true));
    }
    
    protected function _getNbPayment()
    {
        return Mage::helper('atos')->getNbPayment();
    }
    
    /**
     * Get first amount to capture
     *
     * @return string
     */
    protected function _getFirstAmount()
    {
        return round($this->_getAmount() / $this->_getNbPayment());
    }
}