# SBOMinator for Laravel

A Laravel package to easily generate Software Bill of Materials (SBOM) for your Laravel applications. This package provides a convenient Artisan command that automatically analyzes your project dependencies and generates a standards-compliant SBOM file in either CycloneDX or SPDX format.

## What is an SBOM?

A Software Bill of Materials (SBOM) is a formal, machine-readable inventory of all components and dependencies used in your application. SBOMs are becoming increasingly important for:

- Security and vulnerability management
- Software supply chain transparency
- Regulatory and compliance requirements
- Open source license management

## Features

- 🔄 Generates standards-compliant SBOM files (CycloneDX or SPDX format)
- 📦 Automatically parses both Composer and NPM dependencies
- 🛠️ Simple integration via Laravel's service provider system
- ⚡ Convenient Artisan command interface

## Installation

You can install the package via composer:

```bash
composer require sbominator/laravel
```

The package will automatically register its service provider if you're using Laravel's package auto-discovery.

If you're not using auto-discovery, add the service provider to your `config/app.php` file:

```php
'providers' => [
    // ...
    SBOMinator\Laravel\SBOMinatorServiceProvider::class,
],
```

## Usage

To generate an SBOM for your Laravel application with default settings (CycloneDX format), run:

```bash
php artisan sbominator:generate
```

By default, this will create a CycloneDX SBOM file called `sbom.json` in your project's base directory.

### Choose Output Format

You can specify the output format using the `--format` option:

```bash
# Generate in CycloneDX format (default)
php artisan sbominator:generate --format=cyclonedx

# Generate in SPDX format
php artisan sbominator:generate --format=spdx
```

### Custom Output Path

You can specify a custom output path using the `--output` option:

```bash
php artisan sbominator:generate --output=storage/sbom/my-app-sbom.json
```

You can combine both options:

```bash
php artisan sbominator:generate --format=spdx --output=storage/sbom/my-app-spdx.json
```

### Dependencies Analyzed

The package analyzes the following dependency sources:

- **Composer dependencies** (using `composer.lock`)
- **NPM dependencies** (using `package-lock.json`, if present)

## Requirements

- PHP 8.2 or higher
- Laravel 9.0 or higher
- Composer lock file (`composer.lock`) must be present and readable

## How It Works

The `sbominator:generate` command:

1. Locates and parses your `composer.lock` file to extract PHP dependencies
2. If present, parses your `package-lock.json` file to extract NPM dependencies
3. Combines these dependencies into a standardized format
4. Generates a standards-compliant SBOM file in your chosen format at the specified location

## Example Output

### CycloneDX Format

```json
{
  "bomFormat": "CycloneDX",
  "specVersion": "1.4",
  "serialNumber": "urn:uuid:...",
  "version": 1,
  "metadata": {
    "timestamp": "2025-03-17T12:00:00Z",
    "tools": [
      {
        "vendor": "SBOMinator",
        "name": "Generator",
        "version": "0.4.1"
      }
    ]
  },
  "components": [
    {
      "type": "library",
      "name": "laravel/framework",
      "version": "10.0.0",
      "purl": "pkg:composer/laravel/framework@10.0.0",
      "licenses": [
        {
          "license": {
            "id": "MIT"
          }
        }
      ]
    },
    // Additional dependencies...
  ]
}
```

### SPDX Format

```json
{
  "spdxVersion": "SPDX-2.3",
  "dataLicense": "CC0-1.0",
  "SPDXID": "SPDXRef-DOCUMENT",
  "name": "app-sbom",
  "documentNamespace": "http://spdx.org/spdxdocs/app-sbom",
  "creationInfo": {
    "created": "2025-03-17T12:00:00Z",
    "creators": [
      "Tool: SBOMinator-0.4.1"
    ]
  },
  "packages": [
    {
      "name": "laravel/framework",
      "SPDXID": "SPDXRef-Package-laravel-framework",
      "versionInfo": "10.0.0",
      "downloadLocation": "https://github.com/laravel/framework.git",
      "licenseConcluded": "MIT",
      "licenseDeclared": "MIT"
    },
    // Additional dependencies...
  ]
}
```

## Why Use SBOMinator?

- **Security**: Identify vulnerable components quickly when new CVEs are published
- **Compliance**: Meet regulatory requirements for software transparency
- **Flexibility**: Generate SBOMs in different formats based on your needs
- **Auditability**: Maintain accurate records of dependencies for each release
- **Simplicity**: Generate SBOMs with a single command

## Testing

```bash
composer test
```
