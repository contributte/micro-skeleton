.PHONY: qa dev cs cfx tests build

qa: cs

cs:
	vendor/bin/codesniffer app

cfx:
	vendor/bin/codefixer app

tests:
	echo "OK"

tests-coverage:
	echo "OK"

#####################
# LOCAL DEVELOPMENT #
#####################

dev:
	NETTE_DEBUG=1 NETTE_ENV=dev php -S 0.0.0.0:8000 -t www
