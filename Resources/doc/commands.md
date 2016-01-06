# CLI Commands

> All commands can be accessed using Symfony command line interface. To use it simply type `app/console <command_name> <arguments/options>` in root folder of your project.

## Create index

Command name: `ongr:es:index:create`

Creates a new index in Elasticsearch (including with mapping if not skipped) for the specified manager (see: [configuration chapter](configuration.md)).

|     Options    | Short name |             Value            |                                      What it does                                      |
|:--------------:|:----------:|:----------------------------:|:--------------------------------------------------------------------------------------:|
|   `--manager`  |   `-mng`   | *Manager name. e.g.* `default` | Used to select manager to create index for. If not specified, default manager is used. |
|    `--time`    |    `-t`    |        *not required*        | Creates an index with current timestamp appended to its name.                          |
|    `--alias`   |    `-a`    |        *not required*        | Creates an alias with index name specified in the configuration.                       |
| `--no-mapping` |    `-nm`   |        *not required*        | Skips the mapping configuration on index create action.                                |
| `--if-not-exists` |    *no value*   |        *not required*        | Skips an index creation, when the index already exists.                                |

If you want to use timestabale indexes it's very handy to use it together with `-a` option. `-t` adds a date as the suffix and `-a` adds an alias as defined index name in manager configuration. So the code will work fine without any configuration changes, you dont need to do any other actions.

> `-a` option drops a previous aliases before creating new one.


## Drop index

Command name: `ongr:es:index:drop`

Drops the index for the specified manager.

|     Options    | Short name |             Value            |                                      What it does                                      |
|:--------------:|:----------:|:----------------------------:|:--------------------------------------------------------------------------------------:|
|   `--manager`  |   `-mng`   | *Manager name. e.g.* `default` | Used to select manager to create index for. If not specified, default manager is used. |
|    `--force`    |    `-f`    |        *not required*        | This flag is mandatory for the command to work.  


## Import index

Command name: `ongr:es:index:import <file-path>`

Imports data to the selected index. We are using custom `JSON` notation to specify data inside the file for faster handling. Please keep the structure as it is described below.


|     Options          | Short name |             Value            |                                      What it does                                      |
|:--------------------:|:----------:|:----------------------------:|:--------------------------------------------------------------------------------------:|
|   `--manager`        |   `-mng`   | *Manager name. e.g.* `default` | Used to select manager to create index for. If not specified, default manager is used. |
|    `--bulk-size`     |    `-b`    |        *Bulk size, default 1000*        | The document frequency to flush the index on import.

So here's a simple example how the data looks like:

```json

[
    {"count":2,"date":"2015-10-25T14:46:21+0200"},
    {"_type":"content","_id":"15","_source":{"id":"15","title":"About","content":"Sample ONGR about page..","urls":[{"url":"about\/","key":""}]}},
    {"_type":"content","_id":"37","_source":{"id":"37","title":"Home Page","content":"<div class=\"jumbotron\">\r\n  <h1>Welcome to ONGR demo site!<\/h1>\r\n  <p>Enterprise E-commerce Accelerator.<\/p><\/div>","urls":[{"url":"home-page\/","key":""}]}}
]

```

Every line of file is `JSON` object. First line must specify `count`, how many lines are in the files except first and the file timestamp in `date`.

In a single line is one document. In a single file could be defined different types, basically with a single file you can import a whole index. There are 3 required keys:
* `_type` which specifies elasticsearch type name (not an ElasticsearchBundle class document)
* `_id` is optional, if not specified (provided `null`) elasticsearch will create random.
* `_source`: document array encoded to json object, where all fields are equel to the elasticsearch type field names.


## Export index

Command name: `ongr:es:index:export <file-path>`

Exports data from Elasticsearch index in a json format.


|     Options          | Short name |             Value            |                                      What it does                                      |
|:--------------------:|:----------:|:----------------------------:|:--------------------------------------------------------------------------------------:|
|   `--manager`        |   `-mng`   | *Manager name. e.g.* `default` | Used to select manager to create index for. If not specified, default manager is used. |
|    `--chunk`     |    *no value*     |        *Chunk size, default 500*        | Specifies the size of each chunk to be received from Elasticsearch. This can be changed for performance reasons.
|    `--types`     |    *no value*    |        *Elasticsearch index type names*       | Selected types to export, if no specified will export all index.

> Index export generates the same `JSON` format as specified in the import chapter.

## Mapping update

Command name: `ongr:es:mapping:update`

Updates elasticsearch index mapping. See more info at ([official elastic docs](https://www.elastic.co/guide/en/elasticsearch/guide/current/mapping-intro.html#updating-a-mapping)).

|     Options    | Short name |             Value            |                                      What it does                                      |
|:--------------:|:----------:|:----------------------------:|:--------------------------------------------------------------------------------------:|
|   `--manager`  |   `-mng`   | *Manager name. e.g.* `default` | Used to select manager to create index for. If not specified, default manager is used. |
|    `--force`    |    `-f`    |        *not required*        | This flag is mandatory for the command to work.
|    `--types`     |    *no value*    |        *Elasticsearch index type names*       | Selected types to update, if no specified will update all index.



## Cache clear

Command name: `ongr:es:cache:clear`

Clears elasticsearch document storage cache. See more info at ([official elastic docs](https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-clearcache.html)).

|     Options    | Short name |             Value            |                                      What it does                                      |
|:--------------:|:----------:|:----------------------------:|:--------------------------------------------------------------------------------------:|
|   `--manager`  |   `-mng`   | *Manager name. e.g.* `default` | Used to select manager to create index for. If not specified, default manager is used.
