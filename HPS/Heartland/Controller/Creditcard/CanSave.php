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

namespace HPS\Heartland\Controller\Creditcard;
use HPS\Heartland\Model\StoredCard;


/**
 * Class CanSave
 * /heartland/hss/cansave/ URI segment
 * @package HPS\Heartland\Controller\Hss
 */
class CanSave extends \Magento\Framework\App\Action\Action
{
    //
    /** \HPS\Heartland\Controller\Hss\CanSave::execute
     * checks if card saving is enabled and prints a 1 for enabled or 0 for disabled
     */
    public function execute(){
        // \HPS\Heartland\Model\StoredCard::getCanStoreCards
        echo ((int) StoredCard::getCanStoreCards());
    }
}