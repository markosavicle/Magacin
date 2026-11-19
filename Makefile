# Thin wrapper around setup.sh, so `make demo` and `./setup.sh demo` behave the same.
# On Windows without make, run ./setup.sh from Git Bash or WSL.

SETUP := ./setup.sh

.DEFAULT_GOAL := help
.PHONY: help demo env up down logs admin test shell backup verify reset

help:   ## List the commands
	@grep -E '^[a-z]+:.*## ' $(MAKEFILE_LIST) | awk -F ':.*## ' '{printf "  make %-8s %s\n", $$1, $$2}'

demo:   ## Build, start, seed demo data, print URL and admin login
	@bash $(SETUP) demo

env:    ## Create .env with random passwords (only if missing)
	@bash $(SETUP) env

up:     ## Start without rebuilding
	@bash $(SETUP) up

down:   ## Stop the stack (data is kept)
	@bash $(SETUP) down

logs:   ## Follow logs of all containers
	@bash $(SETUP) logs

admin:  ## Print the admin login
	@bash $(SETUP) admin

test:   ## Run the test suite in a container
	@bash $(SETUP) test

shell:  ## Open a shell in the app container
	@bash $(SETUP) shell

backup: ## Run a database + files backup now
	@bash $(SETUP) backup

verify: ## Recalculate and check every partner balance
	@bash $(SETUP) verify

reset:  ## Delete all data (asks first)
	@bash $(SETUP) reset
