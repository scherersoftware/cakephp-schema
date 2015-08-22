<?php
namespace Schema\Shell\Task;

use Cake\Cache\Cache;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Database\Driver\Sqlserver;
use Cake\Database\Schema\Table;
use Cake\Datasource\ConnectionManager;
use Cake\Filesystem\File;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Exception;

class SeedImportTask extends Shell
{
    public $connection = 'default';

    public $tasks = ['SeedGenerator'];

    /**
     * main() method.
     *
     * @return bool|int Success or error code.
     */
    public function import()
    {
        /*if (ENVIRONMENT !== \App\Lib\Environments::DEVELOPMENT) {
            return $this->error('You can only import seed data on development systems.');
        }*/
        $this->seed();
    }

    /**
     * Default configuration.
     *
     * @var array
     */
    private $_config = [
        'connection' => 'default',
        'seed' => 'config/seed.php',
        'truncate' => true
    ];

    /**
     * Insert data from seed.php file into database.
     *
     * @param array $options Set connection name and path to the seed.php file.
     * @return void
     */
    public function seed($options = [])
    {
        $this->_config = array_merge($this->_config, $this->params, $options);

        $data = $this->_readSeed($this->_config['seed']);

        $db = $this->_connection();

        $this->_truncate($db, $data);
        $this->_insert($db, $data);
    }

    /**
     * Truncate the tables if requested. Because of postgres it must run in separate transaction.
     *
     * @param \Cake\Database\Connection $db Connection to run the SQL queries on.
     * @param  array $data List tables and rows.
     * @return void
     */
    protected function _truncate($db, $data = null)
    {
        if ($this->_config['truncate']) {
            $this->_io->out('Truncating ', 0);

            $operation = function ($db) use ($data) {
                $db->disableForeignKeys();
                foreach ($data as $table => $rows) {
                    $this->_io->out('.', 0);
                    $this->_truncateTable($db, $table);
                }
                $db->enableForeignKeys();
            };

            $this->_runOperation($db, $operation);
            $this->_io->out(); // New line
        }
    }

    /**
     * Truncates table. Deletes all rows in the table.
     *
     * @param  \Cake\Datasource\Connection $db Connection where table is stored.
     * @param  string $table Table name.
     * @return void
     */
    protected function _truncateTable($db, $table)
    {
        $schema = $db->schemaCollection()->describe($table);
        $truncateSql = $schema->truncateSql($db);
        foreach ($truncateSql as $statement) {
            $db->execute($statement)->closeCursor();
        }
    }

    /**
     * Insert data into tables.
     *
     * @param \Cake\Database\Connection $db Connection to run the SQL queries on.
     * @param  array $data List tables and rows.
     * @return void
     */
    protected function _insert($db, $data = null)
    {
        $this->_io->out('Seeding ', 0);

        $operation = function ($db) use ($data) {
            $db->disableForeignKeys();
            foreach ($data as $table => $rows) {
                $this->_io->out('.', 0);

                $this->_beforeTableInsert($db, $table);
                $this->_insertTable($db, $table, $rows);
                $this->_afterTableInsert($db, $table);
            }
            $db->enableForeignKeys();
        };

        $this->_runOperation($db, $operation);
        $this->_io->out(); // New line
    }

    /**
     * Insert data into table.
     *
     * @param  \Cake\Datasource\Connection $db Connection where table is stored.
     * @param  string $table Table name.
     * @param  array $rows Data to be stored.
     * @return void
     */
    protected function _insertTable($db, $table, $rows)
    {
        $modelName = \Cake\Utility\Inflector::camelize($table);
        $model = $this->SeedGenerator->findModel($modelName, $table);

        try {
            foreach ($rows as $row) {
                $query = $model->query();
                $query->insert(array_keys($row))
                    ->values($row)->execute();

                continue;
                /*$entity = $model->newEntity($row, [
                    'accessibleFields' => ['*' => true],
                    'validate' => false
                ]);
                if (!$model->save($entity, ['checkRules' => false])) {
                    $this->out("{$table} record with ID {$row->id} could not be saved");
                }*/
            }
        } catch (Exception $e) {
            debug($e);
            $this->_io->err($e->getMessage());
            exit(1);
        }
    }

    /**
     * Runs operation in the SQL transaction with disabled logging.
     *
     * @param  \Cake\Datasource\Connection $db Connection to run the transaction on.
     * @param  callable $operation Operation to run.
     * @return void
     */
    protected function _runOperation($db, $operation)
    {
        $logQueries = $db->logQueries();
        if ($logQueries) {
            $db->logQueries(false);
        }

        $db->transactional($operation);

        if ($logQueries) {
            $db->logQueries(true);
        }
    }

    /**
     * Converts the internal records into data used to generate a query
     * for given table schema.
     *
     * @param \Schema\Table $schema Table schema.
     * @param  array $records Internal records.
     * @return array Fields, values and types.
     */
    protected function _getRecords(Table $schema, $records)
    {
        $fields = $values = $types = [];
        $columns = $schema->columns();
        foreach ($records as $record) {
            $fields = array_merge($fields, array_intersect(array_keys($record), $columns));
        }
        $fields = array_values(array_unique($fields));
        foreach ($fields as $field) {
            $types[$field] = $schema->columnType($field);
        }
        $default = array_fill_keys($fields, null);
        foreach ($records as $record) {
            $values[] = array_merge($default, $record);
        }
        return [$fields, $values, $types];
    }

    /**
     * Prepare table for data insertion.
     *
     * @return void
     */
    protected function _beforeTableInsert($db, $table)
    {
        // TODO: Move this into the driver
        if ($db->driver() instanceof Sqlserver) {
            $table = $db->quoteIdentifier($table);
            $db->query(sprintf('SET IDENTITY_INSERT %s ON', $table))->closeCursor();
        }
    }

    /**
     * Clean after inserting.
     *
     * @return void
     */
    protected function _afterTableInsert($db, $table)
    {
        // TODO: Move this into the driver
        if ($db->driver() instanceof Sqlserver) {
            $table = $db->quoteIdentifier($table);
            $db->query(sprintf('SET IDENTITY_INSERT %s OFF', $table))->closeCursor();
        }
    }

    /**
     * Returns the database connection.
     *
     * @return \Cake\Database\Connection Object.
     * @throws  \RuntimeException If the connection does not implement schemaCollection()
     */
    protected function _connection()
    {
        $db = ConnectionManager::get($this->_config['connection'], false);
        if (!method_exists($db, 'schemaCollection')) {
            throw new \RuntimeException(
                'Cannot generate fixtures for connections that do not implement schemaCollection()'
            );
        }
        return $db;
    }

    /**
     * Returns the data array.
     *
     * @param  string $path Path to the seed.php file.
     * @return array Data array.
     */
    protected function _readSeed($path)
    {
        if (file_exists($path)) {
            $return = include $path;
            if (is_array($return)) {
                return $return;
            }
        }

        return [];
    }
}
