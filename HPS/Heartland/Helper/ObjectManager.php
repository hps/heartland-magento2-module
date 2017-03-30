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

/**
 * Created by PhpStorm.
 * User: charles.simmons
 * Date: 3/16/2016
 * Time: 9:41 AM
 */

namespace HPS\Heartland\Helper;
// HPS\Heartland\Helper\ObjectManager::getObjectManager()
use \Magento\Framework\App\ObjectManager as MAGE_OM;

/**
 * Class ObjectManager
 *
 * @package HPS\Heartland\Helper
 */
class ObjectManager
{
    /**
     * @return \Magento\Framework\App\ObjectManager
     */
    public static function getObjectManager(){
        return MAGE_OM::getInstance();
        }
}