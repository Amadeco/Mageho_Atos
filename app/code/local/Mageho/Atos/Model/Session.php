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

class Mageho_Atos_Model_Session extends Mage_Core_Model_Session_Abstract
{
    protected $_quoteId;
    protected $_response;
    protected $_redirectMessage;
    protected $_redirectTitle;
    
    public function __construct()
    {
        $this->init('atos');
    }
    
    /**
     * Unset all data associated with object
     */
    public function unsetAll()
    {
        parent::unsetAll();
        $this->_quoteId = null;
        $this->_response = null;
        $this->_redirectMessage = null;
        $this->_redirectTitle = null;
    }
    
    protected function _getQuoteIdKey() {
        return 'quote_id_' . Mage::app()->getStore()->getWebsiteId();
    }

    public function setQuoteId($quoteId) {
        $this->setData($this->_getQuoteIdKey(), $quoteId);
        return $this;
    }

    public function getQuoteId() {
        return $this->getData($this->_getQuoteIdKey());
    }
    
    protected function _getResponseKey()
    {
        return 'response_' . Mage::app()->getStore()->getWebsiteId();
    }
    
    public function setResponse($response)
    {
        $this->setData($this->_getResponseKey(), $response);
        return $this;
    }
    
    public function getResponse()
    {
        return $this->getData($this->_getResponseKey());
    }
    
    protected function _getRedirectMessageKey()
    {
        return 'redirect_message_' . Mage::app()->getStore()->getWebsiteId();
    }
    
    public function setRedirectMessage($message)
    {
        $this->setData($this->_getRedirectMessageKey(), $message);
        return $this;
    }
    
    public function getRedirectMessage()
    {
        return $this->getData($this->_getRedirectMessageKey());
    }
    
    protected function _getRedirectTitleKey()
    {
        return 'redirect_title_' . Mage::app()->getStore()->getWebsiteId();
    }
    
    public function setRedirectTitle($title)
    {
        $this->setData($this->_getRedirectTitleKey(), $title);
        return $this;
    }
    
    public function getRedirectTitle()
    {
        return $this->getData($this->_getRedirectTitleKey());
    }
}
