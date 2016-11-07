<?php
use Cake\Core\Configure;
use Cake\Event\EventManager;
use Schema\Shell\Task\SchemaSaveTask;

$configKey = 'Schema.autoSaveSchemaAfterMigrate';

if (!Configure::check($configKey) || Configure::read($configKey) === true) {
    EventManager::instance()->on('Migration.afterMigrate', function () {
        $task = new SchemaSaveTask;
        $task->interactive = false;
        $task->initialize();
        $task->loadTasks();
        $task->save();
    });
}
