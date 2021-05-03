.PHONY: install qa cs csf phpstan tests coverage-clover coverage-html

install:
	composer update

qa: phpstan cs

cs:
	vendor/bin/phpcs --standard=vendor/ninjify/coding-standard/ruleset-gamee.xml --extensions=php,phpt --tab-width=4 --ignore=temp -sp src

csf:
	vendor/bin/phpcbf --standard=vendor/ninjify/coding-standard/ruleset-gamee.xml --extensions=php,phpt --tab-width=4 --ignore=temp -sp src

phpstan:
	vendor/phpstan/phpstan/bin/phpstan analyse -c vendor/gamee/php-code-checker-rules/phpstan.neon src --level 7

tests:
	vendor/bin/tester -s --colors 1 -C tests

coverage-clover:
	vendor/bin/tester -s -p phpdbg --colors 1 -C --coverage ./coverage.xml --coverage-src ./src ./tests

coverage-html:
	vendor/bin/tester -s -p phpdbg --colors 1 -C --coverage ./coverage.html --coverage-src ./src ./tests
