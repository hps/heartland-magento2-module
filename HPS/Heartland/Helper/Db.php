<?php
/**
 * Created by PhpStorm.
 * User: charles.simmons
 * Date: 3/10/2016
 * Time: 3:59 PM
 */

namespace HPS\Heartland\Helper;
use \HPS\Heartland\Helper\ObjectManager as HPS_OM;

/**
 * Class Db
 *
 * @package HPS\Heartland\Helper
 */
class Db
{
    /**
     * Retrieve connection to resource specified by $resourceName
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     * @throws \DomainException
     * @codeCoverageIgnore
     */
    public static function db_connect(){
        return HPS_OM::getObjectManager()->get('\Magento\Framework\App\ResourceConnection')->getConnection();
    }
}
