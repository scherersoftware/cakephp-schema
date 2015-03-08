<?php
namespace Schema\Shell\Task;

use Bake\Shell\Task\SimpleBakeTask;
use Cake\Console\Shell;
use Cake\Database\Schema\Table;
use Cake\Datasource\ConnectionManager;
use Cake\Filesystem\File;

class SchemaSaveTask extends SimpleBakeTask
{
    /**
     * Default configuration.
     *
     * @var array
     */
    private $_config = [
        'connection' => 'default',
        'path' => 'config/schema.php',
        'no-interaction' => true
    ];

    /**
     * {@inheritDoc}
     */
    public function name()
    {
        return 'schema';
    }

    /**
     * {@inheritDoc}
     */
    public function fileName($name)
    {
        return $this->_config['path'];
    }

    /**
     * {@inheritDoc}
     */
    public function template()
    {
        return 'Schema.config/schema';
    }

    /**
     * {@inheritDoc}
     */
    public function getPath()
    {
        return ROOT . DS;
    }

    /**
     * {@inheritdoc}
     */
    public function templateData()
    {
        $tables = '';

        $data = $this->_describeTables();
        foreach ($data as $name => $table) {
            $schema = $this->_generateSchema($table);
            $tables .= "        '$name' => $schema,\n";
        }

        return [
            'tables' => $tables
        ];
    }

    /**
     * Save the schema into lock file.
     *
     * @param array $options Set connection name and path to save the schema.lock file.
     * @return void
     */
    public function save($options = [])
    {
        $this->_config = array_merge($this->_config, $this->params, $options);
        if ($this->_config['no-interaction']) {
            $this->interactive = false;
        }
        parent::bake('schema');
    }

    /**
     * Returns list of all tables and their Schema objects.
     *
     * @return array List of tables schema indexed by table name.
     */
    protected function _describeTables()
    {
        $this->_io->out(sprintf(
            'Reading the schema from the `%s` database ',
            $this->_config['connection']
        ), 0);

        $connection = ConnectionManager::get($this->_config['connection'], false);
        if (!method_exists($connection, 'schemaCollection')) {
            throw new \RuntimeException(
                'Cannot generate fixtures for connections that do not implement schemaCollection()'
            );
        }
        $schemaCollection = $connection->schemaCollection();
        $tables = $schemaCollection->listTables();

        $data = [];
        foreach ($tables as $table) {
            $this->_io->out('.', 0);
            $data[$table] = $schemaCollection->describe($table);
        }

        $this->_io->out(); // New line
        return $data;
    }

    /**
     * Generates a string representation of a schema.
     *
     * @param \Cake\Database\Schema\Table $table Table schema.
     * @return string fields definitions
     */
    protected function _generateSchema(Table $table)
    {
        $cols = $indexes = $constraints = [];
        foreach ($table->columns() as $field) {
            $fieldData = $table->column($field);
            $properties = implode(', ', $this->_values($fieldData));
            $cols[] = "            '$field' => [$properties],";
        }
        foreach ($table->indexes() as $index) {
            $fieldData = $table->index($index);
            $properties = implode(', ', $this->_values($fieldData));
            $indexes[] = "                '$index' => [$properties],";
        }
        foreach ($table->constraints() as $index) {
            $fieldData = $table->constraint($index);
            $properties = implode(', ', $this->_values($fieldData));
            $constraints[] = "                '$index' => [$properties],";
        }
        $options = $this->_values($table->options());

        $content = implode("\n", $cols) . "\n";
        if (!empty($indexes)) {
            $content .= "            '_indexes' => [\n" . implode("\n", $indexes) . "\n            ],\n";
        }
        if (!empty($constraints)) {
            $content .= "            '_constraints' => [\n" . implode("\n", $constraints) . "\n            ],\n";
        }
        if (!empty($options)) {
            $content .= "            '_options' => [\n" . implode(', ', $options) . "\n            ],\n";
        }
        return "[\n$content        ]";
    }

    /**
     * Formats Schema columns from Model Object
     *
     * @param array $values Options keys(type, null, default, key, length, extra).
     * @return array Formatted values
     */
    protected function _values($values)
    {
        $vals = [];
        if (!is_array($values)) {
            return $vals;
        }
        foreach ($values as $key => $val) {
            if (is_array($val)) {
                $vals[] = "'{$key}' => [" . implode(", ", $this->_values($val)) . "]";
            } else {
                $val = var_export($val, true);
                if ($val === 'NULL') {
                    $val = 'null';
                }
                if (!is_numeric($key)) {
                    $vals[] = "'{$key}' => {$val}";
                } else {
                    $vals[] = "{$val}";
                }
            }
        }
        return $vals;
    }
}
