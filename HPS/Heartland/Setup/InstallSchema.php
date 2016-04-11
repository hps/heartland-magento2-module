<?php
/**
 * Created by PhpStorm.
 * User: charles.simmons
 * Date: 3/7/2016
 * Time: 3:17 PM
 */
namespace HPS\Heartland\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $tableName = $installer->getTable('hps_heartland_storedcard');
        // Check if the table already exists
        if ($installer->getConnection()->isTableExists($tableName) != true) {



            /*we also need to get the vendor files to the correct location
            Apparently Magento 2 will not copy files using composer unless its refered to in the root composer.json
            or unless composer is run manually*/


            $baseDir = \HPS\Heartland\Helper\Data::getRoot();
            exec( 'cd ' . $baseDir . ' && composer require hps/heartland-php', $output);
            /**/

            // Create tutorial_simplenews table
            $table = $installer->getConnection()
                ->newTable($tableName)
                ->addColumn(
                    'storedcard_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true
                    ],
                    'record id'
                )
                ->addColumn(
                    'dt',
                    Table::TYPE_DATETIME,
                    null,
                    [],
                    'Multi Use Token'
                )
                ->addColumn(
                    'customer_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['nullable' => false,
                     'unsigned' => true ],
                    'Customer Entity'
                )
                ->addColumn(
                    'token_value',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => false],
                    'Multi Use Token'
                )
                ->addColumn(
                    'cc_type',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => false],
                    'Multi Use Toke card typen'
                )
                ->addColumn(
                    'cc_last4',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => false],
                    'last4'
                )
                ->addColumn(
                    'cc_exp_month',
                    Table::TYPE_TEXT,
                    2,
                    ['nullable' => false],
                    'exp_m'
                )
                ->addColumn(
                    'cc_exp_year',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => false],
                    'exp_y'
                )

                ->addForeignKey(
                    $installer->getFkName(
                        'hps_heartland_storedcard',
                        'customer_id',
                        'customer_entity',
                        'entity_id'),
                    'customer_id',
                    $installer->getTable('customer_entity'),
                    'entity_id',
                    Table::ACTION_CASCADE,
                    Table::ACTION_CASCADE
                )/**/
                ->setComment('Stored Cards');
            $installer->getConnection()->createTable($table);
        }

        $installer->endSetup();
    }
} //hps_vault/Setup/InstallSchema.php