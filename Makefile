composer:
	composer validate
	composer update --no-interaction --prefer-dist

cs:
	vendor/bin/phpcs --standard=./ruleset.xml --cache=${HOME}/phpcs-cache/.phpcs-cache --encoding=utf-8 -sp src tests/KdybyTests
	vendor/bin/parallel-lint -e php,phpt --exclude vendor .

phpstan:
	vendor/bin/phpstan analyse -l 2 -c phpstan.neon src tests/KdybyTests

phpstan-generate-baseline:
	git clean -xdf tests/
	php -d memory_limit=-1 vendor/bin/phpstan.phar analyse -l 2 -c phpstan.neon src tests/KdybyTests --no-progress --generate-baseline

tester:
	vendor/bin/tester -s -C ./tests/KdybyTests/
