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

namespace HPS\Heartland\Setup;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class InstallSchema implements InstallSchemaInterface
{

    /**
     * {@inheritdoc}
     */
    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $installer = $setup;
        $installer->startSetup();

        $table_hps_heartland_storedcard = $setup->getConnection()->newTable($setup->getTable('hps_heartland_storedcard'));


        $table_hps_heartland_storedcard->addColumn(
            'heartland_storedcard_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            array('identity' => true,'nullable' => false,'primary' => true,'unsigned' => true,),
            'Entity ID'
        );



        $table_hps_heartland_storedcard->addColumn(
            'token_value',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => False],
            'token_value'
        );



        $table_hps_heartland_storedcard->addColumn(
            'customer_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => False],
            'customer_id'
        );



        $table_hps_heartland_storedcard->addColumn(
            'cc_last4',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            4,
            [],
            'cc_last4'
        );



        $table_hps_heartland_storedcard->addColumn(
            'cc_type',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            [],
            'cc_type'
        );



        $table_hps_heartland_storedcard->addColumn(
            'cc_exp_month',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['unsigned' => True],
            'cc_exp_month'
        );
        $table_hps_heartland_storedcard->addColumn(
            'cc_exp_year',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['unsigned' => True],
            'cc_exp_year'
        );

        $table_hps_heartland_storedcard->addColumn(
            'dt',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            [],
            'dt'
        );

        $setup->getConnection()->createTable($table_hps_heartland_storedcard);

        $setup->endSetup();
    }
}