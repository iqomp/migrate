# iqomp/migrate

Database migration that sync migrate config to database table. This migration
method sync your migration config file with what's on database. This migration
way is to store the database migration in a file config, and sync the config with
your current database state. The migrations logs is not stored on each migration
time file or database table, you'll have to check your repository for migrations
logs.

The migration is extendable which means one migration is combined with other
migration before execution. For example, module `post` already define structure
for table `post`, another module ( ex: `post-publish` ) allowed to add column
for table `post` on `post-publish` migration config.

## Installation

```bash
composer require iqomp/migrate
```

## Command Line

This module create new composer command that can be used to test, create db,
sync table and config, and print out sql/script for manual execution by
developer.

```bash
# Create database defined on iqomp/config/database->connections
# if the database is not yet there.
composer migrate db

# Start migrating for migrate config to database table.
composer migrate start

# Compare migration config and database without executing the migration
composer migrate test

# Compare migration config and database without executing the migration
# and print it to STD_OUT for manual execution by developer.
composer migrate to > ./migrate.sql
```

## Migration Config

Update your `composer.json` file to include content as below:

```json
{
    "extra": {
        "iqomp/migrate": "iqomp/migrate/config.php"
    }
}
```

Then create new file named `iqomp/migrate/config.php` under you main module
directory, fill the file with content as below:

```php
<?php

return [
    '/ModelClassName/' => [
        'fields' => [
            '/field-name/' => [
                'comment' => '/comment/',
                'type' => '/type/',
                'attrs' => [
                    // list of column attrs
                ],
                'index' => '/index/'
            ]
        ],
        'indexes' => [
            '/index-name/' => [
                'type' => '/index-type/',
                'fields' => [
                    '/field/' => [ /* option */ ]
                    // list of columns
                ],

            ]
        ],
        'data' => [
            '/search-field/' => [
                '/search-value/' => [
                    '/field/' => '/value/'
                ]
            ]
        ]
    ],
    'Company\\Model\\User' => [
        'fields' => [
            'id' => [
                'type' => 'int',
                'attrs' => [
                    'unsigned' => true,
                    'primary_key' => true,
                    'auto_increment' => true
                ],
                'index' => 100
            ],
            'name' => [
                'type' => 'varchar',
                'attrs' => [
                    'length' => 5,
                    'null' => false,
                    'unique' => true
                ],
                'index' => 200
            ],
            'status' => [
                'comment' => '0: Deleted, 1: Active',
                'type' => 'tinyint',
                'attrs' => [
                    'null' => false,
                    'default' => 1
                ],
                'index' => 3000
            ]
        ],
        'indexes' => [
            'by_name_status' => [
                'fields' => [
                    'name' => [],
                    'status' => []
                ]
            ]
        ],
        'data' => [
            'name' => [
                'admin' => [
                    'name' => 'admin',
                    'status' => 1
                ]
            ]
        ]
    ]
];
```

All migrations property is explained as below:

### fields

Array list of table fields with `name->meta` pair, where `name` is the table
column name, and `meta` is list of column meta data. The column should has at
least one property, which is `type`. Below is list of metas known so far:

#### comment::string

Column comment, not all database engine accept this actually.

#### type::string

The column data type, supported data type so far are as below:

1. Text
    1. `CHAR`. Require `attrs: {length}`
    1. `ENUM`. Require `attrs: {options:[]}`
    1. `LONGTEXT`
    1. `SET`. Require `attrs: {options:[]}`
    1. `TEXT`
    1. `TINYTEXT`
    1. `VARCHAR`. Require `attrs: {length}`
1. Number
    1. `BIGINT`
    1. `BOOLEAN`
    1. `DECIMAL`
    1. `DOUBLE`. Require `attrs: {length}`
    1. `FLOAT`
    1. `INTEGER`
    1. `MEDIUMINT`
    1. `SMALLINT`
    1. `TINYINT`
1. Date
    1. `DATE`
    1. `DATETIME`
    1. `TIMESTAMP`
    1. `TIME`
    1. `YEAR`

#### attrs::array

List of additional attributes for the column. Supported attributes for now are:

1. `length::string` The length of the column. For `DOUBLE` it accept comma for
length and decimal value.
1. `options::array` List of options for the column. Mostly used by `ENUM` and
`SET`.
1. `null::boolean` Set it to `false` to make sure the column not accept `null`
value
1. `default::mixed` Default value for the column.
1. `update::mixed` Default value for `update` action. This attribute use mostly
for column `updated_at` for value `CURRENT_TIMESTAMP`.
1. `unsigned::boolean` Set number column as `UNSIGNED`, which is not accept
negative value.
1. `unique::boolean` Set the column as `UNIQUE`, that don't accept duplicate
value.
1. `primary_key::boolean` Set the column as primary key.
1. `auto_increment:boolean` Set the column as auto increment.

### indexes

List of column indexes. It's array `name-meta` pair where `name` is the index
name and `meta` is list of index meta data and field list. This property accept
properties:

1. `type::string` The index type, accepted value are `UNIQUE`, `FULLTEXT`,
`SPATIAL`, `BTREE`, and `HASH`. If not set, `BTREE` will be used.
1. `fields::array` List of array to use as index columns in format `column-options`
pair. Where `column` is the column name, and `options` are list of additional
option for the index column. For example, column with type text may have `options`
length for index length.

### data

List of data to insert to table on migration if the data is not there yet based
on table column. This property is an array `column->datalist` pair where `column`
is the column name, and `datalist` is list of row with `search-value->row` pair
where the `search-value` is the value that will be used on search query to define
if the value should be inserted to table or not, while `row` is `column-value`
pair of new data to insert.

Please note that there's no such `update` and `remove` on migration.

## Creating Migrator

This part explain how to create new migration handler for some database type.

Create new class that implements interface `Iqomp\Migrate\MigratorInterface`. The
class should has below methods:

### public __construct(In $in, Out $out, array $config)

Construct he class, this method accept arguments command in, command out, and
database connection config. The `$in` and `$out` arguments is interfaces as of
below:

```php
use Symfony\Component\Console\Input\InputInterface as In;
use Symfony\Component\Console\Output\OutputInterface as Out;
```

### public createDb(): bool

Create new database based on provided config on `__construct`.

### public dbExists(): bool

Check if database exists based on provided connection.

### public lastError(): ?string

Return last error accured.

### public syncTable(string $model, string $table, array $config): void

Sync migration config to database table.

### public syncTableTo(string $model, string $table, array $config): void

Sync migration config to database and create print out script/sql for the
migration instead of executing it to the database. This action means to be
executed manually by developer.

### public testTable(string $model, string $table, array $config): void

Compare migration config to database table, and print the comparation result
without executing the migration.

Please check [iqomp/migrate-mysql](https://github.com/iqomp/migrate-mysql) for
migration example.

After creating the migrator class, register the handler by creating new global
[iqomp/config](https://github.com/iqomp/config) file named
`iqomp/config/database.php`. Fill the file with content as below:

```php
<?php

return [
    'migrators' => [
        '/db-type/' => 'ClassHandler',
        'mysql' => 'Iqomp\\MigrateMysql\\Migrator'
    ]
];
```

Then update your `composer.json` file to include content as below:

```json
{
    "extra": {
        "iqomp/config": "iqomp/config/"
    }
}
```

Make sure to call `composer update` for the config to take effect.
