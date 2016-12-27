<?php
/**
 *  Heartland payment method model
 *
 *  @category    HPS
 *  @package     HPS_Heartland
 *  @author      Charlie Simmons <charles.simmons@e-hps.com>
 *  @copyright   Heartland (http://heartland.us)
 *  @license     https://github.com/hps/heartland-magento2-extension/blob/master/LICENSE.md
 */

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
 * @method \Magento\User\Model\User|null getUser()
 *
 * @package HPS\Heartland\Helper
 */
class Admin
{
    /**
     * @return \Magento\Backend\Model\Auth\Session
     */
    public static function getSession(){
        return HPS_OM::getObjectManager()->get('Magento\Backend\Model\Auth\Session');
    }

    /**
     * @return bool
     */
    public static function isLoggedIn(){
        return self::getSession()->isLoggedIn();
    }

    /**
     * @return bool|\Magento\User\Model\User
     */
    public static function getAdmin(){
        return self::isLoggedIn() ? (int)self::getSession()->getUser() : false;
    }
    /**
     * @return bool|int
     */
    public static function getAdminID(){
        return self::isLoggedIn() ? (int)self::getAdmin()->getId() : false;
    }

}
