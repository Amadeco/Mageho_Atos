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
 
class Mageho_Atos_Block_Several_Catalog extends Mage_Catalog_Block_Product_View
{
	public function getNbPayment() 
	{
		return Mage::helper('atos')->getNbPayment();	
	}
	
	public function getSeveralPrice() 
	{
		$price = $this->_getFinalProductPrice() / $this->getNbPayment();
		return Mage::helper('core')->currency($price);
	}
	
	public function getCmsBlockUrl()
	{
		return Mage::getUrl('atos/method_several/information');
	}
	
	protected function _prepareLayout() 
	{
		if ($this->isAvailable()) {
			$headBlock = $this->getLayout()->getBlock('head');
			if ($headBlock) {
				$headBlock->addCss('css/mageho/atos/atos.css');
			}
		}
		return parent::_prepareLayout();
	}
	
	protected function _toHtml()
	{
		if ($this->isAvailable()) {
			return parent::_toHtml();	
		}
	}
	
	protected function isAvailable() 
	{
		$_product = $this->getProduct();
		
		$minOrderTotal = (float) Mage::getStoreConfig('atos/atoswpseveral/min_order_total');
		$maxOrderTotal = (float) Mage::getStoreConfig('atos/atoswpseveral/max_order_total');
		
		if (! Mage::getStoreConfigFlag('payment/atoswpseveral/active')) {
			return false;
		}
		if ($_product->isConfigurable() || $_product->hasOptions()) {
			return false;
		}
		if ( isset($minOrderTotal) && $minOrderTotal > 0 && $this->_getFinalProductPrice() < $minOrderTotal) {
			return false;
		}
		if ( isset($maxOrderTotal) && $maxOrderTotal > 0 && $this->_getFinalProductPrice() > $maxOrderTotal) {
			return false;
		}
		if ( Mage::helper('catalog')->canApplyMsrp($_product) ) {
			return false;
		}
		
		return true;
	}
	
	protected function _getFinalProductPrice()
	{
		$_weeeHelper = $this->helper('weee');
		$_taxHelper  = $this->helper('tax');
		$_product = $this->getProduct();
		$_storeId = $_product->getStoreId();
		$_finalPrice = $_product->getFinalPrice();
		$_finalPriceInclTax = $_taxHelper->getPrice($_product, $_finalPrice, true);
		
		$_weeeTaxAmount = $_weeeHelper->getAmountForDisplay($_product);
		
		if ($_weeeHelper->typeOfDisplay($_product, array(Mage_Weee_Model_Tax::DISPLAY_INCL_DESCR, Mage_Weee_Model_Tax::DISPLAY_EXCL_DESCR_INCL, 4)))
		{
			$_weeeTaxAmount = $_weeeHelper->getAmount($_product);
		}
		
		$_weeeTaxAmountInclTaxes = $_weeeTaxAmount;
		if ($_weeeHelper->isTaxable() && !$_taxHelper->priceIncludesTax($_storeId))
		{
			$_attributes = $_weeeHelper->getProductWeeeAttributesForRenderer($_product, null, null, null, true);
			$_weeeTaxAmountInclTaxes = $_weeeHelper->getAmountInclTaxes($_attributes);
		}
		
		$finalProductPrice = $_finalPriceInclTax + $_weeeTaxAmountInclTaxes;
		
		return $finalProductPrice;
	}
}