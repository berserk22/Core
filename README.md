# SkeletonApp Core

The Core component provides the foundational infrastructure for the SkeletonApp modular web application. It handles application bootstrapping, dependency injection, configuration management, module registration, and command-line interface (CLI) integration.

## Overview

The Core is built on the **Slim 4** framework and uses **PHP-DI** for container management. It defines the base classes and traits used throughout the application to ensure a consistent modular architecture.

### Key Features:
- **Application Bootstrapping**: Centralized initialization logic in `Application.php`.
- **Dependency Injection**: Integrated PSR-11 compliant container using PHP-DI.
- **Modular Architecture**: Base classes for modules (`Provider`), controllers, and routers.
- **Flexible Configuration**: Support for INI and JSON configuration files.
- **CLI Framework**: Built-in support for Symfony Console-based commands.
- **Event Dispatching**: Simple event system for module lifecycle hooks.

## Requirements

- **PHP**: >= 8.2
- **Composer**: For dependency management.
- **Extensions**: `curl`, `simplexml`, `gd`, `libxml`, `pdo`.

## Project Structure

- `Common/`: Common event dispatching logic.
- `Config/`: Configuration loading and parsing (INI, JSON).
- `Console/`: CLI base classes (Command, Input, Terminal).
- `Events/`: Core event management.
- `Factory/`: Container factory for PHP-DI.
- `Handler/`: Error and exception handling/rendering.
- `Module/`: Base classes for modular components (Provider, Controller, Router).
- `Traits/`: Reusable traits like `App` for easy access to the container.
- `Utils/`: Utility classes for arrays, strings, curl, etc.
- `Application.php`: Main application entry class.
- `Console.php`: CLI application entry class.
- `bootstrap.php`: Web entry point/initialization script.

## Setup & Usage

### As part of SkeletonApp
The Core is automatically loaded via Composer PSR-4 autoloading in the main project:
```json
"autoload": {
  "psr-4": {
    "Core\\": "core/"
  }
}
```

### Entry Points
- **Web**: `core/bootstrap.php` (called by `www/index.php`)
- **CLI**: `core/Console.php` (utilized by `cli/console`)

## Scripts

The Core doesn't provide standalone scripts but facilitates them via the `Console` class. Modules register commands by overriding the `console()` method in their `Provider` class.

## Configuration (Env Vars)

Configuration is managed by the `Core\Config` class. It typically loads settings from the `config/` directory.
- `config/config.ini`: Main configuration file.
- Supported drivers: `Ini`, `Json`.

TODO: Document specific core-level configuration keys.

## Tests

Tests for the core are located in the root `tests/` directory. Run them using:
```bash
composer test
```
(Note: Core-specific unit tests should ideally be located within a `tests` subfolder in `core/` in the future).

## License

This project is licensed under a proprietary license as specified in `composer.json`.
