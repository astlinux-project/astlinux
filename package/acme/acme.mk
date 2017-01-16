################################################################################
#
# acme
#
################################################################################

ACME_VERSION = 2.6.5
ACME_SOURCE = acme.sh-$(ACME_VERSION).tar.gz
ACME_SITE = http://files.astlinux-project.org

ACME_REMOVE_TARGET_DNSAPI = dns_myapi.sh $(if $(BR2_PACKAGE_BIND),,dns_nsupdate.sh) README.md

##
## curl -L -o dl/acme.sh-2.6.5-2017-01-13.tar.gz https://github.com/Neilpang/acme.sh/archive/master.tar.gz
## ./scripts/upload-dl-pair dl/acme.sh-2.6.5-2017-01-13.tar.gz
##

define ACME_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 0755 package/acme/acme-client.sh $(TARGET_DIR)/usr/sbin/acme-client
	$(INSTALL) -D -m 0755 package/acme/astlinux.sh $(TARGET_DIR)/stat/etc/acme/deploy/astlinux.sh
	$(INSTALL) -D -m 0755 $(@D)/acme.sh $(TARGET_DIR)/stat/etc/acme/acme.sh
	cp -a $(@D)/dnsapi $(TARGET_DIR)/stat/etc/acme/
	ln -s /mnt/kd/acme $(TARGET_DIR)/etc/acme
	# Remove non-required dnsapi files
	rm -f $(addprefix $(TARGET_DIR)/stat/etc/acme/dnsapi/, $(ACME_REMOVE_TARGET_DNSAPI))
	# Make sure the dnsapi scripts are executable
	find $(TARGET_DIR)/stat/etc/acme/dnsapi/ -name '*.sh' -print0 | xargs -0 chmod 755
endef

define ACME_UNINSTALL_TARGET_CMDS
	rm -f  $(TARGET_DIR)/usr/sbin/acme-client
	rm -f  $(TARGET_DIR)/etc/acme
	rm -rf $(TARGET_DIR)/stat/etc/acme
endef

$(eval $(call GENTARGETS,package,acme))
