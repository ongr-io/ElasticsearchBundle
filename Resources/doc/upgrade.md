UPGRADE FROM 1.x to 5.0
===

#### Breaking changes
* Removed all deprecations from 1.x version.
* Removed `_ttl` metafield annotation.
* Service name `@annotations.cached_reader` changed to `@es.annotations.cached_reader` #717
* From Document annotation removed all properties except `type`. From now on everything has to be defined in the `options`.
* `string` property type was deprecated in elasticsearch 5.0, please use `text` or `keyword` accordingly.
 More info: https://www.elastic.co/blog/strings-are-dead-long-live-strings
* `auth` in the configuration was removed. Use authentication information directly in host or create event listener
 to modify client creation. There are too many ways to authenticate elasticsearch. That said we leaving this customisation to the user due difficult support. 
* `connections` node in configuration was removed. Use `index` from now on. There was absolute
 misunderstanding to have exposed connections, we never saw any benefits to use single connection
 between several managers.  
* Changed the namespace of the `DocumentParserException` to `ONGR\ElasticsearchBundle\Mapping\Exception`. #722
* `analysis` node in `index`/`connection` was deprecated. From now on used analyzers, filters, etc. must be provided in document annotations
* `Results` (constants container for result type definitions) class was removed in favor for
 new find functions with predefined types in the names.
* Export service now uses own query calling instead of elasticsearch-php. It was changes due a bug
 in hits iterator in elasticsearch-php. We will try to help them to resolve this issue.
* `Manager::execute()` was removed. Use `Manager::search()` instead.
* `Repository::execute()` was removed. Use `findDocuments()`, `findArray()` or `findRaw()` instead.
* `Manager::scroll()` third argument with result type definition was removed.
 Now you can get only raw result data from scroll.
* `AbstractElasticsearchTestCase::runTest()` was removed. It was introduced when elasticsearch
 in our CI was very unstable. Now there is no sense to repeat failing tests again and again.
* `AbstractElasticsearchTestCase::getNumberOfRetries()` was removed.
 If you write tests by extending `AbstractElasticsearchTestCase` delete your retries data provides.
 
#### Changes which should not impact the functionality

* Minimum PHP required version now is 5.6
* Minimum Symfony required version now is 2.8
* Minimum ES version was upped to 5.0
* Document annotation now has an options support.
* No more needed to define analysis in manager, it will be collected automatically from documents.
 
UPGRADE FROM 0.x to 1.0
===

#### Breaking changes

* All stuffs which were marked as `@deprecated` is removed.
* DSL query builder was exposed to standalone [ElasticsearchDSL](https://github.com/ongr-io/ElasticsearchDSL) library. So this effects a namespace. Run global search and replace (any modern IDE has this feature) through your project files. Change namespace `ONGR\ElasticsearchDSL\` to `ONGR\ElasticsearchDSL\`.
* `Client` is now the part of the `Manager`. So if you had any extensions of using client directly e.g. type hinting then search and remove it or change to the `Manager`.
* `Manager` and `Repository` namespace is changed to `Service`, there is no more `ORM`. Again, run search and replace to find old `ONGR\ElasticsearchBundle\ORM\` to `ONGR\ElasticsearchBundle\Service\` namespace.
* `Results` namespace was completely refactored. The `Suggestion`, `RawResultScanIterator`, `DocumentScanIterator`, `DocumentHighlight`, `IndicesResult` were removed.
* Events currently are disabled due previous complex integration. They will be introduced back in the v1.1.0.
* `config.yml` structure lightly was changed. The `analysis` section appeared where you can define analyzers, filters etc and then reuse them in connections. See [configuration chapter](connection.md) for more information.
* `document_dir` option in `config.yml` was removed. From now on `Document` namespace for ElasticsearchBundle's documents is mandatory.
* Removed `createDocument()` from `Repository` class. To create documents use normal object creation way with `new`.
* Mapping annotations were simplified. In the `Document` annotations `Skip` and `Inherit` annotations were removed. In `Property` there are only `type`, `name` and `options` attributes left. Fields containing `Object` and `Nested` now must be defined using `Embedded` annotation. From now on all custom fields has to be defined in `options` (e.g. index_analyzer). See [mapping chapter](mapping.md) for more info.
* `AbstractDocument` and `DocumentInterface` were removed. Now any class with correct annotations can be used as a document.
* `@Id`, `@Ttl`, `@ParentDocument` annotations were introduced to define Elasticsearch meta-fields like `_id`, `_ttl`, `_parent`.
* From now on `Repository` always represents single document. To execute search on multiple types use `Manager`.

#### Changes which should not impact the functionality

* Minimum PHP required version now is 5.5
* Minimum Symfony required version now is 2.7
* Document Proxy class was completely removed. This should not effect any functionality.
* Profiler namespace is changed from `DataCollector` to `Profiler`.
* `findBy` in the Repository now uses `query_string` query instead of terms query.

> If we miss forgot something here in the list, please open an issue or a PR with a suggestion. 
