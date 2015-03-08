<?php
namespace Schema\Shell;

use Cake\Console\Shell;
use Schema\Task\SchemaSave;

/**
 * Command-line code schema saving and loading.
 */
class SchemaShell extends Shell
{
    public $tasks = ['Schema.SchemaSave', 'Schema.SchemaLoad', 'Schema.Seed'];

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
     * Insert data into database.
     *
     * @return void
     */
    public function seed()
    {
        $this->Seed->seed();
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
        ])->addSubcommand('seed', [
            'help' => 'Inserts data into the database.'
        ])->addOption('connection', [
            'help' => 'Connection name to save/load the schema from.',
            'short' => 'c',
            'default' => 'default'
        ])->addOption('path', [
            'help' => 'Path to the schema.php file. Default: config/schema.php',
            'short' => 'p',
            'default' =>  'config/schema.php'
        ])->addOption('seed', [
            'help' => 'Path to the seed.php file. Defualt: config/seed.php',
            'short' => 's',
            'default' =>  'config/seed.php'
        ])->addOption('truncate', [
            'help' => 'Truncate tables before seeding.',
            'short' => 't',
            'boolean' => true,
            'default' => false
        ])->addOption('no-interaction', [
            'help' => 'Disable any user input. Use the default answers for questions.',
            'short' => 'n',
            'boolean' => true,
            'default' => false
        ]);
    }
}
