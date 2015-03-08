# Schema plugin for CakePHP 3.0

Save the schema into one file and then restore the database from the schema file.

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

```
composer require-dev laykou/schema
```

## Usage

The plugin saves the schema of the `default` connection to the `config/schema.lock` file. The structure is similiar to the fixtures fields.

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

## Other examples
```bash
cake schema save --connection test
cake schema save --path config/schema/schema.lock
cake schema load --connection test --path config/schema/schema.lock --no-interaction

# To only drop all tables in database
cake schema drop
cake schema drop --connection test
```

## TODO
 
- [ ] Auto-creation of the schema.lock file after `cake migrations migrate`
- [ ] Tests
- [ ] Data loading from the `yaml`
- [ ] More options and configuration