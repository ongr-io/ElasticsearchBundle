# CHANGELOG
## v2.0.0 (2016-x)
- Drop PHP 5.5 and 5.6 support. Now only PHP >=7 are supported.
- Array iterator now returns document _id field as well.

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
