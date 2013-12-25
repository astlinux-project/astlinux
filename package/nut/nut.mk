################################################################################
#
# nut
#
################################################################################

NUT_VERSION_MAJOR = 2.7
NUT_VERSION = $(NUT_VERSION_MAJOR).1
NUT_SITE = http://www.networkupstools.org/source/$(NUT_VERSION_MAJOR)/
NUT_DEPENDENCIES = host-pkg-config

# Our patch changes m4 macros, so we need to autoreconf
NUT_AUTORECONF = YES

# Put the PID files in a read-write place (/var/run is a tmpfs)
# since the default location (/var/state/ups) maybe readonly.
NUT_CONF_OPT = \
	--with-altpidpath=/var/run/upsd

NUT_CONF_ENV = \
	GDLIB_CONFIG=$(STAGING_DIR)/usr/bin/gdlib-config \
	NET_SNMP_CONFIG=$(STAGING_DIR)/usr/bin/net-snmp-config

# For uClibc-based toolchains, nut forgets to link with -lm
ifeq ($(BR2_TOOLCHAIN_USES_UCLIBC),y)
NUT_CONF_ENV += LDFLAGS="$(TARGET_LDFLAGS) -lm"
endif

# All drivers without serial
NUT_CONF_OPT += --without-serial
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
	#$(INSTALL) -D -m 755 package/nut/upsd.init $(TARGET_DIR)/etc/init.d/upsd
	mkdir -p $(TARGET_DIR)/stat/etc/upsd
	for i in upsd.conf upsd.users ups.conf upsmon.conf upssched.conf; do \
	  mv $(TARGET_DIR)/etc/$$i.sample $(TARGET_DIR)/stat/etc/upsd/$$i ; \
       	  ln -sf /tmp/etc/$$i $(TARGET_DIR)/etc/$$i ; \
	done
endef

NUT_POST_INSTALL_TARGET_HOOKS += NUT_INSTALL_SCRIPT

$(eval $(call AUTOTARGETS,package,nut))
