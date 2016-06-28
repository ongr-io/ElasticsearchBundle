# CHANGELOG
## v1.0.x (2016-x)

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
