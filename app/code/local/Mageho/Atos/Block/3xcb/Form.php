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

class Mageho_Atos_Block_3xcb_Form extends Mage_Payment_Block_Form
{
    /**
     * Payment method code
     * @var string
     */
    protected $_methodCode = Mageho_Atos_Model_Config::METHOD_ATOS_SIPS_PAYMENT_3XCB;
    
	protected $_paymentMeans = array();
	
    protected function _prepareLayout()
    {
	    parent::_construct();
        
        $this->_config = Mage::getModel('atos/config')->setMethod($this->getMethodCode());
                    
        $this->setTemplate('mageho/atos/3xcb/form.phtml');
        
        if ($this->_config->getMethodCardIcon()) {
	        $mark = Mage::getConfig()->getBlockClassName('core/template');
	        $mark = new $mark;
	        $mark->setTemplate('mageho/atos/3xcb/mark.phtml')
	        	->setMethodTitle($this->_config->title)
	        	->setIcon($this->_config->getMethodCardIcon());
	        
	        $this->setMethodTitle('')
        		->setMethodLabelAfterHtml($mark->toHtml());
        } else {
	        $this->setMethodTitle($this->_config->title);
        }
        	
        return parent::_construct();
    }
	
	/**
     * Payment method code getter
     * @return string
     */
    public function getMethodCode()
    {
        return $this->_methodCode;
    }
}