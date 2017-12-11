<?php

use yii\db\Migration;

/**
 * Class m171015_085210_filemanager
 * php yii migrate/up --migrationPath=@education/runtime/tmp-extensions/yii2-file-manager/migrations
 */
class m171015_085210_flysystem_wrapper extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%file_storage}}', [
            'id' => $this->primaryKey(),
            'path' => $this->string(255)->notNull()->unique(),
            'type' => $this->string(15)->notNull(),
            'contents' => 'LONGBLOB',
            'size' => $this->integer()->notNull()->defaultValue(0),
            'mimetype' => $this->string(127),
            'timestamp' => $this->integer()->notNull()->defaultValue(0)
        ], $tableOptions);
    }

    public function safeDown()
    {
        $this->dropTable('{{%file_storage}}');
    }
}
