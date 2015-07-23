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

require_once('Mageho/Atos/controllers/PaymentController.php');

class Mageho_Atos_Payment_SprintController extends Mageho_Atos_PaymentController
{
    /**
     * Get current Atos Method Instance
     *
     * @return Mageho_Atos_Model_Method_Sprint
     */
    public function getMethodInstance()
    {
        return Mage::getSingleton('atos/method_sprint');
    }
}