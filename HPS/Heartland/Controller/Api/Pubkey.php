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

namespace HPS\Heartland\Controller\Api;

/**
 * Class Pubkey
 *
 * @package HPS\Heartland\Controller\Hss
 *
 */
use \HPS\Heartland\Helper\Data as HPS_DATA;
// \HPS\Heartland\Controller\Api\Pubkey
class Pubkey extends \Magento\Framework\App\Action\Action
{
    public function execute(){ // void
        echo((string) HPS_DATA::getPublicKey());
    }
}
