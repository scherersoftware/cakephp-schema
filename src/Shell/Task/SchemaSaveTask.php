<?php
namespace Schema\Shell\Task;

use Cake\Console\Shell;
use Cake\Database\Schema\Table;
use Cake\Datasource\ConnectionManager;
use Cake\Filesystem\File;

class SchemaSaveTask extends Shell
{
    /**
     * Default configuration.
     *
     * @var array
     */
    private $config = [
        'connection' => 'default',
        'path' => 'config/schema.php',
        'no-interaction' => true
    ];

    /**
     * Save the schema into lock file.
     *
     * @param array $options Set connection name and path to save the schema.lock file.
     * @return void
     */
    public function save($options = [])
    {
        $this->config = array_merge($this->config, $this->params, $options);

        $this->_io->out('Reading the schema from the database ', 0);

        $connection = ConnectionManager::get($this->config['connection']);
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

        $this->_generateSchemaFile($data);
    }

    /**
     * Generates the schema file and writes it to the disk.
     *
     * @param  array $data List of tables and their schemas.
     * @return void
     */
    protected function _generateSchemaFile($data)
    {
        $this->_io->out('Generateing schema file ', 0);

        $content = "<?php\n";
        $content .= "/**\n";
        $content .= " * This file is auto-generated from the current state of the database. Instead\n";
        $content .= " * of editing this file, please use the migrations to incrementally modify your\n";
        $content .= " * database, and then regenerate this schema definition.\n";
        $content .= " *\n";
        $content .= " * Note that this schema definition is the authoritative source for your\n";
        $content .= " * database schema. If you need to create the application database on another\n";
        $content .= " * system, you should be using `cake schema load`, not running all the migrations\n";
        $content .= " * from scratch. The latter is a flawed and unsustainable approach (the more migrations\n";
        $content .= " * you'll amass, the slower it'll run and the greater likelihood for issues).\n";
        $content .= " *\n";
        $content .= " * It's strongly recommended that you check this file into your version control system.\n";
        $content .= " */\n";
        $content .= "\n";
        $content .= "return [\n";
        $content .= "    'tables' => [\n";

        foreach ($data as $name => $table) {
            $this->_io->out('.', 0);
            $schema = $this->_generateSchema($table);
            $content .= "        '$name' => $schema,\n";
        }

        $this->_io->out(); // New line

        $content .= "    ],\n";
        $content .= "];\n";

        $path = $this->config['path'];
        $this->_createFile($path, $content);
    }

    /**
     * Save the content to the file.
     *
     * @param  string $path Path to the file.
     * @param  string $contents Content of the file.
     * @return bool True if the file was successfully saved.
     */
    protected function _createFile($path, $contents)
    {
        $File = new File($path, true);
        if ($File->exists() && $File->writable()) {
            $data = $File->prepare($contents);
            $File->write($data);
            $this->_io->out(sprintf('<success>Wrote</success> `%s`', $path));
            return true;
        }

        $this->_io->err(sprintf('<error>Could not write to `%s`</error>.', $path), 2);
        return false;
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
            $content .= "            '_indexes' => [\n" . implode("\n", $indexes) . "\n        ],\n";
        }
        if (!empty($constraints)) {
            $content .= "            '_constraints' => [\n" . implode("\n", $constraints) . "\n        ],\n";
        }
        if (!empty($options)) {
            $content .= "            '_options' => [\n" . implode(', ', $options) . "\n        ],\n";
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
