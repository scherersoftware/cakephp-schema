<?php
namespace Schema\Shell\Task;

use Bake\Shell\Task\SimpleBakeTask;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Exception;

class SeedGenerateTask extends SimpleBakeTask
{
    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_config = [
        'connection' => 'default',
        'seed' => 'config/seed.php',
        'path' => 'config/schema.php',
        'no-interaction' => false
    ];

    /**
     * {@inheritDoc}
     */
    public function name()
    {
        return 'seed';
    }

    /**
     * {@inheritDoc}
     */
    public function fileName($name)
    {
        return $this->_config['seed'];
    }

    /**
     * {@inheritDoc}
     */
    public function template()
    {
        return 'Schema.config/seed';
    }

    /**
     * {@inheritDoc}
     */
    public function getPath()
    {
        return ROOT . DS;
    }

    /**
     * main() method.
     *
     * @return bool|int Success or error code.
     */
    public function generate(array $options = [])
    {
        // Hook into the bake process to load our SchemaHelper
        EventManager::instance()->on('Bake.initialize', function (Event $event) {
            $view = $event->subject;
            $view->loadHelper('Schema.Schema');
        });

        $this->_config = array_merge($this->_config, $this->params, $options);
        if ($this->_config['no-interaction']) {
            $this->interactive = false;
        }

        if (!file_exists($this->_config['path'])) {
            throw new Exception(sprintf('Schema file "%s" does not exist.', $this->_config['path']));
        }

        parent::bake('seed');
    }

    /**
     * Called by bake for retrieving view vars.
     *
     * @return array
     */
    public function templateData()
    {
        $schema = require $this->_config['path'];
        $seedData = [];

        $connection = ConnectionManager::get($this->_config['connection']);
        $schemaCollection = $connection->schemaCollection();

        if (!($excludedTables = Configure::read('Schema.GenerateSeed.excludedTables'))) {
            $excludedTables = [];
        }

        foreach ($schema['tables'] as $tableName => $tableSchema) {
            if (in_array($tableName, $excludedTables)) {
                continue;
            }
            $model = Inflector::camelize($tableName);
            $data = $this->getRecordsFromTable($model, $tableName)->toArray();
            if (empty($data)) {
                continue;
            }
            $seedData[$tableName] = $data;
        }
        return [
            'seedData' => $seedData
        ];
    }


    /**
     * Interact with the user to get a custom SQL condition and use that to extract data
     * to build a fixture.
     *
     * @param string $modelName name of the model to take records from.
     * @param string|null $useTable Name of table to use.
     * @return array Array of records.
     */
    public function getRecordsFromTable($modelName, $useTable = null)
    {
        $recordCount = (isset($this->params['count']) ? $this->params['count'] : false);
        $conditions = (isset($this->params['conditions']) ? $this->params['conditions'] : '1=1');
        $model = $this->findModel($modelName, $useTable);

        $records = $model->find('all')
            ->where($conditions)
            ->hydrate(false);

        if ($recordCount) {
            $records->limit($recordCount);
        }

        return $records;
    }

    /**
     * Return a Table instance for the given model.
     *
     * @param string $modelName Camelized model name
     * @param string $useTable Table to use
     * @return Cake\ORM\Table
     */
    public function findModel($modelName, $useTable)
    {
        $model = TableRegistry::get($modelName);
        // This means we have not found a Table implementation in the app namespace
        // Iterate through loaded plugins and try to find the table
        if (get_class($model) == 'Cake\ORM\Table') {
            foreach (\Cake\Core\Plugin::loaded() as $plugin) {
                $ret = TableRegistry::get("{$plugin}.{$modelName}");
                if (get_class($ret) != 'Cake\ORM\Table') {
                    $model = $ret;
                }
            }
        }
        if (get_class($model) == 'Cake\ORM\Table') {
            $this->out('Warning: Using Auto-Table for ' . $modelName);
        }
        return $model;
    }
}
