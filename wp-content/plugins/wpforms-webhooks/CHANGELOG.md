# Changelog
All notable changes to this project will be documented in this file, formatted via [this recommendation](https://keepachangelog.com/).

## [1.4.0] - 2024-08-01
### IMPORTANT
- Support for PHP 5.6 has been discontinued. If you are running PHP 5.6, you MUST upgrade PHP before installing WPForms Webhooks 1.4.0. Failure to do that will disable WPForms Webhooks functionality.
- Support for WordPress 5.4 and below has been discontinued. If you are running any of those outdated versions, you MUST upgrade WordPress before installing WPForms Webhooks 1.4.0. Failure to do that will disable WPForms Webhooks functionality.

### Changed
- The minimum WPForms version supported is 1.8.4.
- Secure checkbox UI improvements on the Form Builder > Settings > Webhooks screen.

### Fixed
- Currency symbols were not decoded in payment field values.

## [1.3.0] - 2023-08-15
### Changed
- Minimum WPForms version supported is 1.8.3.

### Fixed
- Long labels of the webhooks configuration in the Form Builder were moving to the next row.

## [1.2.1] - 2023-07-03
### Fixed
- Compatibility with WPForms 1.8.2.2.

## [1.2.0] - 2022-05-26
### IMPORTANT
- Support for WordPress 5.1 has been discontinued. If you are running WordPress 5.1, you MUST upgrade WordPress before installing the new WPForms Webhooks. Failure to do that will disable the new WPForms Webhooks functionality.

### Changed
- Minimum WPForms version supported is 1.7.4.2.
- Extend hook arguments: make it possible to use formatted field values.

## [1.1.0] - 2021-10-28
### Added
- Process smart tags inside Request Headers and Body fields.
- Compatibility with WPForms 1.6.8 and the updated Form Builder.

### Changed
- Improved compatibility with jQuery 3.5 and no jQuery Migrate plugin.
- Improved compatibility with WordPress 5.9 and PHP 8.1.

### Fixed
- Compatibility with WordPress Multisite installations.
- Improve the way webhooks are disabled and displayed when disabled.
- Incorrect width of setting fields in Safari.
- Issue with saving settings.

## [1.0.0] - 2020-07-08
- Initial release.
