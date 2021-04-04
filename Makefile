.PHONY: qa dev cs cfx tests build

qa: cs

cs:
	vendor/bin/codesniffer app

cfx:
	vendor/bin/codefixer app

phpstan:
	echo "OK"

tests:
	echo "OK"

tests-coverage:
	echo "OK"

#####################
# DEPLOY ########## #
#####################

build:
	mkdir -p var/log var/tmp
	rm -rf var/log/** var/tmp/**
	chmod 0777 var/log var/tmp

#####################
# LOCAL DEVELOPMENT #
#####################

dev:
	NETTE_DEBUG=1 NETTE_ENV=dev php -S 0.0.0.0:8000 -t www
