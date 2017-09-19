<?php
/**
 *  Heartland payment method model
 *
 * @category    HPS
 * @package     HPS_Heartland
 * @author      Heartland Developer Portal <EntApp_DevPortal@e-hps.com>
 * @copyright   Heartland (http://heartland.us)
 * @license     https://github.com/hps/heartland-magento2-extension/blob/master/LICENSE.md
 */

namespace HPS\Heartland\Block\Form;

use Magento\Payment\Model\MethodInterface;

/**
 * Class Cc
 * @property \Magento\Framework\View\Element\Template\Context $context,
 * @property \Magento\Payment\Model\Config $paymentConfig,
 * @const \Magento\Payment\Block\Form parent
 * @package HPS\Heartland\Block\Form
 */

class Cc extends \Magento\Payment\Block\Form\Cc
{
    private $_template = 'HPS_Heartland::form/cc.phtml';

    /** in context gets stored cards from database for the selected customer
     * @return array
     * @throws \Exception
     */
    public function getCcTokens()
    {
        $customerId = $this->getData('method')->getData('info_instance')->getQuote()->getOrigData('customer_id');
        $customerEmail = $this->getData('method')->getData('info_instance')->getQuote()->getOrigData('customer_email');
       //Retrieve customer id from customer mail id
        if ($customerId === null && !empty($customerEmail)) {
            try {
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $customerFactory = $objectManager->get('\Magento\Customer\Api\CustomerRepositoryInterface');
                $customer = $customerFactory->get($customerEmail);
                $customerId = $customer->getId();
            } catch (\Exception $e) {
                $customerId = null;
            }
        }

        return \HPS\Heartland\Model\StoredCard::getStoredCardsAdmin($customerId);
    }
    
    public function getCanSaveCards()
    {
        return (int) \HPS\Heartland\Model\StoredCard::getCanStoreCards();
    }
    
    /**
     * Internal constructor, that is called from real constructor
     *
     * @return void
     */
    private function _construct()
    {
        parent::_construct();
    }
}
