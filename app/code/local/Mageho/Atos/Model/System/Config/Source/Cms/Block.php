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
 
class Mageho_Atos_Model_System_Config_Source_Cms_Block
{
    protected $_options;

    public function toOptionArray()
    {
        if (! $this ->_options)
        {
        	$options = Mage::getModel('cms/block')->getCollection()
            	->load()
            	->toOptionArray();
        
            $this->_options = array(array('value' => '', 'label' => Mage::helper('catalog')->__('Please select static block ...')));
            $this->_options = array_merge($this->_options, $options);
        }
        return $this->_options;
    }
}