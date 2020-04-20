################################################################################
#
# nut
#
################################################################################

NUT_VERSION_MAJOR = 2.7
NUT_VERSION = $(NUT_VERSION_MAJOR).4
NUT_SITE = http://www.networkupstools.org/source/$(NUT_VERSION_MAJOR)/
NUT_DEPENDENCIES = host-pkg-config

# Our patch changes m4 macros, so we need to autoreconf
NUT_AUTORECONF = YES

NUT_CONF_OPT = \
	--with-drvpath=/usr/lib/ups \
	--with-altpidpath=/var/run/ups \
	--with-user=nobody \
	--with-group=nobody \
	--datadir=/usr/share/ups \
	--with-udev-dir=/etc/udev \
	--sysconfdir=/etc/ups

NUT_CONF_ENV = \
	GDLIB_CONFIG=$(STAGING_DIR)/usr/bin/gdlib-config \
	NET_SNMP_CONFIG=$(STAGING_DIR)/usr/bin/net-snmp-config

# For uClibc-based toolchains, nut forgets to link with -lm
ifeq ($(BR2_TOOLCHAIN_USES_UCLIBC),y)
NUT_CONF_ENV += LDFLAGS="$(TARGET_LDFLAGS) -lm"
endif

# serial driver optional support
ifeq ($(BR2_PACKAGE_NUT_SERIAL_DRIVERS),y)
NUT_CONF_OPT += --with-serial
else
NUT_CONF_OPT += --without-serial
endif

# Build all applicable drivers
NUT_CONF_OPT += --with-drivers=all

ifeq ($(BR2_PACKAGE_AVAHI)$(BR2_PACKAGE_DBUS),yy)
NUT_DEPENDENCIES += avahi dbus
NUT_CONF_OPT += --with-avahi
else
NUT_CONF_OPT += --without-avahi
endif

# gd with support for png is required for the CGI
ifeq ($(BR2_PACKAGE_GD)$(BR2_PACKAGE_LIBPNG),yy)
NUT_DEPENDENCIES += gd libpng
NUT_CONF_OPT += --with-cgi
else
NUT_CONF_OPT += --without-cgi
endif

# libltdl (libtool) is needed for nut-scanner
ifeq ($(BR2_PACKAGE_LIBTOOL),y)
NUT_DEPENDENCIES += libtool
NUT_CONF_OPT += --with-libltdl
else
NUT_CONF_OPT += --without-libltdl
endif

ifeq ($(BR2_PACKAGE_LIBUSB_COMPAT),y)
NUT_DEPENDENCIES += libusb-compat
NUT_CONF_OPT += --with-usb
else
NUT_CONF_OPT += --without-usb
endif

ifeq ($(BR2_PACKAGE_NEON_EXPAT)$(BR2_PACKAGE_NEON_LIBXML2),y)
NUT_DEPENDENCIES += neon
NUT_CONF_OPT += --with-neon
else
NUT_CONF_OPT += --without-neon
endif

ifeq ($(BR2_PACKAGE_NETSNMP),y)
NUT_DEPENDENCIES += netsnmp
NUT_CONF_OPT += --with-snmp
else
NUT_CONF_OPT += --without-snmp
endif

ifeq ($(BR2_PACKAGE_OPENSSL),y)
NUT_DEPENDENCIES += openssl
NUT_CONF_OPT += --with-ssl
else
NUT_CONF_OPT += --without-ssl
endif

define NUT_INSTALL_SCRIPT
	$(INSTALL) -D -m 755 package/nut/ups.init $(TARGET_DIR)/etc/init.d/ups
	$(INSTALL) -D -m 755 package/nut/upsnotify.sh $(TARGET_DIR)/stat/etc/ups/upsnotify
	for i in upsd.conf upsd.users ups.conf upsmon.conf upssched.conf; do \
	  cp $(TARGET_DIR)/etc/ups/$$i.sample $(TARGET_DIR)/stat/etc/ups/$$i ; \
	done
	rm -rf $(TARGET_DIR)/etc/ups
	ln -s /tmp/etc/ups $(TARGET_DIR)/etc/ups
	ln -sf ../../init.d/ups $(TARGET_DIR)/etc/runlevels/default/S35ups
	ln -sf ../../init.d/ups $(TARGET_DIR)/etc/runlevels/default/K21ups
endef

NUT_POST_INSTALL_TARGET_HOOKS += NUT_INSTALL_SCRIPT

NUT_UNINSTALL_STAGING_OPT = --version

define NUT_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/etc/init.d/ups
	rm -f $(TARGET_DIR)/etc/ups
	rm -rf $(TARGET_DIR)/stat/etc/ups
	rm -rf $(TARGET_DIR)/usr/lib/ups
	rm -rf $(TARGET_DIR)/usr/share/ups
	rm -f $(TARGET_DIR)/etc/runlevels/default/S35ups
	rm -f $(TARGET_DIR)/etc/runlevels/default/K21ups
endef

$(eval $(call AUTOTARGETS,package,nut))
