<?php
namespace Schema\Shell\Task;

use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Migrations\Shell\Task\SeedTask;
use Riimu\Kit\PHPEncoder\PHPEncoder;

class MigrationSeedTask extends SeedTask
{

    public $tasks = ['Schema.SeedGenerate'];

    /**
     * Gets the option parser instance and configures it.
     *
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();
        $parser->addOption('records', [
            'boolean' => true,
            'help' => 'Include records from the database in the seed file'
        ]);
        return $parser;
    }

    /**
     * Data provider for the template. Overridden to include records if needed.
     *
     * @return array
     */
    public function templateData()
    {
        $templateData = parent::templateData();
        if (!empty($this->params['records'])) {
            $modelName = Inflector::camelize($templateData['table']);
            $data = $this->SeedGenerate->getRecordsFromTable($modelName, $templateData['table'])->toArray();
            if (!empty($data)) {
                $templateData['records'] = $this->stringifyRecords($data);
                #debug($templateData);exit;
            }
        }
        return $templateData;
    }

    /**
     * Generates the PHP array string for an array of records. Will use
     * var_export() and PHPEncoder for more sophisticated types.
     *
     * @param array $records Array of seed records
     * @return string PHP Code
     */
    public function stringifyRecords(array $records)
    {
        $out = "[\n";
        $encoder = new PHPEncoder();

        foreach ($records as $record) {
            $values = [];
            foreach ($record as $field => $value) {
                if ($value instanceof \DateTime || $value instanceof \Cake\Chronos\Date || $value instanceof \Cake\Chronos\Chronos) {
                    $value = $value->format('Y-m-d H:i:s');
                }
                if (is_array($value)) {
                    $value = json_encode($value);
                }
                $val = var_export($value, true);
                if ($val === 'NULL') {
                    $val = 'null';
                }
                $values[] = "                '$field' => $val";
            }
            $out .= "            [\n";
            $out .= implode(",\n", $values);
            $out .= "\n            ],\n";
        }
        $out .= "        ]";
        return $out;
    }
}
