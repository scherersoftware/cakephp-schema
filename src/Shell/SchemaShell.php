<?php
namespace Schema\Shell;

use Cake\Cache\Cache;
use Cake\Console\ConsoleIo;
use Cake\Console\Shell;
use Schema\Task\SchemaSave;

/**
 * Command-line code schema saving and loading.
 */
class SchemaShell extends Shell
{
    public $tasks = ['Schema.SchemaSave', 'Schema.SchemaLoad', 'Schema.SeedImport', 'Schema.SeedGenerate'];


    /**
     * Constructs this Shell instance.
     *
     * @param \Cake\Console\ConsoleIo $io An io instance.
     */
    public function __construct(ConsoleIo $io = null)
    {
        Cache::disable();
        parent::__construct($io);
    }

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
        $this->SeedImport->import();
    }

    /**
     * Insert data into database.
     *
     * @return void
     */
    public function generateSeed()
    {
        $this->SeedGenerate->generate();
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
            'Saves and loads the schema from the the schema.php file.'
        ])->addSubcommand('save', [
            'help' => 'Saves the schema into schema.php file.'
        ])->addSubcommand('load', [
            'help' => 'Loads the schema from the schema.php file.'
        ])->addSubcommand('drop', [
            'help' => 'Drops all tables in the database.'
        ])->addSubcommand('seed', [
            'help' => 'Inserts data into the database.'
        ])->addSubcommand('generateseed', [
            'help' => 'Generates a seed.php file based on the current database contents.'
        ])->addOption('connection', [
            'help' => 'Connection name to save/load the schema from.',
            'short' => 'c',
            'default' => 'default'
        ])->addOption('path', [
            'help' => 'Path to the schema.php file. Default: config/schema.php',
            'short' => 'p',
            'default' => 'config/schema.php'
        ])->addOption('seed', [
            'help' => 'Path to the seed.php file. Default: config/seed.php',
            'short' => 's',
            'default' => 'config/seed.php'
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
        ])->addOption('count', [
            'help' => 'Set the limit when generating seed',
            'default' => false
        ]);
    }
}
