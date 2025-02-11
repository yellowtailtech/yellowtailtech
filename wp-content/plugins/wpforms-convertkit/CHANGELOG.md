# Changelog
All notable changes to this project will be documented in this file and formatted via [this recommendation](https://keepachangelog.com/).

## [1.1.0] - 2024-11-07
### IMPORTANT
- Updated logo and name to reflect the company's rebranding from ConvertKit to Kit.

### Added
- WPForms 1.9.1 compatibility.

### Changed
- The minimum WPForms version supported is 1.9.1.

### Fixed
- In some cases incomplete addon configuration data could prevent the form with payments to be submitted.
- In rare cases PHP warning could be thrown if custom fields were mapped to form fields no longer available due to license downgrade.
- Ensure payment values are correctly formatted and properly escaped before sending to ConvertKit.
- Conditional Logic disappeared for newly added connections.

## [1.0.0] - 2023-12-13
### Added
- Initial release.
