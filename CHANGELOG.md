# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


## [1.2.2] - 2020-12-21

### Fixed

- Fixed removing of wrappers added by DOMDocument in Sanitize, Minify, StripTags

## [1.2.0] - 2020-09-15

### Added

- Added minfication of inline JS
- Added minification of inline CSS
- Added options to minfiy for tigther control. Options are collapseWhitespace, conservativeCollapse, collapseInlineTagWhitespace, minifyJs and minifyJs

### Changed

- Changed minification engine, this improves and fixes issues sometimes caused by the minfication process
- Changed toText link output to `title [url]` so that it is standard with the image, for markdown style links you can use originphp/markdown to convert

### Fixed

- Fixed toText without formatting excluded images

## [1.1.2] - 2020-09-14

### Fixed

- Fixed complete list of inline elements e.g. bdo, big detection for minify

## [1.1.1] - 2020-09-13

### Fixed

- Fixed test which was failing on PHP 7.2 due to EOT

## [1.1.0] - 2020-09-13

### Changed

- Changed the HTML minify to attempt to preserve some spaces between elements, e.g. buttons, links.

## [1.0.2] - 2020-09-12

### Fixed

- Fixed comment removal in minify so that it uses the DOM rather than regex


## [1.0.0] - 2019-10-12

This component has been decoupled from the [OriginPHP framework](https://www.originphp.com/).