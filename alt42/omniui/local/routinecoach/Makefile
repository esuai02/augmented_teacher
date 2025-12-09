# Makefile for Routine Coach plugin
# Provides convenient commands for development, testing, and deployment

.PHONY: help install validate test deploy rollback clean backup health build

# Default environment
ENVIRONMENT ?= development

# Paths
PLUGIN_DIR := $(shell pwd)
DEPLOY_DIR := $(PLUGIN_DIR)/deploy
MOODLE_ROOT := /home/moodle/public_html/moodle

# Colors for output
RED := \033[0;31m
GREEN := \033[0;32m
YELLOW := \033[1;33m
NC := \033[0m

# Default target
help:
	@echo "Routine Coach Plugin Management"
	@echo "================================"
	@echo ""
	@echo "Available targets:"
	@echo "  make install       - Install plugin for the first time"
	@echo "  make validate      - Validate plugin files and configuration"
	@echo "  make test          - Run all tests (unit + E2E)"
	@echo "  make test-unit     - Run PHPUnit tests only"
	@echo "  make test-e2e      - Run E2E tests only"
	@echo "  make coverage      - Generate test coverage report"
	@echo "  make deploy        - Deploy plugin with backup and validation"
	@echo "  make rollback      - Rollback to previous version"
	@echo "  make backup        - Create backup of current installation"
	@echo "  make health        - Run health check"
	@echo "  make build         - Build AMD JavaScript modules"
	@echo "  make clean         - Clean build artifacts and caches"
	@echo "  make purge         - Purge all Moodle caches"
	@echo "  make watch         - Watch for changes and auto-build"
	@echo ""
	@echo "Environment variables:"
	@echo "  ENVIRONMENT=[development|staging|production] (current: $(ENVIRONMENT))"
	@echo ""
	@echo "Examples:"
	@echo "  make deploy ENVIRONMENT=production"
	@echo "  make test-unit"
	@echo "  make rollback"

# Install plugin for the first time
install:
	@echo "$(YELLOW)Installing Routine Coach plugin...$(NC)"
	@chmod +x $(DEPLOY_DIR)/pre_deploy_check.sh
	@chmod +x $(DEPLOY_DIR)/deploy.sh
	@$(DEPLOY_DIR)/pre_deploy_check.sh
	@if [ $$? -eq 0 ]; then \
		echo "$(GREEN)Validation passed, proceeding with installation...$(NC)"; \
		ENVIRONMENT=$(ENVIRONMENT) $(DEPLOY_DIR)/deploy.sh deploy; \
	else \
		echo "$(RED)Validation failed, fix errors before installation$(NC)"; \
		exit 1; \
	fi

# Validate plugin files
validate:
	@echo "$(YELLOW)Validating plugin...$(NC)"
	@chmod +x $(DEPLOY_DIR)/pre_deploy_check.sh
	@$(DEPLOY_DIR)/pre_deploy_check.sh

# Run all tests
test: test-unit test-e2e
	@echo "$(GREEN)All tests completed$(NC)"

# Run unit tests
test-unit:
	@echo "$(YELLOW)Running PHPUnit tests...$(NC)"
	@chmod +x tests/run_tests.sh
	@tests/run_tests.sh unit

# Run E2E tests
test-e2e:
	@echo "$(YELLOW)Running E2E tests...$(NC)"
	@if [ ! -d "node_modules" ]; then \
		npm install; \
	fi
	@npm run test:e2e

# Run specific test scenario
test-scenario:
	@echo "$(YELLOW)Running test scenario: $(SCENARIO)$(NC)"
	@chmod +x tests/run_tests.sh
	@tests/run_tests.sh scenario $(SCENARIO)

# Generate coverage report
coverage:
	@echo "$(YELLOW)Generating coverage report...$(NC)"
	@chmod +x tests/run_tests.sh
	@tests/run_tests.sh coverage
	@echo "$(GREEN)Coverage report generated at: tests/coverage/index.html$(NC)"

# Deploy plugin
deploy: validate backup
	@echo "$(YELLOW)Deploying plugin (Environment: $(ENVIRONMENT))...$(NC)"
	@chmod +x $(DEPLOY_DIR)/deploy.sh
	@ENVIRONMENT=$(ENVIRONMENT) $(DEPLOY_DIR)/deploy.sh deploy

# Rollback plugin
rollback:
	@echo "$(YELLOW)Rolling back plugin...$(NC)"
	@chmod +x $(DEPLOY_DIR)/rollback.sh
	@$(DEPLOY_DIR)/rollback.sh

# Create backup
backup:
	@echo "$(YELLOW)Creating backup...$(NC)"
	@chmod +x $(DEPLOY_DIR)/deploy.sh
	@ENVIRONMENT=$(ENVIRONMENT) $(DEPLOY_DIR)/deploy.sh backup

# Run health check
health:
	@echo "$(YELLOW)Running health check...$(NC)"
	@php cli/health_check.php

# Build AMD modules
build:
	@echo "$(YELLOW)Building AMD JavaScript modules...$(NC)"
	@cd $(MOODLE_ROOT) && php admin/cli/build_js.php
	@echo "$(GREEN)AMD modules built successfully$(NC)"

# Clean build artifacts
clean:
	@echo "$(YELLOW)Cleaning build artifacts...$(NC)"
	@rm -rf amd/build/*
	@rm -rf tests/coverage/*
	@rm -rf tests/logs/*
	@rm -rf tests/screenshots/*
	@rm -rf tests/playwright-report/*
	@rm -rf node_modules
	@echo "$(GREEN)Clean completed$(NC)"

# Purge Moodle caches
purge:
	@echo "$(YELLOW)Purging Moodle caches...$(NC)"
	@cd $(MOODLE_ROOT) && php admin/cli/purge_caches.php
	@echo "$(GREEN)Caches purged$(NC)"

# Watch for changes and auto-build
watch:
	@echo "$(YELLOW)Watching for changes...$(NC)"
	@while true; do \
		inotifywait -r -e modify,create,delete amd/src/; \
		make build; \
		echo "$(GREEN)Rebuild completed$(NC)"; \
	done

# Development setup
dev-setup:
	@echo "$(YELLOW)Setting up development environment...$(NC)"
	@npm install
	@composer install --no-interaction
	@make build
	@make validate
	@echo "$(GREEN)Development environment ready$(NC)"

# Production deployment
production-deploy:
	@echo "$(RED)WARNING: Deploying to PRODUCTION$(NC)"
	@read -p "Are you sure? (yes/no): " confirm; \
	if [ "$$confirm" = "yes" ]; then \
		make validate && \
		make test && \
		make deploy ENVIRONMENT=production; \
	else \
		echo "Deployment cancelled"; \
	fi

# Quick fix deployment (for hotfixes)
hotfix:
	@echo "$(YELLOW)Deploying hotfix...$(NC)"
	@make backup
	@make validate
	@make deploy ENVIRONMENT=$(ENVIRONMENT)
	@make health

# Database operations
db-backup:
	@echo "$(YELLOW)Backing up database tables...$(NC)"
	@mysqldump -h 58.180.27.46 -u moodle -p@MCtrigd7128 mathking \
		mdl_routinecoach_exam \
		mdl_routinecoach_routine \
		mdl_routinecoach_task \
		mdl_routinecoach_log \
		mdl_routinecoach_pref \
		> backup_$$(date +%Y%m%d_%H%M%S).sql
	@echo "$(GREEN)Database backup completed$(NC)"

db-restore:
	@echo "$(YELLOW)Restoring database from backup...$(NC)"
	@read -p "Enter backup file path: " backup_file; \
	mysql -h 58.180.27.46 -u moodle -p@MCtrigd7128 mathking < $$backup_file
	@echo "$(GREEN)Database restored$(NC)"

# Version management
version:
	@grep '^\$$plugin->version' version.php | grep -oP '\d+' || echo "No version found"

bump-version:
	@echo "$(YELLOW)Bumping version...$(NC)"
	@current=$$(grep '^\$$plugin->version' version.php | grep -oP '\d+'); \
	new=$$(date +%Y%m%d)00; \
	if [ "$$current" = "$$new" ]; then \
		new=$$(date +%Y%m%d)01; \
	fi; \
	sed -i "s/\$$plugin->version = $$current/\$$plugin->version = $$new/" version.php; \
	echo "Version bumped from $$current to $$new"

# Git operations
commit-ready:
	@make validate
	@make test-unit
	@git status

# Docker operations (if using Docker)
docker-up:
	@docker-compose up -d

docker-down:
	@docker-compose down

docker-logs:
	@docker-compose logs -f

# Monitoring
monitor:
	@watch -n 5 'php cli/health_check.php --json | jq .'

# Full CI/CD pipeline
ci-pipeline: validate test coverage
	@echo "$(GREEN)CI pipeline completed successfully$(NC)"

cd-pipeline: ci-pipeline deploy health
	@echo "$(GREEN)CD pipeline completed successfully$(NC)"

# Check all
check-all: validate health test coverage
	@echo "$(GREEN)All checks passed$(NC)"

.SILENT: help version