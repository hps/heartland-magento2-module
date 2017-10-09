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
 *
 * @package HPS\Heartland\Helper
 */
class Customer
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $authSession;
    
    public function __construct(
        \Magento\Customer\Model\Session $authSession
    ) {
        $this->authSession = $authSession;
    }
    
    /**
     * @return \Magento\Customer\Model\Session
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
     * @return bool|int
     */
    public static function getCustID()
    {
        return self::isLoggedIn() ? (int)self::getSession()->getCustomerId() : false;
    }
}
