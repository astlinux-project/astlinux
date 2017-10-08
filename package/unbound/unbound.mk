#############################################################
#
# unbound
#
#############################################################

UNBOUND_VERSION = 1.6.6
UNBOUND_SITE = https://www.unbound.net/downloads
UNBOUND_SOURCE = unbound-$(UNBOUND_VERSION).tar.gz
UNBOUND_INSTALL_STAGING = YES

UNBOUND_DEPENDENCIES = host-bison host-flex openssl expat

UNBOUND_CONF_OPT = \
	--disable-rpath \
	--with-libexpat="$(STAGING_DIR)/usr" \
	--with-ssl="$(STAGING_DIR)/usr"

# GOST cipher support requires openssl extra engines
ifeq ($(BR2_PACKAGE_OPENSSL_ENGINES),y)
UNBOUND_CONF_OPT += --enable-gost
else
UNBOUND_CONF_OPT += --disable-gost
endif

define UNBOUND_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(STAGING_DIR)/usr/sbin/unbound-host $(TARGET_DIR)/usr/sbin/unbound-host
	ln -sf ../sbin/unbound-host $(TARGET_DIR)/usr/bin/host
	cp -a $(STAGING_DIR)/usr/lib/libunbound.so* $(TARGET_DIR)/usr/lib/
endef

define UNBOUND_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/unbound-host
	rm -f $(TARGET_DIR)/usr/bin/host
	rm -f $(TARGET_DIR)/usr/lib/libunbound.so*
endef

$(eval $(call AUTOTARGETS,package,unbound))
