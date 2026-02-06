# Smart Product Migrator - Dev Environment

This repository contains the source code for the **Smart Product Migrator** PrestaShop module and a complete Dockerized environment for development and testing.

## 📂 Project Structure

- **`smartmigrator/`**: The PrestaShop module source code.
- **`docker-compose.yml`**: Docker configuration for a local PrestaShop 1.7 + MySQL stack.
- **`.github/workflows/`**: CI/CD pipelines for automated testing and releasing.

## 🚀 Getting Started

### Prerequisites
- Docker & Docker Compose
- Git

### Development Environment
To start the PrestaShop environment with the module mounted:

```bash
docker-compose up -d
```

- **PrestaShop URL**: `http://localhost:8080/admin`
- **Database (MySQL)**: Port `3306`

### Tests
Run the integration tests inside the container:

```bash
docker exec ps_shop php modules/smartmigrator/test_runner.php
```

## 📦 CI/CD & Releases
This project uses **GitHub Actions**:
1.  **Tests**: Automatically run on every push to `main`.
2.  **Releases**: Pushing a tag (e.g., `v2.0.0`) automatically builds a ZIP file and creates a GitHub Release.

## 📖 Module Documentation
For detailed usage instructions of the module itself, please refer to the [Module README](smartmigrator/README.md).

---
**Author**: Reinaldo Tineo
