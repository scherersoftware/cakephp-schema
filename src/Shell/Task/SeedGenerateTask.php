<?php
namespace Schema\Shell\Task;

use Cake\Cache\Cache;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

class SeedGenerateTask extends Shell
{
    public $connection = 'default';

    /**
     * main() method.
     *
     * @return bool|int Success or error code.
     */
    public function generate()
    {
        Cache::disable();
        $seedFile = ROOT . '/config/seed.php';
        Configure::load('schema');

        $seedData = [];

        $connection = ConnectionManager::get($this->connection);
        $schemaCollection = $connection->schemaCollection();
        
        foreach (Configure::read('tables') as $tableName => $tableSchema) {
            $model = Inflector::camelize($tableName);
            $data = $this->_getRecordsFromTable($model, $tableName)->toArray();
            if (empty($data)) {
                continue;
            }
            $seedData[$tableName] = $data;
        }

        $fileContent = "<?php\nreturn [\n";
        foreach ($seedData as $tableName => $records) {
            $fileContent .= sprintf("    '%s' => ", $tableName);
            $fileContent .= $this->_makeRecordString($records);
            $fileContent .= ",\n";
        }
        $fileContent .= "];\n";
        file_put_contents($seedFile, $fileContent);
    }

    /**
     * Convert a $records array into a string.
     *
     * @param array $records Array of records to be converted to string
     * @return string A string value of the $records array.
     */
    protected function _makeRecordString($records)
    {
        $out = "[\n";
        $encoder = new \Riimu\Kit\PHPEncoder\PHPEncoder();

        foreach ($records as $record) {
            $values = [];
            foreach ($record as $field => $value) {
                if ($value instanceof \DateTime) {
                    $value = $value->format('Y-m-d H:i:s');
                }
                if (is_array($value)) {
                    // FIXME: the encoder will forget precisions of floats
                    $val = $encoder->encode($value, [
                        'array.inline' => false,
                        'array.omit' => false,
                        'array.indent' => 4,
                        'boolean.capitalize' => false,
                        'null.capitalize' => false,
                        'string.escape' => false,
                        'array.base' => 12,
                        'float.integers' => "all",
                        'float.precision' => false
                    ]);
                } else {
                    $val = var_export($value, true);
                }

                if ($val === 'NULL') {
                    $val = 'null';
                }
                $values[] = "            '$field' => $val";
            }
            $out .= "        [\n";
            $out .= implode(",\n", $values);
            $out .= "\n        ],\n";
        }
        $out .= "    ]";
        return $out;
    }

    /**
     * Interact with the user to get a custom SQL condition and use that to extract data
     * to build a fixture.
     *
     * @param string $modelName name of the model to take records from.
     * @param string|null $useTable Name of table to use.
     * @return array Array of records.
     */
    protected function _getRecordsFromTable($modelName, $useTable = null)
    {
        $recordCount = (isset($this->params['count']) ? $this->params['count'] : 10);
        $conditions = (isset($this->params['conditions']) ? $this->params['conditions'] : '1=1');
        $model = $this->findModel($modelName, $useTable);

        $records = $model->find('all')
            ->where($conditions)
            ->limit($recordCount)
            ->hydrate(false);

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
