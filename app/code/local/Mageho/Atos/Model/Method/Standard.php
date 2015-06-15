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

class Mageho_Atos_Model_Method_Standard extends Mageho_Atos_Model_Method_Abstract
{
    protected $_code  = Mageho_Atos_Model_Config::METHOD_ATOS_SIPS_PAYMENT_STANDARD;
    protected $_formBlockType = 'atos/standard_form';
	protected $_infoBlockType = 'atos/standard_info';
	
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
        if (/*parent::isAvailable($quote) && */$this->getConfig()->isMethodAvailable()) {
            return true;
        }
        
        return false;
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
			'templatefile' => $this->getConfig()->templatefile,
			'capture_mode' => $this->_getCaptureMode(),
			'capture_day' => $this->_getCaptureDay(),
			'cmd' => $this->getConfig()->getDatafield()
		);
		
		$request = new Mageho_Atos_Model_Api_Request($params);
		
        if ($request->getError()) {
			$this->_error = true;
	        $this->_html = $request->getDebug();
			
			if ($this->getDebug()->getRequestCmd()) {
				$this->_html.= "\n\n" . $this->getDebug()->getRequestCmd();
			}
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
	    return explode(',', $this->getConfig()->payment_means);
	}
	
    /**
     * Get normal return URL
     *
     * @return string
     */
    protected function _getNormalReturnUrl()
    {
        return Mage::getUrl('atos/standard/normal', array('_secure' => true));
    }
    
    /**
     * Get cancel return URL
     *
     * @return string
     */
    protected function _getCancelReturnUrl()
    {
        return Mage::getUrl('atos/standard/cancel', array('_secure' => true));
    }
    
    /**
     * Get automatic response URL
     *
     * @return string
     */
    protected function _getAutomaticResponseUrl()
    {
        return Mage::getUrl('atos/automatic/index', array('_secure' => true));
    }
    
    /**
     * Return Order place redirect url
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('atos/standard/redirect', array('_secure' => true));
    }
    
    /**
     * Get capture day
     *
     * @return int
     */
    protected function _getCaptureDay()
    {
        return (int) $this->getConfigData('capture_day');
    }
    
    /**
     * Get capture mode
     *
     * @return string
     */
    protected function _getCaptureMode()
    {
        return $this->getConfig()->getPaymentAction($this->getConfigData('capture_mode'));
    }
}