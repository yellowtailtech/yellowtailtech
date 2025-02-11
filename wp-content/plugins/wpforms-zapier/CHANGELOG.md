# Changelog
All notable changes to this project will be documented in this file, formatted via [this recommendation](https://keepachangelog.com/).

## [1.6.0] - 2024-04-16
### IMPORTANT
- Support for PHP 5.6 has been discontinued. If you are running PHP 5.6, you MUST upgrade PHP before installing WPForms Zapier 1.6.0. Failure to do that will disable WPForms Zapier functionality.
- Support for WordPress 5.4 and below has been discontinued. If you are running any of those outdated versions, you MUST upgrade WordPress before installing WPForms Zapier 1.6.0. Failure to do that will disable WPForms Zapier functionality.

### Added
- Compatibility with the upcoming WPForms 1.8.8.

### Changed
- Minimum WPForms version supported is 1.8.4.

## [1.5.0] - 2023-04-05
### Added
- The Site URL needed for Zapier configuration is now displayed inside the Form Builder and on the Settings > Integrations page.

### Changed
- More addon strings are now translatable.
- Zapier external assets are now lazy-loaded on demand, only when needed inside the Form Builder.
- The Edit Zap link is removed.

### Fixed
- Texts in various places were rephrased and typos were fixed.

## [1.4.0] - 2022-08-29
### Changed
- Minimum WPForms version supported is 1.7.5.5.
- Updated Zapier logo.

### Fixed
- Incorrect URL to the documentation article was used on the Settings > Integrations page.
- Multi-select drowdown field options were not comma separated.
- '0' values were not processed correctly.
- Files were not properly uploaded to Zapier when more than 1 file was uploaded using a modern style file upload field.

## [1.3.0] - 2020-12-11
### Added
- Popular Zap templates inside the form builder Zapier settings area.

## [1.2.0] - 2019-07-23
### Added
- Complete translations for French and Portuguese (Brazilian).

### Fixed
- Incorrect checkbox value is passed to Zapier with "Show values" option enabled.

## [1.1.0] - 2019-02-06
### Added
- Complete translations for Spanish, Italian, Japanese, and German.

### Fixed
- Typos, grammar, and other i18n related issues.

## [1.0.6] - 2018-01-07
### Fixed
- Entry ID not correctly passing to Zaps.
- Empty Checkbox field choice labels causing PHP error.

## [1.0.5] - 2017-10-25
### Fixed
- "Live" Zap status setting not updating correctly
- "Edit this Zap" link incorrect/broken

## [1.0.4] - 2017-09-28
### Changed
- All HTTP requests now validate target sites SSL certificates with WP bundled certificates (since 3.7)

### Fixed
- Properly decode forms name before sending them to Zapier to display.
- Update function incorrectly named which could cause conflicts with MailChimp addon.
- Visual display of connected forms on Settings > Integrations page.

## [1.0.3] - 2017-03-09
### Changed
- Adjust display order so that the providers show in alphabetical order.

## [1.0.2] - 2016-12-08
### Added
- Support for Dropdown Items payment field.

## [1.0.1] - 2016-09-02
### Fixed
- Error with Zapier custom field polling.

## [1.0.0] - 2016-08-30
- Initial release.
