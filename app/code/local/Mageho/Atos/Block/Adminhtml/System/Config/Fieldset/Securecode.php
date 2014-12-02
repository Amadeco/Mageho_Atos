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

class Mageho_Atos_Block_Adminhtml_System_Config_Fieldset_Securecode 
	extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
	protected $_pathConfiguration = 'atos/securecode/conditions'; 
	protected $_backendModel = 'atos/system_config_backend_rules';
    protected $_dummyElement;
	protected $_fieldRenderer;
	protected $_values;
	
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        $model = Mage::getModel('salesrule/rule');
        
        if ($websiteCode = $this->getRequest()->getParam('website')) {
            $data = Mage::app()->getWebsite($websiteCode)->getConfig($this->_pathConfiguration);
        }
        if ($storeCode = $this->getRequest()->getParam('store')) {
            $data = Mage::app()->getStore($storeCode)->getConfig($this->_pathConfiguration);
        }
        if (empty($data)) {
        	$data  = Mage::getStoreConfig($this->_pathConfiguration, 0);
        }
			
        if (! empty($data)) {
            $model->loadPost(unserialize($data));
        }
        
        $html = $this->_getHeaderHtml($element);

        foreach ($element->getSortedElements() as $field) {
        	if ($field->getId() == 'atos_securecode_conditions') {
            	$field->setRule($model)
            		->setRenderer(Mage::getBlockSingleton('rule/conditions'));
            	
				$html.= '<tr id="row_' . $field->getId() . '">' .
							'	<td class="label"><label for="' . $field->getId() . '">' . $field->getLabel() . '</label></td>' . 
							'	<td class="value"><div id="' . $field->getId() . '">' . $field->toHtml() . '</div></td>' . 
							'	<td class="scope-label">' . $field->getScopeLabel() . '</td>' . 
							'	<td class=""></td>' . 
							'</tr>';
			} else {
				$html.= $field->toHtml();
			}
        }

        $html .= $this->_getFooterHtml($element);
        
    	return $html;
    }
    
    /**
     * Return js code for fieldset:
     * - observe fieldset rows;
     * - apply collapse;
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @param bool $tooltipsExist Init tooltips observer or not
     * @return string
     */
    protected function _getExtraJs($element, $tooltipsExist = false)
    {
        $id = $element->getHtmlId();
        $js = "Fieldset.applyCollapse('{$id}');";
        
        /* To support Sales Rules Conditionnal */
        $js.= "var {$element->getHtmlId()} = new VarienRulesForm('{$element->getHtmlId()}', '{$this->getUrl('*/promo_quote/newConditionHtml/form/rule_conditions_fieldset')}');";
        if ($element->getReadonly()) {
			$js.= "{$element->getHtmlId()}.setReadonly(true);";
    	}
        
        return Mage::helper('adminhtml/js')->getScript($js);
    }
}