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
* Mapping annotations was simplified. In the `Document` annotations `Skip` and `Inherit` annotations were removed. In `Property` there are only a `type`, `name`, `multiple`, `objectName` and `options`. From now on all custom fields has to be defined in `options` (e.g. index_analyzer). See [mapping chapter](mapping.md) for more info.
* `AbstractDocument` and `DocumentInterface` was removed in favor of `DocumentTrait`. We think that document detection should be done via annotation and not by interfaces.
* MetaField annotation introduced to define fields like `_id`, `_score`, `_parent`, `_ttl` etc.
* From now on `Repository` always represents single document. To execute search on multiple types use `Manager`.

#### Changes which should not impact the functionality

* Minimum PHP required version now is 5.5
* Minimum Symfony required version now is 2.7
* Document Proxy class was completely removed. This should not effect any functionality.
* Profiler namespace is changed from `DataCollector` to `Profiler`.
* `findBy` in the Repository now uses `query_string` query instead of terms query.

> If we miss forgot something here in the list, please open an issue or a PR with a suggestion. 
