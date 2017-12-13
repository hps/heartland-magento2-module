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


namespace HPS\Heartland\Controller\Creditcard;

use \Magento\Framework\App\Action\Action;
use \HPS\Heartland\Model\StoredCard as HPS_STORED_CARDS;

/**
 * Class Delete
 *
 * @package HPS\Heartland\Controller\Creditcard
 * \HPS\Heartland\Controller\Creditcard\Delete
 */
class Delete extends Action
{
    /**
     * @var \HPS\Heartland\Model\StoredCard
     */
    private $hpsStoredCard;
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \HPS\Heartland\Model\StoredCard $hpsStoredCard
    ) {
        parent::__construct($context);
        $this->hpsStoredCard = $hpsStoredCard;
    }
    
    /** Provides and ajax callable way to delete a saved token by id
     * @throws \Exception
     */
    public function execute()
    {
        $this->hpsStoredCard->deleteStoredCards((int) $this->getRequest()->getParam('t'));
    }
}
