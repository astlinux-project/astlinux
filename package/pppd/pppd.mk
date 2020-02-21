#############################################################
#
# pppd
#
#############################################################

PPPD_VERSION = 2.4.8
PPPD_SOURCE = ppp-$(PPPD_VERSION).tar.gz
PPPD_SITE = https://github.com/paulusmack/ppp/archive/ppp-$(PPPD_VERSION)
PPPD_DEPENDENCIES = openssl

PPPD_INSTALL_STAGING = YES
PPPD_TARGET_BINS = chat pppd pppdump pppstats
PPPD_RADIUS_CONF = dictionary dictionary.ascend dictionary.compat \
			dictionary.merit dictionary.microsoft \
			issue port-id-map realms servers radiusclient.conf

ifeq ($(BR2_PACKAGE_PPPD_FILTER),y)
PPPD_DEPENDENCIES += libpcap
PPPD_MAKE_OPT += FILTER=y
endif

ifeq ($(BR2_INET_IPV6),y)
PPPD_MAKE_OPT += HAVE_INET6=y
endif

define PPPD_CONFIGURE_CMDS
	$(SED) 's/FILTER=y/#FILTER=y/' $(PPPD_DIR)/pppd/Makefile.linux
	$(SED) 's/ifneq ($$(wildcard \/usr\/include\/pcap-bpf.h),)/ifdef FILTER/' $(PPPD_DIR)/*/Makefile.linux
	( cd $(@D); $(TARGET_MAKE_ENV) ./configure --prefix=/usr )
endef

define PPPD_BUILD_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) CC="$(TARGET_CC)" COPTS="$(TARGET_CFLAGS)" \
		-C $(@D) $(PPPD_MAKE_OPT)
endef

define PPPD_UNINSTALL_TARGET_CMDS
	rm -f $(addprefix $(TARGET_DIR)/usr/sbin/, $(PPPD_TARGET_BINS))
	rm -f $(TARGET_DIR)/usr/sbin/pppoe-discovery
	rm -rf $(TARGET_DIR)/usr/lib/pppd
	rm -rf $(TARGET_DIR)/etc/ppp/radius
endef

ifeq ($(BR2_PACKAGE_PPPD_RADIUS),y)
define PPPD_INSTALL_RADIUS
	$(INSTALL) -D $(PPPD_DIR)/pppd/plugins/radius/radattr.so \
		$(TARGET_DIR)/usr/lib/pppd/$(PPPD_VERSION)/radattr.so
	$(INSTALL) -D $(PPPD_DIR)/pppd/plugins/radius/radius.so \
		$(TARGET_DIR)/usr/lib/pppd/$(PPPD_VERSION)/radius.so
	$(INSTALL) -D $(PPPD_DIR)/pppd/plugins/radius/radrealms.so \
		$(TARGET_DIR)/usr/lib/pppd/$(PPPD_VERSION)/radrealms.so
	for m in $(PPPD_RADIUS_CONF); do \
		$(INSTALL) -m 644 -D $(PPPD_DIR)/pppd/plugins/radius/etc/$$m \
			$(TARGET_DIR)/etc/ppp/radius/$$m; \
	done
	$(SED) 's:/usr/local/etc:/etc:' \
		$(TARGET_DIR)/etc/ppp/radius/radiusclient.conf
	$(SED) 's:/usr/local/sbin:/usr/sbin:' \
		$(TARGET_DIR)/etc/ppp/radius/radiusclient.conf
	$(SED) 's:/etc/radiusclient:/etc/ppp/radius:g' \
		$(TARGET_DIR)/etc/ppp/radius/*
endef
endif

define PPPD_INSTALL_TARGET_CMDS
	for sbin in $(PPPD_TARGET_BINS); do \
		$(INSTALL) -D $(PPPD_DIR)/$$sbin/$$sbin \
			$(TARGET_DIR)/usr/sbin/$$sbin; \
	done
	$(INSTALL) -D $(PPPD_DIR)/pppd/plugins/minconn.so \
		$(TARGET_DIR)/usr/lib/pppd/$(PPPD_VERSION)/minconn.so
	$(INSTALL) -D $(PPPD_DIR)/pppd/plugins/passprompt.so \
		$(TARGET_DIR)/usr/lib/pppd/$(PPPD_VERSION)/passprompt.so
	$(INSTALL) -D $(PPPD_DIR)/pppd/plugins/passwordfd.so \
		$(TARGET_DIR)/usr/lib/pppd/$(PPPD_VERSION)/passwordfd.so
	$(INSTALL) -D $(PPPD_DIR)/pppd/plugins/pppoatm/pppoatm.so \
		$(TARGET_DIR)/usr/lib/pppd/$(PPPD_VERSION)/pppoatm.so
	$(INSTALL) -D $(PPPD_DIR)/pppd/plugins/rp-pppoe/rp-pppoe.so \
		$(TARGET_DIR)/usr/lib/pppd/$(PPPD_VERSION)/rp-pppoe.so
	$(INSTALL) -D $(PPPD_DIR)/pppd/plugins/rp-pppoe/pppoe-discovery \
		$(TARGET_DIR)/usr/sbin/pppoe-discovery
	$(INSTALL) -D $(PPPD_DIR)/pppd/plugins/winbind.so \
		$(TARGET_DIR)/usr/lib/pppd/$(PPPD_VERSION)/winbind.so
	$(INSTALL) -D $(PPPD_DIR)/pppd/plugins/pppol2tp/openl2tp.so \
		$(TARGET_DIR)/usr/lib/pppd/$(PPPD_VERSION)/openl2tp.so
	$(INSTALL) -D $(PPPD_DIR)/pppd/plugins/pppol2tp/pppol2tp.so \
		$(TARGET_DIR)/usr/lib/pppd/$(PPPD_VERSION)/pppol2tp.so
	$(PPPD_INSTALL_RADIUS)
	ln -snf /tmp/etc/ppp $(TARGET_DIR)/etc/ppp
endef

define PPPD_INSTALL_STAGING_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) INSTROOT=$(STAGING_DIR)/ -C $(@D) $(PPPD_MAKE_OPT) install-devel
endef

$(eval $(call GENTARGETS,package,pppd))
