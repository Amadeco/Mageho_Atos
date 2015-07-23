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
 
class Mageho_Atos_Model_Config extends Mageho_Atos_Model_Abstract
{
    /*
     * Atos Sips Standard
     * @var string
     */
    const METHOD_ATOS_SIPS_PAYMENT_STANDARD = 'atoswps';
	const METHOD_ATOS_SIPS_PAYMENT_SEVERAL = 'atoswpseveral';
    const METHOD_ATOS_SIPS_PAYMENT_SPRINT = 'atoswpsprint';
    const METHOD_ATOS_SIPS_PAYMENT_3XCB = 'atoswp3xcb';
	
    /*
     * Atos Sips Standard Mode Action
     * @var string
     */
	const PAYMENT_ACTION_AUTHORIZE_CAPTURE = 'AUTHOR_CAPTURE';
	
	/* 
	 * Ce mode de capture est dangereux
	 * Si on oublie de valider la transaction sur le BO de la banque, pas de débit, si supérieur à 7 jours, le débit n'est plus autorisé, la banque fait une nouvelle demande d'autorisation
	 * 
	 * Si activé, ne pas oublier d'enlever le champs depends du fichier system.xml du champs "capture_day" 
	 */
    const PAYMENT_ACTION_AUTHORIZE = 'VALIDATION';
	
	/*
     * Current payment method code
     * @var string
     */
    protected $_methodCode = null;

    /*
     * Current store id
     *
     * @var int
     */
    protected $_storeId = null;
	
	/*
     * Set method and store id, if specified
     *
     * @param array $params
     */
    public function __construct($params = array())
    {
        if ($params) {
            $method = array_shift($params);
            $this->setMethod($method);
            if ($params) {
                $storeId = array_shift($params);
                $this->setStoreId($storeId);
            }
        }
    }

    /*
     * Method code setter
     *
     * @param string|Mage_Payment_Model_Method_Abstract $method
     * @return Mageho_Atos_Model_Config
     */
    public function setMethod($method)
    {
        if ($method instanceof Mage_Payment_Model_Method_Abstract) {
            $this->_methodCode = $method->getCode();
        } elseif (is_string($method)) {
            $this->_methodCode = $method;
        }
        return $this;
    }

    /*
     * Payment method instance code getter
     *
     * @return string
     */
    public function getMethodCode()
    {
        return $this->_methodCode;
    }

    /*
     * Store ID setter
     *
     * @param int $storeId
     * @return Mage_Paypal_Model_Config
     */
    public function setStoreId($storeId)
    {
        $this->_storeId = (int)$storeId;
        return $this;
    }

    /*
     * Check whether method active in configuration and supported for merchant country or not
     *
     * @param string $method Method code
     * @return bool
     */
    public function isMethodActive($method)
    {
        if (Mage::getStoreConfigFlag("payment/{$method}/active", $this->_storeId)) {
            return true;
        }
        return false;
    }

    /*
     * Check whether method available for checkout or not
     * Logic based on merchant country, methods dependence
     *
     * @param string $method Method code
     * @return bool
     */
    public function isMethodAvailable($methodCode = null)
    {
        if ($methodCode === null) {
            $methodCode = $this->getMethodCode();
        }

        $result = true;

        if (!$this->isMethodActive($methodCode)) {
            $result = false;
        }

        switch ($methodCode) {
            case self::METHOD_ATOS_SIPS_PAYMENT_STANDARD:
        	case self::METHOD_ATOS_SIPS_PAYMENT_SEVERAL:
        	case self::METHOD_ATOS_SIPS_PAYMENT_SPRINT:
        	case self::METHOD_ATOS_SIPS_PAYMENT_3XCB:
                if (! $this->merchant_id || ! $this->bin_request || ! $this->bin_response) {
                    $result = false;
                }
                break;
		}
        return $result;
    }
	
	/*
     * Config field magic getter
     * The specified key can be either in camelCase or under_score format
     * Tries to map specified value according to set payment method code, into the configuration value
     * Sets the values into public class parameters, to avoid redundant calls of this method
     *
     * @param string $key
     * @return string|null
     */
    public function __get($key)
    {
        $underscored = strtolower(preg_replace('/(.)([A-Z])/', "$1_$2", $key));
        $value = Mage::getStoreConfig($this->_getSpecificConfigPath($underscored), $this->_storeId);
        $this->$key = $value;
        $this->$underscored = $value;
        return $value;
    }
    
    /*
     * Mapper from Atos/Sips Standard payment actions to Magento payment actions
     *
     * @return string|null
     */
    public function getPaymentAction($action)
    {
        switch ($action) {
            case Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE:
                return self::PAYMENT_ACTION_AUTHORIZE;
            default:
            case Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE:
                return self::PAYMENT_ACTION_AUTHORIZE_CAPTURE;
        }
    }
    
    /*
     * Payment actions source getter
     *
     * @return array
     */
    public function getPaymentActions()
    {
        $paymentActions = array(
            Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE => Mage::helper('adminhtml')->__('Validation'),
            Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE => Mage::helper('adminhtml')->__('Author Capture')
        );
        return $paymentActions;
    }
	
	public function getConfigFile($file, $repertory = 'etc')
	{
	    return Mage::getConfig()->getModuleDir($repertory, 'Mageho_Atos') . DS . $file;
	}
	
	/*
     * Get Atos/Sips authorized countries
     *
     * @return array
     */
    public function getMerchantCountries() 
    {
        $countries = array();
        foreach (Mage::getConfig()->getNode('global/payment/atos/merchant_country')->asArray() as $data) {
            $countries[$data['code']] = $data['name'];
        }

        return $countries;
    }
	
	/*
     * Get merchant country code
     *
     * @return string
     */
    public function getMerchantCountry() 
    {
        $countries = Mage::getStoreConfig('general/country');
        $currentCountryCode = strtolower($countries['default']);
        $atosConfigCountries = $this->getMerchantCountries();

        if (count($atosConfigCountries) === 1) {
            return strtolower($atosConfigCountries[0]);
        }

        if (array_key_exists($currentCountryCode, $atosConfigCountries)) {
            $code = array_keys($atosConfigCountries);
            $key = array_search($currentCountryCode, $code);

            return strtolower($code[$key]);
        }

        return 'fr';
    }
	
    /*
     * Récupère un tableau des devises autorisées
     *
     * @return array $currencies
     */
    public function getCurrencies()
    {
		$currencies = array();
        foreach (Mage::getConfig()->getNode('global/payment/atos/currencies')->asArray() as $data) {
            $currencies[$data['iso']] = $data['code'];
        }
        return $currencies;
    }
    
    /*
     * Get currency code
     *
     * @return string|boolean
     */
    public function getCurrencyCode($currentCurrencyCode) 
    {
	    if (! $currentCurrencyCode) {
		    $currentCurrencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
	    }
        $atosConfigCurrencies = $this->getCurrencies();

        if (array_key_exists($currentCurrencyCode, $atosConfigCurrencies)) {
            return $atosConfigCurrencies[$currentCurrencyCode];
        } else {
            return false;
        }
    }

    /*
     * Récupère un tableau des langages autorisées
     *
     * @return array $languages
     */
    public function getLanguages()
	{
        $languages = array();
        foreach (Mage::getConfig()->getNode('global/payment/atos/languages')->asArray() as $data) {
            $languages[$data['code']] = $data['name'];
        }
        return $languages;
	}
	
	/*
     * Get language code
     *
     * @return string
     */
    public function getLanguageCode() 
    {
        $language = substr(Mage::getStoreConfig('general/locale/code'), 0, 2);
        $atosConfigLanguages = $this->getLanguages();

        if (count($atosConfigLanguages) === 1) {
            return strtolower($atosConfigLanguages[0]);
        }

        if (array_key_exists($language, $atosConfigLanguages)) {
            $code = array_keys($atosConfigLanguages);
            $key = array_search($language, $code);

            return strtolower($code[$key]);
        }

        return 'fr';
    }

    /*
     * Récupère un tableau des modes de paiement autorisés
     *
     * @return array $paymentMeans
     */
	public function getPaymentMeans()
    {
        $paymentMeans = array();
        foreach (Mage::getConfig()->getNode('global/payment/atos/payment_means')->asArray() as $data) {
            $paymentMeans[$data['code']] = $data['name'];
        }
        return $paymentMeans;
    }
    
     /*
	  * Get customer date of birth
	  *
	  * @return string
	  */
	protected function _getCustomerDob() 
	{
		$date = explode(' ', Mage::getSingleton('atos/session')->getCustomerDob());
		return preg_replace('/-/', '', $date[0]);
	}
	
    /*
     * Récupère un tableau des mots clés du champ data 
     *
     * @return array $datafields
     */
	public function getDataFieldKeys()
    {
        $datafields = array();
        foreach (Mage::getConfig()->getNode('global/payment/atos/datafield')->asArray() as $data) {
            $datafields[$data['code']] = $data['name'];
        }
        return $datafields;
    }
	
	/*
	 *
	 *
	 * Generate DATA fields for request
	 *
	 *
	 */
	public function getDatafield($fields = array())
	{
		$datafield = array();
		
		$datafield = array_merge(
			$datafield,
			explode(',', Mage::getStoreConfig($this->_getSpecificConfigPath('datafield'), $this->_storeId)),
			$fields
		);
		
		if ($paymentMeans = $this->getAtosSession()->getData(self::METHOD_ATOS_SIPS_PAYMENT_STANDARD . '_payment_means')) {
			switch ($paymentMeans) {
				/* Donnée spécifique Aurore: Date de naissance */
				case 'AURORE':
					if ($dob = $this->_getCustomerDob()) {
						$datafield['DATE_NAISSANCE'] = $dob;
					}
					$datafield['MODE_REGLEMENT'] = 'MR_CREDIT';
					break;
					
				/* 
				 * Donnée spécifique PayPal: Le numéro de commande PayPal
				 * PP_INVOICEID : ce champ doit faire au maximum 127 caractères et contenir des caractères alpha-numériques
				 */
				case 'PAYPAL':
					if ($orderId = $this->_getOrderId() && ctype_digit($orderId)) {
						$datafield['PP_INVOICEID'] = $orderId;
					}
					break;
			}
		}
		
		/*
		 *
		 * Sogenactif solutions
		 * Société générale
		 * Sprint Secure & 3XCB
		 *
		 */
		if ($this->getMethodCode() == self::METHOD_ATOS_SIPS_PAYMENT_SPRINT ||
			$this->getMethodCode() == self::METHOD_ATOS_SIPS_PAYMENT_3XCB) 
		{
			$billing = $this->_getOrder()->getBillingAddress();

	        $telephone = $billing->getTelephone();
	        if (strlen($telephone) > 10) {
	            $telephone = str_replace(array('.', ' ', '-'), '', $telephone);
	        }
	        
	        $fax = $billing->getFax();
	        if (strlen($fax) > 10) {
	            $fax = str_replace(array('.', ' ', '-'), '', $fax);
	        }

			if ($this->getMethodCode() == self::METHOD_ATOS_SIPS_PAYMENT_SPRINT) {
				$sprint  = Mage::getSingleton('atos/method_sprint');
				
	        	$datakey = 'SOLUTIONSPRINTSECURE_DATA';
	        	
				$data = '#' . substr(trim($sprint->getConfigData('authentication_chain')), 0, 512) . '#';
				$data.= substr(trim($sprint->getConfigData('style_route')), 0, 5) . '######';
				$data.= substr(trim($sprint->getConfigData('pre_capture_bin')), 0, 6) . '#';
	        }
	        
	        if ($this->getMethodCode() == self::METHOD_ATOS_SIPS_PAYMENT_3XCB) {
				$ff3xcb = Mage::getSingleton('atos/method_3xcb');
				
				$datakey = '3XCBFRANFINANCE_DATA';
				
				$data = '#' . substr(trim($ff3xcb->getConfigData('authentication_chain')), 0, 512) . '#';
				$data.= substr(trim($ff3xcb->getConfigData('style_route')), 0, 5) . '####';
				$data.= substr(trim($ff3xcb->getConfigData('timer')), 0, 3) . '##';
				$data.= substr(trim($sprint->getConfigData('pre_capture_bin')), 0, 6) . '#';
			}
	        
	        // Doit etre sous la forme => Mr, Mme, Mle
	        //$data .= substr(trim($billing->getPrefix()), 0, 3) . '#';
	        $data .= '#';
	        
	        $data .= substr(trim($billing->getLastname()), 0, 24) . '##';
	        $data .= substr(trim($billing->getFirstname()), 0, 15) . '#####';
	        $data .= substr(trim($billing->getStreet1()), 0, 32) . '#';
	        
	        if ($billing->getStreet2()) {
	        	$data .= substr(trim($billing->getStreet2()), 0, 32) . '#';
	        } else {
	        	$data .= '#';
	        }
	        
	        $data .= substr(trim($billing->getPostcode()), 0, 5) . '#';
	        $data .= substr(trim($billing->getCity()), 0, 26) . '#';
	        $data .= substr(trim($telephone), 0, 10) . '#';
	        $data .= substr(trim($fax), 0, 10) . '#';
			
			$datafield[$datakey] = $data;
		}
		
		/*
		 *
		 * 3D Secure Bypass
		 *
		 */
		if ($this->getConfig()->bypass_enabled) 
		{
			$conditions = unserialize($this->getConfig()->conditions);
			if (is_array($conditions) && !empty($conditions))
			{
				$rule = Mage::getModel('salesrule/rule');
				$rule->loadPost($conditions);
	
				$object = new Varien_Object(array('quote' => $this->_getQuote()));
				
				if ($rule->validate($object)) {
					$datafield['3D_BYPASS'] = true;
				}
			}
		}
		
		if ( ! empty($datafield) ) {
			return sprintf(' data="%s;"', implode(';', array_map(function ($v, $k) { return (strlen($k) > 2 ? $k . '=' : '') . $v; }, $datafield, array_keys($datafield))));
		}
	}
	
	/*
     * Get relative credit card image file path
     *
     * @return string
     */
    public function getCardIcon($type)
    {
		$path = Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA) . DS . 'atos' . DS;
		$url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'atos'. DS ;
		$file = Mage::getStoreConfig('atos/images/' . $type . '_icon');
		
		if (! isset($file) && empty($file) || isset($file) && ! file_exists($path . $file)) {
			switch ($type) {
				case 'amex':
					return $url . 'AMEX.gif';
					break;
				case 'aurore':
					return $url . 'AURORE.gif';
					break;
				case 'cb':
					return $url . 'CB.gif';
					break;
				case 'mastercard':
					return $url . 'MASTERCARD.gif';
					break;
				case 'visa':
					return $url . 'VISA.gif';	
					break;
				case 'paylib':
					return $url . 'PAYLIB.gif';
					break;
				case 'paypal':
					return $url . 'PAYPAL.gif';
					break;
			}
		} else {
			return $url . $file;
		}
    }
    
    /*
     * Get relative icon file path
     *
     * @return string
     */
    public function getMethodCardIcon()
    {
		$path = Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA) . DS . 'atos' . DS;
		$url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'atos'. DS ;
		$file = $this->icon;
		
		if (!isset($file) || empty($file) || isset($file) && ! file_exists($path . $file)) {
			return false;
		} else {
			return $url . $file;
		}
    }
    
    /*
     * Get authorized IPs
     *
     * @return array
     */
    public function getAllowedIp()
    {
    	$configAllowedIps = unserialize($this->allowed_ip);
    	$ips = array();
    	foreach ($configAllowedIps as $configAllowedIp) {
	    	$ips[] = current($configAllowedIp);
    	}
    	return $ips;
    }
    
    public function isTestMode() 
	{
	    $certificates = Mage::getSingleton('atos/api_files')->getPredefinedCertificates();
        if (isset($certificates[$this->merchant_id])) {
		    return true;
	    }
        return false;
    }
	
	/*
     * Map any supported payment method into a config path by specified field name
     *
     * @param string $fieldName
     * @return string|null
     */
    protected function _getSpecificConfigPath($fieldName)
    {
        $path = null;
        switch ($this->_methodCode) {
            case self::METHOD_ATOS_SIPS_PAYMENT_STANDARD:
                $path = $this->_mapStandardFieldset($fieldName);
                break;
			case self::METHOD_ATOS_SIPS_PAYMENT_SEVERAL:
                $path = $this->_mapSeveralFieldset($fieldName);
                break;
            case self::METHOD_ATOS_SIPS_PAYMENT_SPRINT:
                $path = $this->_mapSprintFieldset($fieldName);
            	break;
            case self::METHOD_ATOS_SIPS_PAYMENT_3XCB:
                $path = $this->_map3xcbFieldset($fieldName);
            	break;
        }
        if ($path === null) {
            $path = $this->_mapConfigurationFieldset($fieldName);
        }
        if ($path === null) {
	        $path = $this->_mapSecureCodeFieldset($fieldName);
        }
        if ($path === null) {
            $path = $this->_mapIntegrationFieldset($fieldName);
        }
        if ($path === null) {
            $path = $this->_mapSecurityEnhancementFieldset($fieldName);
        }
        return $path;
    }
	
	/*
     * Map Website Atos Payment Standard Settings
     *
     * @param string $fieldName
     * @return string|null
     */
    protected function _mapStandardFieldset($fieldName)
    {
        if (!$this->_methodCode) {
            return null;
        }
        switch ($fieldName)
        {
            case 'title':
			case 'sort_order':
			case 'payment_means':
			case 'payment_action':
			case 'capture_day':
			case 'cms_block':
			case 'icon':
            case 'allowspecific':
            case 'specificcountry':
            case 'min_order_total':
            case 'max_order_total':
                return "atos/{$this->_methodCode}/{$fieldName}";
        	case 'active':
        		return "payment/{$this->_methodCode}/{$fieldName}";
			default:
				return null;
        }
    }
	
	/*
     * Map Website Atos Payment Several Settings
     *
     * @param string $fieldName
     * @return string|null
     */
    protected function _mapSeveralFieldset($fieldName)
    {
        if (!$this->_methodCode) {
            return null;
        }
        switch ($fieldName)
        {
            case 'title':
			case 'sort_order':
			case 'payment_means':
			case 'nb_payment':
			case 'icon':
			case 'cms_block':
            case 'allowspecific':
            case 'specificcountry':
            case 'min_order_total':
            case 'max_order_total':
                return "atos/{$this->_methodCode}/{$fieldName}";
        	case 'active':
        		return "payment/{$this->_methodCode}/{$fieldName}";
			default:
				return null;
        }
    }
    
    /*
     * Map Website Atos Payment Sprint Secure Franfinance Settings
     *
     * @param string $fieldName
     * @return string|null
     */
    protected function _mapSprintFieldset($fieldName)
    {
        if (!$this->_methodCode) {
            return null;
        }
        switch ($fieldName)
        {
            case 'title':
			case 'sort_order':
			case 'icon':
			case 'authentication_chain':
			case 'style_route':
			case 'pre_capture_bin':
            case 'allowspecific':
            case 'specificcountry':
            case 'min_order_total':
            case 'max_order_total':
                return "atos/{$this->_methodCode}/{$fieldName}";
        	case 'active':
        		return "payment/{$this->_methodCode}/{$fieldName}";
			default:
				return null;
        }
    }
    
    /*
     * Map Website Atos Payment 3xCB Franfinance Settings
     *
     * @param string $fieldName
     * @return string|null
     */
    protected function _map3xcbFieldset($fieldName)
    {
        if (!$this->_methodCode) {
            return null;
        }
        switch ($fieldName)
        {
            case 'title':
			case 'sort_order':
			case 'icon':
			case 'authentication_chain':
			case 'style_route':
			case 'timer':
			case 'pre_capture_bin':
            case 'allowspecific':
            case 'specificcountry':
            case 'min_order_total':
            case 'max_order_total':
                return "atos/{$this->_methodCode}/{$fieldName}";
        	case 'active':
        		return "payment/{$this->_methodCode}/{$fieldName}";
			default:
				return null;
        }
    }
	
	/*
     * Map Configuration Atos Settings
     *
     * @param string $fieldName
     * @return string|null
     */
    protected function _mapConfigurationFieldset($fieldName)
    {
        switch ($fieldName)
        {
            case 'merchant_id':
                return "atos/configuration/{$fieldName}";
			default:
				return null;
        }
    }
	
	/*
     * Map Integration Settings
     *
     * @param string $fieldName
     * @return string|null
     */
    protected function _mapIntegrationFieldset($fieldName)
    {
        switch ($fieldName)
        {
            case 'bin_request':
            case 'bin_response':
			case 'pathfile':
			case 'templatefile':
			case 'datafield':
			case 'redirect':
                return "atos/integration/{$fieldName}";
			default:
				return null;
        }
    }
	
	/*
     * Map SecureCode Settings
     *
     * @param string $fieldName
     * @return string|null
     */
    protected function _mapSecureCodeFieldset($fieldName)
    {
        switch ($fieldName)
        {
			case 'bypass_enabled':
			case 'conditions':
                return "atos/securecode/{$fieldName}";
			default:
				return null;
        }
    }
	
	/*
     * Map Security Enhancement During Response Return
     *
     * @param string $fieldName
     * @return string|null
     */
    protected function _mapSecurityEnhancementFieldset($fieldName)
    {
        switch ($fieldName)
        {
            case 'log_ip_address':
			case 'check_ip_address':
			case 'allowed_ip':
                return "atos/security_enhancement/{$fieldName}";
			default:
				return null;
        }
    }
}