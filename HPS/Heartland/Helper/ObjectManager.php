<?php
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