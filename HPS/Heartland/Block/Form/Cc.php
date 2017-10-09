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
    private $template = 'HPS_Heartland::form/cc.phtml';
    private $customerRepository;
    
    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    ) {
        $this->customerRepository = $customerRepository;
    }

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
                $customer = $this->customerRepository->get($customerEmail);
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
}
