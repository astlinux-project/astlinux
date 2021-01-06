#############################################################
#
# pppd
#
#############################################################

PPPD_VERSION = 2.4.9
PPPD_SOURCE = ppp-$(PPPD_VERSION).tar.gz
PPPD_SITE = https://github.com/paulusmack/ppp/archive/ppp-$(PPPD_VERSION)
PPPD_DEPENDENCIES = openssl libpcap

PPPD_INSTALL_STAGING = YES
PPPD_TARGET_BINS = chat pppd pppdump pppstats

PPPD_MAKE_OPT =

define PPPD_CONFIGURE_CMDS
	$(SED) 's/^USE_EAPTLS=y/#USE_EAPTLS=y/' $(PPPD_DIR)/pppd/Makefile.linux
	( cd $(@D); $(TARGET_MAKE_ENV) ./configure --prefix=/usr )
endef

define PPPD_BUILD_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) CC="$(TARGET_CC)" COPTS="$(TARGET_CFLAGS)" \
		-C $(@D) $(PPPD_MAKE_OPT)
endef

define PPPD_UNINSTALL_TARGET_CMDS
	rm -f $(addprefix $(TARGET_DIR)/usr/sbin/, $(PPPD_TARGET_BINS))
	rm -f $(TARGET_DIR)/usr/sbin/pppoe-*
	rm -rf $(TARGET_DIR)/usr/lib/pppd
endef

define PPPD_INSTALL_TARGET_CMDS
	for sbin in $(PPPD_TARGET_BINS); do \
		$(INSTALL) -D $(PPPD_DIR)/$$sbin/$$sbin \
			$(TARGET_DIR)/usr/sbin/$$sbin; \
	done
	for sbin in pppoe-start pppoe-stop pppoe-restart pppoe-status; do \
		$(INSTALL) -D -m 0755 package/pppd/pppoe/$$sbin \
			$(TARGET_DIR)/usr/sbin/$$sbin; \
	done
	$(INSTALL) -D $(PPPD_DIR)/pppd/plugins/minconn.so \
		$(TARGET_DIR)/usr/lib/pppd/$(PPPD_VERSION)/minconn.so
	$(INSTALL) -D $(PPPD_DIR)/pppd/plugins/passprompt.so \
		$(TARGET_DIR)/usr/lib/pppd/$(PPPD_VERSION)/passprompt.so
	$(INSTALL) -D $(PPPD_DIR)/pppd/plugins/passwordfd.so \
		$(TARGET_DIR)/usr/lib/pppd/$(PPPD_VERSION)/passwordfd.so
##	No ATM kernel support
#	$(INSTALL) -D $(PPPD_DIR)/pppd/plugins/pppoatm/pppoatm.so \
#		$(TARGET_DIR)/usr/lib/pppd/$(PPPD_VERSION)/pppoatm.so
##
	$(INSTALL) -D $(PPPD_DIR)/pppd/plugins/pppoe/pppoe.so \
		$(TARGET_DIR)/usr/lib/pppd/$(PPPD_VERSION)/pppoe.so
	$(INSTALL) -D $(PPPD_DIR)/pppd/plugins/pppoe/pppoe-discovery \
		$(TARGET_DIR)/usr/sbin/pppoe-discovery
	$(INSTALL) -D $(PPPD_DIR)/pppd/plugins/winbind.so \
		$(TARGET_DIR)/usr/lib/pppd/$(PPPD_VERSION)/winbind.so
	$(INSTALL) -D $(PPPD_DIR)/pppd/plugins/pppol2tp/openl2tp.so \
		$(TARGET_DIR)/usr/lib/pppd/$(PPPD_VERSION)/openl2tp.so
	$(INSTALL) -D $(PPPD_DIR)/pppd/plugins/pppol2tp/pppol2tp.so \
		$(TARGET_DIR)/usr/lib/pppd/$(PPPD_VERSION)/pppol2tp.so
	ln -snf /tmp/etc/ppp $(TARGET_DIR)/etc/ppp
endef

define PPPD_INSTALL_STAGING_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) INSTROOT=$(STAGING_DIR)/ -C $(@D) $(PPPD_MAKE_OPT) install-devel
endef

$(eval $(call GENTARGETS,package,pppd))
