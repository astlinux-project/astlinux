#############################################################
#
# inadyn
#
#############################################################

INADYN_VERSION = 1.96.2
INADYN_SOURCE:=inadyn-$(INADYN_VERSION).tar.gz
INADYN_SITE = https://astlinux-project.org/files

define INADYN_DDCLIENT_EXTRACT
	$(INSTALL) -m 0755 -D package/inadyn/ddclient/ddclient.pl $(@D)/ddclient/ddclient
endef
INADYN_POST_EXTRACT_HOOKS += INADYN_DDCLIENT_EXTRACT

INADYN_UNINSTALL_STAGING_OPT = --version

define INADYN_CONFIGURE_CMDS
	@echo "No configure"
endef

INADYN_MAKE_OPT = CC='$(TARGET_CC)' LD='$(TARGET_LD)' -C $(@D)

define INADYN_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/bin/linux/inadyn $(TARGET_DIR)/usr/sbin/inadyn
	$(INSTALL) -m 0755 -D package/inadyn/dynamicdns.init $(TARGET_DIR)/etc/init.d/dynamicdns
	ln -sf /tmp/etc/inadyn.conf $(TARGET_DIR)/etc/inadyn.conf
	$(if $(BR2_PACKAGE_PERL), \
		$(INSTALL) -m 0755 -D $(@D)/ddclient/ddclient $(TARGET_DIR)/usr/sbin/ddclient ; \
		$(INSTALL) -m 0644 -D package/inadyn/ddclient/ddclient.conf $(TARGET_DIR)/stat/etc/ddclient.conf ; \
		ln -sf /tmp/etc/ddclient.conf $(TARGET_DIR)/etc/ddclient.conf \
	)
endef

define INADYN_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/inadyn
	rm -f $(TARGET_DIR)/etc/init.d/dynamicdns
	rm -f $(TARGET_DIR)/usr/sbin/ddclient
	rm -f $(TARGET_DIR)/stat/etc/ddclient.conf
endef

$(eval $(call AUTOTARGETS,package,inadyn))
