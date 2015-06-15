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
 
class Mageho_Atos_Block_Standard_Redirect extends Mage_Core_Block_Template
{
	protected $_model;
	
    protected function _toHtml()
    {
		$this->_model = Mage::getModel('atos/method_standard');
		$this->_model->callRequest();
		
		if ($this->_model->getError()) 
		{
		    return '<pre>'.$this->_model->getSystemHtml().'</pre>';
		} else {
			$this->setSelectedMethod(Mage::getSingleton('atos/session')->getAtosStandardPaymentMeans())
				->setSystemFormUrl($this->_model->getSystemUrl())
				->setSystemHtml($this->_model->getSystemHtml())
				->setTemplate('mageho/atos/standard/redirect.phtml');
					
			return parent::_toHtml();
		}
    }
    
    public function getStandardPaymentModel()
    {
	    return $this->_model;
    }
}