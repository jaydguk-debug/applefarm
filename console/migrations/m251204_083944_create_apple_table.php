<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%apple}}`.
 */
class m251204_083944_create_apple_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%apple}}', [
            'id' => $this->primaryKey(),
            'color' => $this->string(20)->notNull()->comment('The color of the apple'),
            'created_at' => $this->integer()->notNull()->comment('Unix timestamp when the apple appeared on the tree'),
            'fallen_at' => $this->integer()->defaultValue(null)->comment('Unix timestamp when the apple fell from the tree'),
            'status' => $this->smallInteger()->notNull()->defaultValue(1)->comment('1 - on_tree, 2 - on_ground, 3 - rotten'),
            'eaten_percentage' => $this->decimal(5, 2)->notNull()->defaultValue(0.00)->comment('Percentage of the apple that has been eaten'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT="Table for storing apple objects"');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%apple}}');
    }
}
