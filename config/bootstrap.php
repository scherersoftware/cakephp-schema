<?php

use Cake\Event\EventManager;
use Schema\Shell\Task\SchemaSaveTask;

EventManager::instance()->on('Migration.afterMigrate', function () {
        $task = new SchemaSaveTask;
        $task->interactive = false;
        $task->initialize();
        $task->loadTasks();
        $task->save();
    }
);