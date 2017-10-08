#############################################################
#
# dnsmasq
#
#############################################################

DNSMASQ_VERSION = 2.78
DNSMASQ_SITE = http://thekelleys.org.uk/dnsmasq
DNSMASQ_MAKE_ENV = $(TARGET_MAKE_ENV) CC="$(TARGET_CC)"
DNSMASQ_MAKE_OPT = COPTS="$(DNSMASQ_COPTS)" PREFIX=/usr CFLAGS="$(TARGET_CFLAGS)"
DNSMASQ_MAKE_OPT += DESTDIR=$(TARGET_DIR) LDFLAGS="$(TARGET_LDFLAGS)"
DNSMASQ_DEPENDENCIES = host-pkg-config

ifneq ($(BR2_INET_IPV6),y)
	DNSMASQ_COPTS += -DNO_IPV6
endif

ifneq ($(BR2_PACKAGE_DNSMASQ_DHCP),y)
	DNSMASQ_COPTS += -DNO_DHCP
endif

ifneq ($(BR2_PACKAGE_DNSMASQ_TFTP),y)
	DNSMASQ_COPTS += -DNO_TFTP
endif

ifeq ($(BR2_PACKAGE_DNSMASQ_IDN),y)
	DNSMASQ_MAKE_OPT += all-i18n
	DNSMASQ_DEPENDENCIES += libidn libintl
	DNSMASQ_MAKE_ENV += LDFLAGS+="-lintl -lidn"
endif

ifneq ($(BR2_LARGEFILE),y)
	DNSMASQ_COPTS += -DNO_LARGEFILE
endif

ifeq ($(BR2_PACKAGE_DBUS),y)
	DNSMASQ_DEPENDENCIES += dbus
endif

define DNSMASQ_FIX_PKGCONFIG
	$(SED) 's^PKG_CONFIG = pkg-config^PKG_CONFIG = $(PKG_CONFIG_HOST_BINARY)^' \
		$(DNSMASQ_DIR)/Makefile
endef

ifeq ($(BR2_PACKAGE_DBUS),y)
define DNSMASQ_ENABLE_DBUS
	$(SED) 's^.*#define HAVE_DBUS.*^#define HAVE_DBUS^' \
		$(DNSMASQ_DIR)/src/config.h
endef
else
define DNSMASQ_ENABLE_DBUS
	$(SED) 's^.*#define HAVE_DBUS.*^/* #define HAVE_DBUS */^' \
		$(DNSMASQ_DIR)/src/config.h
endef
endif

ifeq ($(BR2_PACKAGE_IPSET),y)
define DNSMASQ_ENABLE_IPSET
	$(SED) 's^.*#define HAVE_IPSET.*^#define HAVE_IPSET^' \
		$(DNSMASQ_DIR)/src/config.h
endef
else
define DNSMASQ_ENABLE_IPSET
	$(SED) 's^.*#define HAVE_IPSET.*^/* #define HAVE_IPSET */^' \
		$(DNSMASQ_DIR)/src/config.h
endef
endif

define DNSMASQ_BUILD_CMDS
	$(DNSMASQ_FIX_PKGCONFIG)
	$(DNSMASQ_ENABLE_DBUS)
	$(DNSMASQ_ENABLE_IPSET)
	$(DNSMASQ_MAKE_ENV) $(MAKE1) -C $(@D) $(DNSMASQ_MAKE_OPT)
endef

define DNSMASQ_INSTALL_TARGET_CMDS
	$(DNSMASQ_MAKE_ENV) $(MAKE) -C $(@D) $(DNSMASQ_MAKE_OPT) install
	$(INSTALL) -m 0755 -D package/dnsmasq/dnsmasq.init $(TARGET_DIR)/etc/init.d/dnsmasq
	$(INSTALL) -m 0644 -D package/dnsmasq/dnsmasq.static $(TARGET_DIR)/stat/etc/dnsmasq.static
	ln -sf /tmp/etc/dnsmasq.conf $(TARGET_DIR)/etc/dnsmasq.conf
	ln -sf /tmp/etc/dnsmasq.static $(TARGET_DIR)/etc/dnsmasq.static
endef

define DNSMASQ_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/dnsmasq
endef

$(eval $(call GENTARGETS,package,dnsmasq))
