# Changelog

## Unreleased

### Added
- You can now load files from markdown by using the `<<< file.php` convention.

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