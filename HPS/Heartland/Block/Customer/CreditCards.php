<?php

namespace HPS\Heartland\Block\Customer;

class CreditCards extends \Magento\Vault\Block\Customer\CreditCards
{

    public $template = 'HPS_Heartland::cards_list.phtml';
    
    /**
     * @var \HPS\Heartland\Model\StoredCard
     */
    private $hpsStoredCard;
    
    public function __construct(
        \HPS\Heartland\Model\StoredCard $hpsStoredCard
    ) {
        $this->hpsStoredCard = $hpsStoredCard;
    }
    
    public function getStoredCards()
	{
        return $this->hpsStoredCard->getStoredCards();
    }
}
