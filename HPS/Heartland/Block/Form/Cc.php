<?php
/**
 *  Heartland payment method model
 *
 *  @category    HPS
 *  @package     HPS_Heartland
 *  @author      Heartland Developer Portal <EntApp_DevPortal@e-hps.com>
 *  @copyright   Heartland (http://heartland.us)
 *  @license     https://github.com/hps/heartland-magento2-extension/blob/master/LICENSE.md
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
    protected $_template = 'HPS_Heartland::form/cc.phtml';

    /** in context gets stored cards from database for the selected customer
     * @return array
     * @throws \Exception
     */
    public function getCcTokens(){    
       $customer_id = $this->getData('method')->getData('info_instance')->getQuote()->getOrigData('customer_id');       
       $customer_email = $this->getData('method')->getData('info_instance')->getQuote()->getOrigData('customer_email');
       //customer id not available since magento 2.1.5 version. so customer id retrieved from customer mail id
       if($customer_id === null && !empty($customer_email)){
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $customerFactory = $objectManager->get('\Magento\Customer\Api\CustomerRepositoryInterface');
            $customer = $customerFactory->get($customer_email);
            $customer_id = $customer->getId();
        }

        return \HPS\Heartland\Model\StoredCard::getStoredCardsAdmin($customer_id);
    }
    
    /**
     * Internal constructor, that is called from real constructor
     *
     * @return void
     */
    protected function _construct() {
        parent::_construct(); 
    }   
}
