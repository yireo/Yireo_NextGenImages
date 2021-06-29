# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.2.2] - 29 June 2021
### Fixed
- Make sure CLI converts images with their absolute path
- Only convert image URLs matching the Magento base URL [@tdgroot]

## [0.2.1] - 9 March 2021
### Fixed
- Fix Dummy convertor to prevent compilation error

## [0.2.0] - 9 March 2021
### Added
- New option to try to convert on saving
- Add console command for converting images manually
- Add command to test for URIs quickly
- Update convertor to make second arg optional

## [0.1.1] - 15 February 2021
### Fixed
- Fix composer version
- Fix word "Logging" in config options

## [0.1.0] - 15 February 2021
### Added
- Enhance debugger with logging option
- Add logging with every exception

## [0.0.6] - 7 January 2021
### Fixed
- Reimplement `data-src` (PR 51 from the `Yireo_Webp2` module)

## [0.0.5] - 3 December 2020
### Fixed
- Add dummy convertor with DI preference
- Replace all WebP deps with NextGenImages deps

## [0.0.4] - 2 December 2020
### Fixed
- Re-add dep with WebP2 in controller

## [0.0.3] - 2 December 2020
### Fixed
- Remove deps with WebP2 module
- Add missing Browser class

## [0.0.2] - 30 November 2020
### Added
- Methods to added to ConvertorInterface

### Removed
- Remove duplicate DI.xml (and add docs for this)

## [0.0.1] - 30 November 2020
### Added
- Initial release together with Yireo WebP2
