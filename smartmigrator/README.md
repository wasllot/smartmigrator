# Smart Product Migrator for PrestaShop

## Overview
**Smart Product Migrator** is a powerful, universal module designed to migrate products from **ANY CSV source** (Shopify, WooCommerce, Custom ERPs) to PrestaShop 1.7+. 

Unlike standard importers, it features a **Smart Analysis Engine** that:
- **Auto-Detects Columns**: Intelligently maps headers like "Title", "Name", "Nombre" -> `name`, or "Price", "PVP", "Precio" -> `price`.
- **Smart Grouping**: Automatically groups specific variants into parent products using "Handle", "Reference", or by **Name Similarity**.
- **Data Integrity**: Generates smart SKUs (`SM-TITL-OPT-RAND`) if missing and allows safe rollback.

## Architecture
This module follows a Clean Architecture approach decoupling logic into Services and Repositories.

### Directory Structure
- `classes/Service/`:
    - `ColumnMapper`: Heuristic engine to map generic CSV headers to internal fields.
    - `CsvAnalyzer`: Parses CSVs, applies mapping, and executes the grouping logic.
    - `BackupService`: Handles safe deletion and rollback snapshots.
- `classes/Repository/`: Database abstraction (`QueueRepository`).
- `tests/`: Unit tests for the analysis engine.

### Key Features
1.  **Universal CSV Support**: Upload any CSV. The `ColumnMapper` will try to make sense of it.
2.  **Staging Area**: Preview grouped products and generated SKUs before they touch your live catalog.
3.  **Smart SKU Generation**: Creates human-readable SKUs if your source file lacks them.
4.  **Safe-Mode Import**: Batch processing to prevent timeouts + "Undo" button to delete imported items and restore the queue.

## Usage
1.  **Configure**: Go to Module Configuration.
2.  **Upload**: Select any product CSV file.
3.  **Analyze**: The module will map columns, group variants, and show a preview.
4.  **Import**: Click "Import All" to process the queue.
5.  **Rollback**: Mistake? Click "Undo/Delete All Imports" to clean up instantly.

## Development
-   **Tests**: Run via `test_runner.php` (Custom runner included) or PHPUnit.
-   **Docker**: Includes `docker-compose.yml` for a ready-to-use PrestaShop environment.

## Author
Reinaldo Tineo <rei.vzl@gmail.com>
Version: 2.0.0
