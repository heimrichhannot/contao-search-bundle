# Changelog
All notable changes to this project will be documented in this file.

## [2.8.4] - 2023-04-25
- Fixed: search page changes not applied directly

## [2.8.3] - 2023-03-20
- Fixed: bad pdf url when using search index crawler ([#7], [#8]) 
- Fixed: some deprecations ([#8])

## [2.8.2] - 2023-03-14
- Fixed: deprecation warning on cache warmup

## [2.8.1] - 2023-01-09
- Changed: reduced database size

## [2.8.0] - 2022-11-01
- Changed: allow php 8
- Changed: raised php and contao dependencies
- Fixed: symfony 5 support

## [2.7.2] - 2021-09-24
- Fixed: usage of php 7.4-only method

## [2.7.1] - 2021-09-23
- Fixed: bundle not installable within symfony 3.4 due symfony/translation dependency (remove this dependency to fix this)

## [2.7.0] - 2021-09-23
- Changed: invalid characters from pdf files are converted to utf-8
- Changed: added support for symfony/config:^5.0
- Fixed: exception when indexing a pdf not working (will now only show up in dev mode)
- Fixed: missing symfony dependencies

## [2.6.0] - 2021-03-12
- raised minimum smalot/pdfparser version to 0.18.2

## [2.5.2] - 2021-02-02
- allow smalot/pdfparser 0.18
- add github issue template

## [2.5.1] - 2021-01-28
- made filterPages non-mandatory

## [2.4.1] - 2020-06-03
- fixed exception when reject reason has no response

## [2.4.0] - 2020-06-02
- added BeforeGetSearchablePagesEvent to RebuildSearchIndexCommand
- added option to log search terms

## [2.3.0] - 2020-05-29
* added `valid_word_chars` option to configure keyword count method

## [2.2.0] - 2020-04-22
* added additional logging to search command
* added custom User-Agent of index requests
* fixed disabling search indexing 

## [2.1.0] - 2020-03-17
* added option to set maximum keyword count

## [2.0.2] - 2020-02-18
* respect getSearchablePages hook
* enhanced console output

## [2.0.1] - 2020-02-18
* added missing commands.yml

## [2.0.0] - 2020-02-17
* added RebuildSearchIndexCommand
* added disable search index option
* [BC BREAK] Renamed bundle class

## [1.1.2] - 2020-01-08
* adapted to coding standards

## [1.1.1] - 2019-11-06

* moved RelatedSearchLinkElement to the correct namespace
* renamed RelatedSearchElement to RelatedSearchLinkElement
* fixed related search element templates

## [1.1.0] - 2019-11-06

* added related search link content element

## [1.0.0] - 2019-11-06

First release

[#8]: https://github.com/heimrichhannot/contao-search-bundle/pull/8
[#7]: https://github.com/heimrichhannot/contao-search-bundle/issues/7