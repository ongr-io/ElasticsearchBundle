# CHANGELOG
## v5.0.0 (2016-x)
- Drop PHP 5.5 support. Now only PHP >=5.6 are supported.
- Added support for Elasticsearch 5.0
- Array iterator now returns document _id field as well.
- Document annotation now has an options support.
You can pass any settings along parameters you want. Simply just put them in the options.
- `Manager::getSettings()` was added. Returns the currently configured settings for manager index.
- `Manager::getAliases()` was added. Gets Elasticsearch aliases information.
- Added `text` and `keyword` property types support.
- Added `murmur3`, `attachments`, `percolator` property type support
- Added `hash_map` annotation. #747
- Added `ONGR\ElasticsearchBundle\Exception` namespace.
- Added `char_filter` analysis support.
- All features and fixes from 1.2.x
- Added `document_dir`. From now on you can change documents directory for each mapped bundle.
- No more needed to define analysis in manager, it will be collected automatically from documents.

### Breaking changes
- Removed all deprecations from 1.x version.
- Removed `_ttl` metafield annotation.
- Service name `@annotations.cached_reader` changed to `@es.annotations.cached_reader` #717
- From Document annotation removed all properties except `type`. From now on everything has to be defined in the `options`.
- `string` property type was deprecated in elasticsearch 5.0, please use `text` or `keyword` accordingly.
 More info: https://www.elastic.co/blog/strings-are-dead-long-live-strings
- `auth` in the configuration was removed. Use authentication information directly in host or create event listener
 to modify client creation. There are too many ways to authenticate elasticsearch. That said we leaving this customisation to the user due difficult support. 
- `connections` node in configuration was removed. Use `index` from now on. There was absolute
 misunderstanding to have exposed connections, we never saw any benefits to use single connection
 between several managers.  
- Changed the namespace of the `DocumentParserException` to `ONGR\ElasticsearchBundle\Mapping\Exception`. #722
- `analysis` node in `index`/`connection` was deprecated. From now on used analyzers, filters, etc. must be provided in document annotations
- `Results` (constants container for result type definitions) class was removed in favor for
 new find functions with predefined types in the names.
- Export service now uses own query calling instead of elasticsearch-php. It was changes due a bug
 in hits iterator in elasticsearch-php. We will try to help them to resolve this issue.
- `Manager::execute()` was removed. Use `Manager::search()` instead.
- `Manager::scroll()` third argument with result type definition was removed.
 Now you can get only raw result data from scroll.
- `AbstractElasticsearchTestCase::runTest()` was removed. It was introduced when elasticsearch
 in our CI was very unstable. Now there is no sense to repeat failing tests again and again.
- `AbstractElasticsearchTestCase::getNumberOfRetries()` was removed.
 If you write tests by extending `AbstractElasticsearchTestCase` delete your retries data provides.

## v1.2.5 (2016-11-14)
- Set index for bulk operations globally #705
- Introduced options in document annotation #707

## v1.2.4 (2016-10-03)
- Upgraded minimum Elasticsearch DSL version

## v1.2.3 (2016-09-23)
- Bug fix with manager name resolve in the configuration #692

## v1.1.3 (2016-09-23)
- Bug fix with manager name resolve in the configuration #692

## v1.0.4 (2016-09-23)
- Bug fix with manager name resolve in the configuration #692

## v1.2.2 (2016-09-13)
- Fixed scroll configuration pass to find functions #687
- Eliminated deprecated execute function in tests #688
- Fixed profiler timing #689

## v1.2.1 (2016-09-13)
- Fixed wrong event name (#683)

## v1.1.2 (2016-09-13)
- Minor fixes

## v1.2.0 (2016-09-12)
- Added strict type return search functions #678
- Added event dispatching at bulk, commit and manager creation actions #677 #659
- Added exception throwing when there is a bad response from elasticsearch #668
- `connections` node in configuration was deprecated, now connection has to be defined in manager under `index` node. 
- Added index `--dump` option to index create command to dump mapping json to the console. #666
- Added routing annotation and parameters in find type functions.
- Added default value in `AggregationValue:getValue()` function. #651

## v1.1.1 (2016-05-26)
- Added sub-folder for elasticsearch mappings cache due conflict with other ONGR bundles.
- Changed the default value for ongr cache to %kernel.debug% #637
- By default es manager always refresh and flush only the current index #638

## v1.1.0 (2016-05-26)
- Introduced documents generator. Check the new `ongr:es:document:generate` command. #601
- Index export now can split files into the parts.

## v1.0.3 (2016-06-28)
- Changed the default value for ongr cache to %kernel.debug% #637
- By default es manager always refresh and flush only the current index #638
- Added sub-folder for elasticsearch mappings cache due conflict with other ONGR bundles.

## v1.0.2 (2016-x)
- Fixed profiler query time calculation #619
- Heavily improved import/export performance #617

## v1.0.1 (2016-04-06)
- Changed properties to private in all tests.
- Fixed bug when there is no `Document` folder #605
- Fixed bulk queries reset bug #606
- Fixed integer and float values storing as an arrays #571

## v1.0.0 (2016-03-17)   
- First stable release
