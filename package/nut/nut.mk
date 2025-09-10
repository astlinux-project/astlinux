################################################################################
#
# nut
#
################################################################################

NUT_VERSION = 2.8.4
NUT_SOURCE = nut-$(NUT_VERSION).tar.gz
NUT_SITE = https://github.com/networkupstools/nut/releases/download/v$(NUT_VERSION)

NUT_INSTALL_STAGING = YES
NUT_DEPENDENCIES = host-pkg-config

NUT_CONF_OPT = \
	--with-pidpath=/var/run \
	--with-altpidpath=/var/run/ups \
	--with-dev \
	--without-nutconf \
	--without-doc \
	--without-python \
	--without-python2 \
	--without-python3 \
	--without-pynut \
	--with-user=nobody \
	--with-group=nobody \
	--with-all=auto \
	--with-drivers=usbhid-ups,netxml-ups \
	--with-drvpath=/usr/lib/ups \
	--datadir=/usr/share/ups \
	--with-udev-dir=/etc/udev \
	--sysconfdir=/etc/ups

NUT_CONF_ENV = \
	PKG_CONFIG_LIBDIR=$(STAGING_DIR)/usr/lib/pkgconfig:$(STAGING_DIR)/usr/share/pkgconfig \
	ax_cv_check_cflags__Werror__Wno_unknown_warning_option=no \
	ax_cv_check_cxxflags__Werror__Wno_unknown_warning_option=no \
	ac_cv_func_strcasecmp=yes \
	ac_cv_func_strdup=yes \
	ac_cv_func_strncasecmp=yes \
	ax_cv__printf_string_null=yes

# serial driver optional support
ifeq ($(BR2_PACKAGE_NUT_SERIAL_DRIVERS),y)
NUT_CONF_OPT += --with-serial
else
NUT_CONF_OPT += --without-serial
endif

ifeq ($(BR2_PACKAGE_AVAHI)$(BR2_PACKAGE_DBUS),yy)
NUT_DEPENDENCIES += avahi dbus
NUT_CONF_OPT += --with-avahi
else
NUT_CONF_OPT += --without-avahi
endif

NUT_CONF_OPT += --without-freeipmi

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

ifeq ($(BR2_PACKAGE_LIBUSB),y)
NUT_DEPENDENCIES += libusb
NUT_CONF_OPT += --with-usb
else ifeq ($(BR2_PACKAGE_LIBUSB_COMPAT),y)
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

## Disable SNMP support
#ifeq ($(BR2_PACKAGE_NETSNMP),y)
#NUT_DEPENDENCIES += netsnmp
#NUT_CONF_OPT += \
#	--with-snmp \
#	--with-net-snmp-config=$(STAGING_DIR)/usr/bin/net-snmp-config
#else
NUT_CONF_OPT += --without-snmp
#endif

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
