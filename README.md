# This repo is not maintained anymore
For current versions please try the original https://github.com/Laykou/cakephp-schema or a successor of this one: https://github.com/raul338/cakephp-schema

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

```
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
                'label' => 'Schema plugin',
                'json_type_field' => [
                    'i' => 'will convert',
                    'to' => 'json'
                ]
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

The Seed commands support the CakePHP ORM's type mapping. So for example, if you're using the JsonType example from the cookbook, the seed commands will automatically convert an array to JSON.

You can use the `schema generateseed` command to automatically generate a seed.php file based on your database contents.

Use `schema seed` for importing the contents of the `seed.php` into your DB.

Seed commands will take the following options:

- `connection` Database connection to use.
- `seed` Path to the seed file to generate (Defaults to "config/seed.php")
- `path` Path to the schema.php file (Defaults to "config/schema.php")



## Other examples

    cake schema save --connection test
    cake schema save --path config/schema/schema.lock
    cake schema load --connection test --path config/schema/schema.lock --no-interaction

# To only drop all tables in database

    cake schema drop
    cake schema drop --connection test

# Seeding Examples

    cake schema seed --truncate
    cake schema generateseed --seed config/my_seed.php

# Seeding for Migrations Plugin / phinx

This plugin provides a bake task extending the `seed` bake task provided by the `cakephp/migrations` plugin, but with automated inclusion of seed data from the database.

Example usage:

    bin/cake bake migration_seed Users --records

This will write a new file into `src/config/Seeds/UsersSeed.php` including all records currently present in the DB's users table.

## TODO
 
- [x] Auto-creation of the schema.php file after `cake migrations migrate`
- [x] Data seeding
- [ ] Tests
- [ ] More options and configuration
- [ ] Refactoring and cleaning the code

## Known issues
 - SQLite is not fully supported
