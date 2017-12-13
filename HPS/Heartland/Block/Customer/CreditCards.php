<?php

namespace HPS\Heartland\Block\Customer;

use \Magento\Framework\View\Element\Template;
use \Magento\Vault\Model\CustomerTokenManagement;

class CreditCards extends \Magento\Vault\Block\Customer\CreditCards
{

    public $template = 'HPS_Heartland::cards_list.phtml';
    
    /**
     * @var \HPS\Heartland\Model\StoredCard
     */
    private $hpsStoredCard;
    
    public function __construct(
        Template\Context $context,
        \Magento\Vault\Model\CustomerTokenManagement $customerTokenManagement,        
        \HPS\Heartland\Model\StoredCard $hpsStoredCard
    ) {
        parent::__construct($context, $customerTokenManagement);
        $this->hpsStoredCard = $hpsStoredCard;        
    }
    
    public function getStoredCards()
	{
        return $this->hpsStoredCard->getStoredCards();
    }
}
