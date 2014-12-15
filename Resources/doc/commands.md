# Console commands in Elasticsearch Bundle.

All commands can be accessed using *Symfony* command line interface.

To use it simply type ```bash app/console <command_name> <arguments/options>``` in root folder of your project.

## Create index.

* Command name: `es:index:create`.
* Description: Creates a new index in Elasticsearch for the specified [*manager*][managers].
* Options:

    | Full name       | Short name  | Value          | What it does                                                                                         |
    |-----------------|-------------|----------------|------------------------------------------------------------------------------------------------------|
    | `--manager`     | *undefined* | Manager name.  | Used to select [*manager*][managers] to create index for. If not specified, default manager is used. |
    | `--time`        | `-t`        | *not required* | Creates an index with current timestamp appended to its name.                     |
    | `--with-warmers`| `-w`        | *not required* | Creates an index with warmers included.                                                              |
* Examples: 

    | Input                                   | What it does                                                                     |
    |-----------------------------------------|----------------------------------------------------------------------------------|
    | `es:index:create`                       | Creates an index in Elasticsearch for the default [*manager*][managers].         |
    | `es:index:create --manager shop --time` | Creates an index for manager `shop` with current timestamp appended to its name. |

## Drop index.

* Command name: `es:index:drop`.
* Description: Drops the index for the specified [*manager*][managers].
* Options:

    | Full name   | Short name  | Value          | What it does                                                                                          |
    |-------------|-------------|----------------|-------------------------------------------------------------------------------------------------------|
    | `--manager` | *undefined* | Manager name.  | Used to select [*manager*][managers] to create index for. If not specified, default manager is used.  |
    | `--force`   | *undefined* | *not required* | This flag is required for the command to work.                                                        |
* Examples: 

    | Input                                   | What it does                                                                                |
    |-----------------------------------------|---------------------------------------------------------------------------------------------|
    | `es:index:drop --force`                 | Drops an index in Elasticsearch of the default [*manager*][managers].                       |
    | `es:index:create --manager shop`        | Drop an index of [*manager*][managers] `shop` with current timestamp appended to its name.  |


## Import index.

>Currently this command only works with --raw flag.

* Command name: `es:index:import`.
* Description: Imports data to your index.
* Arguments:

    | Value      | What it specifies                                                                                 |
    |------------|---------------------------------------------------------------------------------------------------|
    | File name. | File name to import data from. File is looked for in your current working dir.  |
* Options:
    
    | Full name | Short name  | Value       | What it does                                                                                                                           |
    |-----------|-------------|-------------|----------------------------------------------------------------------------------------------------------------------------------------|
    | `--manager` | *undefined* | Manager name.  | Used to select [*manager*][managers] to import data into. If not specified, default manager is used. |
    | `--raw` | *undefined* | *not required* | This flag is used to specify whether data to import is in raw format or not. |
* Examples:

    | Input                                                 | What it does                                                                                                    |
    |-------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------|
    | `es:index:import test.json --raw`                     | Imports data to the default [*manager*][managers] index from the `test.json` file in current working directory. |
    | `es:index:import test.json --manager shop --raw` | Imports data to `shop` [*manager*][managers] index.                                 |
    
    ## Export index.

* Command name: `es:index:export`.
* Description: Exports data from Elasticsearch index in a json format.
* Arguments:

    | Value      | What it specifies                                                                                 |
    |------------|---------------------------------------------------------------------------------------------------|
    | File name. | File name to export data to. All files are exported to your current working directory by default. |
* Options:

    | Full name | Short name  | Value       | What it does                                                                                                                           |
    |-----------|-------------|-------------|----------------------------------------------------------------------------------------------------------------------------------------|
    | `--manager` | *undefined* | Manager name.  | Used to select [*manager*][managers] to dump data from. If not specified, default manager is used. |
    | `--chunk` | *undefined* | Chunk size. | Specifies the size of each chunk to be received from Elasticsearch. This can be changed for performance reasons. Default value: `500`. |
* Examples:

    | Input                                                 | What it does                                                                                                    |
    |-------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------|
    | `es:index:export test.json`                           | Exports data from the default [*manager*][managers] index to the `test.json` file in current working directory. |
    | `es:index:export test.json --manager shop --chunk 10` | Exports data from `shop` [*manager*][managers] index with a chunk size of `10`.                                 |
    
## Type update.

* Command name: `es:type:update`.
* Description: Updates mapping of the specified [*manager*][managers].
* Options:

    | Full name | Short name  | Value       | What it does                                                                                                                           |
    |-----------|-------------|-------------|----------------------------------------------------------------------------------------------------------------------------------------|
    | `--force`   | *undefined* | *not required* | This flag is required for the command to work.               |
    | `--manager` | *undefined* | Manager name.  | Used to select [*manager*][managers] to update types for. If not specified, default manager is used. |
    | `--type` | *undefined* | Type name.  | Used to select a specific type to update in your [*manager*][managers] mapping.  |
* Examples:

    | Input                                                 | What it does                                                                                                    |
    |-------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------|
    | `es:type:update --force`                           | Updates mapping for the default [*manager*][managers]. |
    | `es:type:update --manager shop --type article --force` | Updates mapping for `shop` [*manager*][managers] type `article`. |
    

[managers]: setup.md#step-2-enable-elasticsearch-bundle
