<?php
/**
 * Copyright (c) 2016.
 * Heartland payment method model
 *
 * @category    HPS
 * @package     HPS_Heartland
 * @author      Charlie Simmons <charles.simmons@e-hps.com>
 * @copyright   Heartland (http://heartland.us)
 * @license     https://github.com/hps/heartland-magento2-extension/blob/master/LICENSE.md
 */
namespace HPS\Heartland\Helper;



class HPS_Responses
{

    static
    function getMessageManager()
    {
        /**
         * @var \Magento\Framework\Message\ManagerInterface $messageManager
         */
        $messageManager = HPS_OM::getObjectManager()->get('\Magento\Framework\Message\ManagerInterface');
        return $messageManager;
    }


}
