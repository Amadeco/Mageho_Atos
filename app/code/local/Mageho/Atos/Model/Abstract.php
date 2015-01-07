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
 * @copyright   Copyright (c) 2014  Mageho (http://www.mageho.com)
 * @license      http://www.opensource.org/licenses/OSL-3.0  Open Software License (OSL 3.0)
 */
 
class Mageho_Atos_Model_Abstract extends Mage_Payment_Model_Method_Abstract
{
    protected $_order;
    protected $_quote;

    /**
     * Get Config model
     *
     * @return object Mageho_Atos_Model_Config
     */
    public function getConfig()
    {
        return Mage::getModel('atos/config');
    }
	
	/**
     * Get Debug model
     *
     * @return object Mageho_Atos_Model_Debug
     */
	public function getDebug() 
	{
	    return Mage::getSingleton('atos/debug');
	}
	
	/**
     * Get customers session namespace
     *
     * @return Mage_Customer_Model_Session
     */
	public function getCustomerSession()
	{
	    return Mage::getSingleton('customer/session');	
	}

    /**
     * Get checkout session namespace
     *
     * @return object Mage_Checkout_Model_Session
     */
    public function getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }
	
	/**
     * Get atos session namespace
     *
     * @return Mageho_Atos_Model_Session
     */
    public function getAtosSession()
    {
        return Mage::getSingleton('atos/session');
    }
	
	/**
     * Get method Atos Payment Website Standard
     *
     * @return object Mageho_Atos_Model_Method_Standard
     */
    public function getAtosPaymentStandard()
	{
	    return Mage::getSingleton('atos/method_standard');
	}
	
    /**
     * Get Atos API Request Model
     *
     * @return object Mageho_Atos_Model_Api_Request
     */
    public function getApiRequest()
    {
        return Mage::getSingleton('atos/api_request');
    }
	
    /**
     * Get Atos Api Response Model
     *
     * @return object Mageho_Atos_Model_Api_Response
     */
    public function getApiResponse()
    {
        return Mage::getSingleton('atos/api_response');
    }

    /**
     * Get Atos Api Files Model
     *
     * @return object Mageho_Atos_Model_Api_Files
     */
	public function getApiFiles()
	{
        return Mage::getSingleton('atos/api_files');
	}
	
	/**
     * Return current quote object
     * @return Mageho_Sales_Model_Quote $quote
     */
    protected function _getQuote() 
    {
        if (!$this->_quote) {
            $this->_quote = Mage::getModel('sales/quote')->load(
            	$this->getAtosSession()->getQuoteId()
            );
        }
        return $this->_quote;
    }

	/**
     *  Return current order object
     *
     *  @return	  object
     */
    protected function _getOrder()
    {
        if (empty($this->_order)) {
            $this->_order = Mage::getModel('sales/order')->loadByIncrementId(
            	$this->getCheckoutSession()->getLastRealOrderId()
            );
        }
        return $this->_order;
    }
    
    /**
     * Get order inrement id
     *
     * @return string
     */
    protected function _getOrderId()
    {
        return $this->_getOrder()->getIncrementId();
    }
	
	/**
     * Get order amount
     *
     * @return string
     */
    protected function _getAmount() 
    {
        if ($_order = $this->_getOrder()) 
        {
            /*
	         *
	         * JPY Code ISO devise Yen japonais / 392
	         * KPW & KRW Code ISO devise Won (nord-coréen) & (sud-coréen) / 410
	         * XPF Code ISO devise franc CFP / 953
	         * XAF & XOF Code ISO devise France CFA (BEAC) & CFA (BCEAO) / 952
	         *
	         */
            $orderCurrencyCode = $_order->getOrderCurrencyCode();
            if (in_array($orderCurrencyCode, array('JPY', 'KPW', 'KRW', 'XPF', 'XAF', 'XOF'))) 
            {
	            $decimals = 0;
            } else {
	            $decimals = 2;
            }
            $total = number_format($_order->getTotalDue(), $decimals, '', '');
        } else {
            $total = 0;
		}
		return $total;
    }
    
    /**
     * Get customer ID
     *
     * @return int
     */
    protected function _getCustomerId() 
    {
        if ($this->_getOrder()) {
            return (int) $this->_getOrder()->getCustomerId();
        } else {
            return 0;
        }
    }
    
	/**
     * Get customer e-mail
     *
     * @return string
     */
    protected function _getCustomerEmail() 
    {
        if ($this->_getOrder()) {
            return $this->_getOrder()->getCustomerEmail();
        } else {
            return 'undefined';
        }
    }
    
    /**
     * Get customer IP address
     *
     * @return string
     */
    protected function _getCustomerIpAddress() 
    {
        $ip = $this->_getQuote()->getRemoteIp();
        
        # Determine originating IP address. REMOTE_ADDR is the standard
        # but will fail if the user is behind a proxy. HTTP_CLIENT_IP and/or
        # HTTP_X_FORWARDED_FOR are set by proxies so check for these before
        # falling back to REMOTE_ADDR. HTTP_X_FORWARDED_FOR may be a comma-
        # delimited list in the case of multiple chained proxies; the first is
        # the originating IP.
        #
        # Security note: do not use if IP spoofing is a concern for your
        # application. Since remote_ip checks HTTP headers for addresses forwarded
        # by proxies, the client may send any IP. remote_addr can't be spoofed but
        # also doesn't work behind a proxy, since it's always the proxy's IP.
        # @see http://metautonomo.us/2008/05/30/the-local_request-that-isnt/

        $reg_priv_network = '/^unknown$|^(10|172\.(1[6-9]|2[0-9]|30|31)|192\.168)\./i';
        if (strpos($ip, ',') !== false) {
            $_ips = explode(',', $ip);
            foreach($_ips as $_ip) {
                $_ip = trim($_ip);
                if (! preg_match($reg_priv_network, $_ip, $matches )) {
                    return $_ip;
                }
            }
        }
        
        return $ip;
    }
}
