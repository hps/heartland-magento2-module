<?php
/**
 *  Heartland payment method model
 *
 * @category    HPS
 * @package     HPS_Heartland
 * @author      Heartland Developer Portal <EntApp_DevPortal@e-hps.com>
 * @copyright   Heartland (http://heartland.us)
 * @license     https://github.com/hps/heartland-magento2-module/blob/master/LICENSE.md
 */
namespace HPS\Heartland\Model\ResourceModel\TokenData;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
  
    public function _construct()
    {
        $this->_init(
            'HPS\Heartland\Model\TokenData',
            'HPS\Heartland\Model\ResourceModel\TokenData'
        );
    }
}
