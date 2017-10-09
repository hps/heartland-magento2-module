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
     * @var \Magento\Backend\Model\Auth\Session
     */
    private $authSession;
    
    public function __construct(
        \Magento\Backend\Model\Auth\Session $authSession
    ) {
        $this->authSession = $authSession;
    }
    
    /**
     * @return \Magento\Backend\Model\Auth\Session
     */
    public static function getSession()
    {
        return $this->authSession;
    }

    /**
     * @return bool
     */
    public static function isLoggedIn()
    {
        return self::getSession()->isLoggedIn();
    }

    /**
     * @return bool|\Magento\User\Model\User
     */
    public static function getAdmin()
    {
        return self::isLoggedIn() ? (int)self::getSession()->getUser() : false;
    }
    /**
     * @return bool|int
     */
    public static function getAdminID()
    {
        return self::isLoggedIn() ? (int)self::getAdmin()->getId() : false;
    }
}
