# If the first argument is "upgrade"...
ifeq (upgrade,$(firstword $(MAKECMDGOALS)))
  # use the rest as arguments for "upgrade"
  UPGRADE_ARGS := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
  # ...and turn them into do-nothing targets
  $(eval $(UPGRADE_ARGS):;@:)
endif



.PHONY: release upgrade

release:
	./tools/make-release.sh

upgrade:
	./tools/make-upgrade.sh $(UPGRADE_ARGS)

clean:
	rm -r build