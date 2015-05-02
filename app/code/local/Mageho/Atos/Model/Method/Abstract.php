<?php

/**
 * 1997-2015 Quadra Informatique
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0) that is available
 * through the world-wide-web at this URL: http://www.opensource.org/licenses/OSL-3.0
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to modules@quadra-informatique.fr so we can send you a copy immediately.
 *
 * @author Quadra Informatique <modules@quadra-informatique.fr>
 * @copyright 1997-2015 Quadra Informatique
 * @license http://www.opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */
abstract class Mageho_Atos_Model_Method_Abstract extends Mage_Payment_Model_Method_Abstract
{

    protected $_response = null;
    protected $_html = null;
    protected $_error = false;
    
    /**
     * Config instance
     * @var Mageho_Atos_Model_Config
     */
    protected $_config;
    
    protected $_order;
    protected $_quote;

    /**
     * First call to the Atos server
     */
    abstract public function callRequest();

    /**
     * Get Payment Means
     *
     * @return string
     */
    abstract public function getPaymentMeans();

    /**
     * Get normal return URL
     *
     * @return string
     */
    abstract protected function _getNormalReturnUrl();

    /**
     * Get cancel return URL
     *
     * @return string
     */
    abstract protected function _getCancelReturnUrl();

    /**
     * Get automatic response URL
     *
     * @return string
     */
    abstract protected function _getAutomaticResponseUrl();

    /**
     * Return Order place redirect url
     *
     * @return string
     */
    abstract function getOrderPlaceRedirectUrl();

    /**
     * Instantiate state and set it to state object
     * @param string $paymentAction
     * @param Varien_Object
     */
    public function initialize($paymentAction, $stateObject)
    {
        switch ($paymentAction) {
            case Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE:
            case Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE:
                $stateObject->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
                $stateObject->setStatus('pending_payment');
                $stateObject->setIsNotified(false);
                break;
            default:
                break;
        }
    }

    /**
     * Get system response
     *
     * @return string
     */
    public function getSystemResponse()
    {
        return $this->_response;
    }

    /**
     * Get system generated HTML
     *
     * @return string
     */
    public function getSystemHtml()
    {
        return $this->_html;
    }

    /**
     * Get system url
     *
     * @return string
     */
    public function getSystemUrl()
    {
        return $this->_url;
    }

    /**
     * Has system error
     *
     * @return boolean
     */
    public function hasSystemError()
    {
        return $this->_error;
    }
	
	/**
     * Config instance getter
     * @return Mageho_Atos_Model_Config
     */
    public function getConfig()
    {
        if (null === $this->_config) {
            $params = array($this->_code);
            if ($store = $this->getStore()) {
                $params[] = is_object($store) ? $store->getId() : $store;
            }
            $this->_config = Mage::getModel('atos/config', $params);
        }
        return $this->_config;
    }
	
	/**
     * Custom getter for payment configuration
     *
     * @param string $field
     * @param int $storeId
     * @return mixed
     */
    public function getConfigData($field, $storeId = null)
    {
        return $this->getConfig()->$field;
    }
	
    /**
     * Assign data to info model instance
     *
     * @param   mixed $data
     * @return  Mage_Payment_Model_Info
     */
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
		
		if ($data->getAtosStandardPaymentMeans()) {
	        $this->getAtosSession()->setAtosStandardPaymentMeans($data->getAtosStandardPaymentMeans());
		}
		if ($data->getAtosSeveralPaymentMeans()) {
	        $this->getAtosSession()->setAtosSeveralPaymentMeans($data->getAtosSeveralPaymentMeans());
		}
		
		if ($data->getAuroreDob()) {
			$dob = Mage::app()->getLocale()->date($data->getAuroreDob(), null, null, false)->toString('yyyy-MM-dd');
			$this->getAtosSession()->setCustomerDob($dob);
		}
		
        return $this;
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
     * Get Atos API Request Model
     *
     * @return Quadra_Atos_Model_Api_Request
     */
    public function getApiRequest()
    {
        return Mage::getSingleton('atos/api_request');
    }

    /**
     * Get current quote
     *
     * @return Mage_Sales_Model_Quote|boolean
     */
    protected function _getQuote()
    {
        if (empty($this->_quote)) {
            $quoteId = Mage::getSingleton('atos/session')->getQuoteId();
            $this->_quote = Mage::getModel('sales/quote')->load($quoteId);
        }
        return $this->_quote;
    }

    /**
     * Get current order
     *
     * @return Mage_Sales_Model_Order|boolean
     */
    protected function _getOrder()
    {
        if (empty($this->_order)) {
            $session = Mage::getSingleton('checkout/session');
            $this->_order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
        }

        return $this->_order;
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
        if ($this->_getOrder())
            return (int) $this->_getOrder()->getCustomerId();
        else
            return 0;
    }

    /**
     * Get customer e-mail
     *
     * @return string
     */
    protected function _getCustomerEmail()
    {
        if ($this->_getOrder())
            return $this->_getOrder()->getCustomerEmail();
        else
            return 'undefined';
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

    /**
     * Get order inrement id
     *
     * @return string
     */
    protected function _getOrderId()
    {
        return $this->_getOrder()->getIncrementId();
    }

    public function debugRequest($data)
    {
        $this->debugData(array('type' => 'request', 'parameters' => $data));
    }

    public function debugResponse($data, $from = '')
    {
        ksort($data);
        $this->debugData(array('type' => "{$from} response", 'parameters' => $data));
    }
}