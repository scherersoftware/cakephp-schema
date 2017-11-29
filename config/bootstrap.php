<?php
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Schema\Shell\Task\SchemaSaveTask;

$configKey = 'Schema.autoSaveSchemaAfterMigrate';

if (!Configure::check($configKey) || Configure::read($configKey) === true) {
    EventManager::instance()->on('Migration.afterMigrate', function (Event $event) {
        $task = new SchemaSaveTask;
        $task->interactive = false;
        $task->initialize();
        $task->loadTasks();
        $input = $event->getSubject()->getManager()->getInput();
        $task->save(['connection' => $input->getOption('connection')]);
    });
}
