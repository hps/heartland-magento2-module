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
namespace HPS\Heartland\Model\ResourceModel;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class TokenData extends AbstractDb
{

    /**
     * Define main table
     *
     */
    protected function _construct()
    {
        $this->_init('hps_heartland_storedcard', 'heartland_storedcard_id');
    }
}
