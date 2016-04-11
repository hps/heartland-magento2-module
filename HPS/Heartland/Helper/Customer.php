<?php
/**
 * Created by PhpStorm.
 * User: charles.simmons
 * Date: 3/16/2016
 * Time: 9:46 AM
 */

namespace HPS\Heartland\Helper;
use \HPS\Heartland\Helper\ObjectManager as HPS_OM;

/**
 * Class Customer
 *
 * @package HPS\Heartland\Helper
 */
class Customer
{
    /**
     * @return \Magento\Customer\Model\Session
     */
    public static function getSession(){
        return HPS_OM::getObjectManager()->get('Magento\Customer\Model\Session');
    }

    /**
     * @return bool
     */
    public static function isLoggedIn(){
        return self::getSession()->isLoggedIn();
    }

    /**
     * @return bool|int
     */
    public static function getCustID(){
        return self::isLoggedIn() ? (int)self::getSession()->getCustomerId() : false;
    }

}