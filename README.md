# Schema plugin for CakePHP 3.0

Save the schema into one file and then restore the database from the schema file. The schema is automatically saved when executing `cake migrations migrate`.

## Supported datasources

- Postgres
- MySQL
- SQL Server
- ~~SQLite~~ not yet

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

```
composer require scherersoftware/cakephp-schema
```

Update your `config/bootstrap.php``:

```PHP
Plugin::load('Schema', ['bootstrap' => true]);
```

## Usage

The plugin saves the schema of the `default` connection to the `config/schema.php` file. The structure is similiar to the fixtures fields.

```
cake schema save
```

To load the schema back execute

```
cake schema load
```

All existing tables in the database are dropped before loading. You will be asked

```
Loading the schema from the file Database is not empty. 37 tables will be deleted.
Do you want to continue? (y/n)
```

To disable the question (and answer with yes) run 

```
cake schema load --no-interaction
```

### Seed

The Schema plugin allows you to seed data from the `config/seed.php` file. The `seed.php` file should return array of tables and rows:

```PHP
<?php
// You can work with custom libraries here or use the Cake's ORM
return [
    'articles' => [
        [
            'id' => 1,
            'category_id' => 1,
            'label' => 'CakePHP'
        ], [
            'id' => 2,
            'label' => 'Schema plugin'
        ]
    ],
    'categories' => [
        [
            'id' => 2,
            'label' => 'Frameworks'
        ]
    ]
];
```

## Other examples
```bash
cake schema save --connection test
cake schema save --path config/schema/schema.lock
cake schema load --connection test --path config/schema/schema.lock --no-interaction

# To only drop all tables in database
cake schema drop
cake schema drop --connection test

# Truncate tables before inserting. Otherwise duplicate ID exception is thrown.
cake schema seed --truncate
cake schema seed --seed custom/path/to/seed.php
cake schema seed --connection test --truncate
```

To seed the data into database run following command:

```
cake schema seed
```

## TODO
 
- [x] Auto-creation of the schema.php file after `cake migrations migrate`
- [x] Data seeding
- [ ] Tests
- [ ] More options and configuration
- [ ] Refactoring and cleaning the code

## Known issues
 - SQLite is not fully supported