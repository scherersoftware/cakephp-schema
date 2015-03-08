<?php
namespace Schema\Shell;

use Cake\Console\Shell;
use Schema\Task\SchemaSave;

/**
 * Command-line code schema saving and loading.
 */
class SchemaShell extends Shell
{
    public $tasks = ['Schema.SchemaSave', 'Schema.SchemaLoad'];

    /**
     * Save the schema to the file.
     *
     * @return void
     */
    public function save()
    {
        $this->SchemaSave->save();
    }

    /**
     * Load the schema into the database.
     *
     * @return void
     */
    public function load()
    {
        $this->SchemaLoad->load();
    }

    /**
     * Drop all tables in the database.
     *
     * @return void
     */
    public function drop()
    {
        $this->SchemaLoad->drop();
    }

    /**
     * Get the option parser.
     *
     * @return void
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();

        return $parser->description([
            'Schema Shell',
            '',
            'Saves and loads the schema from the the schema.lock file.'
        ])->addSubcommand('save', [
            'help' => 'Saves the schema into schema.lock file.'
        ])->addSubcommand('load', [
            'help' => 'Loads the schema from the schema.lock file.'
        ])->addSubcommand('drop', [
            'help' => 'Drops all tables in the database.'
        ])->addOption('connection', [
            'help' => 'Connection name to save/load the schema from.',
            'short' => 'c',
            'default' => 'default'
        ])->addOption('path', [
            'help' => 'Path to the schema.lock file.',
            'short' => 'p',
            'default' =>  'config/schema.php'
        ])->addOption('no-interaction', [
            'help' => 'Disable any user input. Use the default answers for questions.',
            'short' => 'n',
            'boolean' => true,
            'default' => false
        ]);
    }
}
