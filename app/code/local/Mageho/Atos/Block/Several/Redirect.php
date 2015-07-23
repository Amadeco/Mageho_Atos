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
 
class Mageho_Atos_Block_Several_Redirect extends Mage_Core_Block_Template
{
    /**
     * Payment method code
     * @var string
     */
    protected $_methodCode = Mageho_Atos_Model_Config::METHOD_ATOS_SIPS_PAYMENT_SEVERAL;
    
	protected $_model;
    
    protected function _toHtml()
    {
		$this->_model = Mage::getModel('atos/method_several');
		$this->_model->callRequest();
		
		if ($this->_model->getError()) 
		{
		    return '<pre>'.$this->_model->getSystemHtml().'</pre>';
		} else {
			$this->setSelectedMethod($this->_model->getAtosSession()->getData($this->getMethodCode() . '_payment_means'))
				->setSystemFormUrl($this->_model->getSystemUrl())
				->setSystemHtml($this->_model->getSystemHtml())
				->setTemplate('mageho/atos/several/redirect.phtml');
					
			return parent::_toHtml();
		}
    }

    /**
     * Payment method code getter
     * @return string
     */
    public function getMethodCode()
    {
        return $this->_methodCode;
    }
    
    public function getSeveralPaymentModel()
    {
	    return $this->_model;
    }
}