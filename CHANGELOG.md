# Changelog

## Unreleased

## 0.5.5 - 2022-02-23

### Changed

- Updated torchlight-laravel version constraint

## 0.5.4 - 2022-02-01

### Added

- Ability to set multiple themes for e.g. dark mode

## 0.5.3 - 2022-01-19

### Added

- Attributes returned from the API will be added to the code block. (The API now returns `data-lang` as an attribute.)

## 0.5.2 - 2021-09-26

### Fixed
- Indented code works correctly now. 

## 0.5.1 - 2021-09-06

### Changed
- When loading content from files using the `<<< path/to/file.php` convention, you can now wrap it in a comment e.g. `// <<<path/to.file.php`.

## 0.5.0 - 2021-09-06

### Changed
- Deprecated `\Torchlight\Commonmark\TorchlightExtension` in favor of the versioned `\Torchlight\Commonmark\V1\TorchlightExtension` and `\Torchlight\Commonmark\V2\TorchlightExtension` extensions 

### Added
- You can now load files from markdown by using the `<<< path/to/file.php` convention.
- Added support for CommonMark V2

## 0.4.1 - 2021-08-02

### Changed
- Use a render function instead of the deprecated `wrapped` response from the API.

## 0.4.0 - 2021-07-31

### Changed
- Bump `torchlight/torchlight-laravel` dependency.
- Use `Torchlight::highlight` instead of `(new Client)->highlight`


## 0.3.3 - 2021-07-31

### Changed
- Changed `registerCustomBlockRenderer` to `useCustomBlockRenderer`.

## 0.3.2 - 2021-07-31

### Added
- Ability to register a custom block renderer. Needed for Ibis client.

## 0.3.1 - 2021-06-17

### Added
- Ability to set a theme per block by using `theme:name` syntax, e.g.:

````
```php theme:dark-plus
// Use dark-plus for this block.
```
````

## 0.3.0 - 2021-05-28

- Bump `torchlight/torchlight-laravel` dependency.

## 0.2.0 - 2021-05-22

- Bump `torchlight/torchlight-laravel` dependency.
- Changed package name from `torchlight/commonmark` to `torchlight/torchlight-commonmark`


## 0.1.0

First.