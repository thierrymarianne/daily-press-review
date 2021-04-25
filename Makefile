SHELL:=/bin/bash

## See also https://marmelab.com/blog/2016/02/29/auto-documented-makefile.html

.PHONY: help

help:
		@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

clone-emoji-repository: ## Clone emoji repository
		@/bin/bash -c 'source ./bin/functions.sh && clone_emoji_repository'

create-network: ## Create Docker network
		@/bin/bash -c 'source ./bin/functions.sh && create_network'

remove-mysql-container: ## Remove MySQL container
		@/bin/bash -c 'source ./bin/functions.sh && remove_mysql_container'

build-apache-container: ## Build Apache image
		@/bin/bash -c 'source ./bin/functions.sh && build_apache_container'

set-permissions-in-apache-container: # Set permissions in Apache container
		@/bin/bash -c 'source ./bin/functions.sh && set_permissions_in_apache_container'

get-apache-interactive-shell: ## Get Apache interactive shell
		@/bin/bash -c 'source ./bin/functions.sh && get_apache_container_interactive_shell'

remove-apache-container: ## Remove Apache container
		@/bin/bash -c 'source ./bin/functions.sh && remove_apache_container'

run-mysql-container: ## Run MySQL container (https://hub.docker.com/_/mysql/)
		@/bin/bash -c 'source ./bin/functions.sh && run_mysql_container'

run-mysql-client: ## Run MySQL client
		@/bin/bash -c 'source ./bin/functions.sh && run_mysql_client'

initialize-mysql-volume: ## Set up MySQL container
		@/bin/bash -c 'source ./bin/functions.sh && initialize_mysql_volume'

grant-privileges: ## Grant privileges
		@/bin/bash -c 'source ./bin/functions.sh && grant_privileges'

build-php-container: ## Build PHP image
		@/bin/bash -c 'source ./bin/functions.sh && build_php_container'

dispatch-messages-from-aggregates-lists: ## Produce messages from aggregates list
		@/bin/bash -c 'source ./bin/functions.sh && dispatch_messages_for_aggregates_list'

dispatch-messages-from-member-timeline: ## Produce messages from member timeline
		@/bin/bash -c 'source ./bin/functions.sh && dispatch_messages_from_member_timeline'

dispatch-messages-from-members-lists: ## Produce messages from members lists
		@/bin/bash -c 'source ./bin/functions.sh && dispatch_messages_from_members_lists'

dispatch-messages-from-networks: ## Produce messages for networks
		@/bin/bash -c 'source ./bin/functions.sh && dispatch_messages_for_networks'

dispatch-messages-from-news-list: ## Produce messages from news list
		@/bin/bash -c 'source ./bin/functions.sh && dispatch_messages_for_news_list'

dispatch-messages-from-search-query: ## Produce messages from search query
		@/bin/bash -c 'source ./bin/functions.sh && dispatch_messages_for_search_query'

dispatch-messages-from-timely-statuses: ## Produce messages for timely statuses
		@/bin/bash -c 'source ./bin/functions.sh && dispatch_messages_for_timely_statuses'

remove-php-container: ## Remove PHP container
		@/bin/bash -c 'source ./bin/functions.sh && remove_php_container'

install-php-dependencies: ## Install PHP dependencies (APP_ENV=prod)
		@/bin/bash -c 'source ./bin/functions.sh && install_php_dependencies'

run-php: ## Run PHP with arguments
		@/bin/bash -c 'source ./bin/functions.sh && run_php'

run-php-script: ## Run PHP script
		@/bin/bash -c 'source ./bin/functions.sh && run_php_script'

run-stack: ## Run stack and its dependencies
		@/bin/bash -c 'source ./bin/functions.sh && run_stack'

run-worker: ## Run worker and its dependencies
		@/bin/bash -c 'source ./bin/functions.sh && run_worker'

build-php-fpm-container: ## Build PHP-FPM image
		@/bin/bash -c 'source ./bin/functions.sh && build_php_fpm_container'

run-php-fpm: ## Run PHP-FPM worker
		@/bin/bash -c 'source ./bin/functions.sh && run_php_fpm'

remove-php-fpm-container: ## Remove PHP-FPM container
		@/bin/bash -c 'source ./bin/functions.sh && remove_php_fpm_container'

create-database-schema-test: # Create database schema in test environment
		@/bin/bash -c 'source ./bin/functions.sh && create_database_test_schema'

create-prod-like-schema: ## Create production-like schema
		@/bin/bash -c 'export PROJECT_DIR='/var/www/devobs'; source ./bin/functions.sh && create_database_prod_like_schema '

diff-schema-of-read-database: ## Generate schema migrations scripts
		@/bin/bash -c 'export PROJECT_DIR='/var/www/devobs'; source ./bin/functions.sh && compute_schema_differences_for_read_database'

diff-schema-of-write-database: ## Generate schema migrations scripts
		@/bin/bash -c 'export PROJECT_DIR='/var/www/devobs'; source ./bin/functions.sh && compute_schema_differences_for_write_database'

migrate-schema-of-read-database: ## Migrate the read database schema
		@/bin/bash -c 'export PROJECT_DIR='/var/www/devobs'; source ./bin/functions.sh && migrate_schema_of_read_database'

migrate-schema-of-write-database: ## Migrate the write database schema
		@/bin/bash -c 'export PROJECT_DIR='/var/www/devobs'; source ./bin/functions.sh && migrate_schema_of_write_database'

list-amqp-messages: ## List AMQP messags
		@/bin/bash -c 'source ./bin/functions.sh && list_amqp_queues'

purge-amqp-queue: ## Purge queue
		@/bin/bash -c 'source ./bin/functions.sh && purge_queues'

run-rabbitmq-container: ## Run RabbitMQ container (https://hub.docker.com/_/rabbitmq/)
		@/bin/bash -c 'source ./bin/functions.sh && run_rabbitmq_container'

remove-rabbitmq-container: ## Remove RabbitMQ container
		@/bin/bash -c 'source ./bin/functions.sh && remove_rabbitmq_container'

refresh-statuses: ## Refresh statuses
		@/bin/bash -c 'source ./bin/functions.sh && refresh_statuses'

handle-messages: ## Consume twitter API messages
		@/bin/bash -c 'export PROJECT_DIR=`pwd` DOCKER_MODE=1 && cd "${PROJECT_DIR}" && source bin/consume_twitter_api.sh'

handle-news-messages: ## Consume twitter API news messages
		@/bin/bash -c 'export PROJECT_DIR=`pwd` && cd "${PROJECT_DIR}" && source bin/consume_twitter_api_for_news.sh'

keep-php-container-running: ## Keep a running container having PHP
		@/bin/bash -c 'source ./bin/functions.sh && keep_php_container_running'

stop-workers: ## Stop workers
		@/bin/bash -c 'source ./bin/functions.sh && stop_workers'

today-statuses: ## Filter the statuses for today from the log file
		@/bin/bash -c 'source ./bin/functions.sh && today_statuses'

follow-today-statuses: ## Filter the statuses for today from the log file
		@/bin/bash -c 'source ./bin/functions.sh && follow_today_statuses'

run-php-unit-tests: ## Run unit tests with PHPUnit
		@/bin/bash -c 'source ./bin/functions.sh && run_php_unit_tests'

run-php-features-tests: ## Run features tests with Behat
		@/bin/bash -c 'source ./bin/functions.sh && run_php_features_tests'

run-composer: # Run composer
		@/bin/bash -c 'source ./bin/functions.sh && run_composer'

restart-web-server: # Restart web Server
		@/bin/bash -c 'source ./bin/functions.sh && restart_web_server'
