<?php

namespace Montapacking\MontaCheckout\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        $connection = $installer->getConnection();
        $connection
            ->addColumn(
                $installer->getTable('quote_address'),
                'montapacking',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table ::TYPE_TEXT,
                    'nullable' => true,
                    'default' => null,
                    'comment' => 'Montapacking Data',
                    'after' => 'free_shipping'
                ]
            );
        $connection
            ->addColumn(
                $installer->getTable('sales_order'),
                'montapacking',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table ::TYPE_TEXT,
                    'nullable' => true,
                    'default' => null,
                    'comment' => 'Montapacking Data',
                    'after' => 'delivery_date'
                ]
            );

        $installer->endSetup();
    }
}
