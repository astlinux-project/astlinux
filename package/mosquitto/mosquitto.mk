################################################################################
#
# mosquitto
#
################################################################################

MOSQUITTO_VERSION = 1.6.13
MOSQUITTO_SITE = https://mosquitto.org/files/source
MOSQUITTO_INSTALL_STAGING = YES
MOSQUITTO_DEPENDENCIES = host-pkg-config

MOSQUITTO_MAKE_OPTS = \
	UNAME=Linux \
	STRIP=true \
	prefix=/usr \
	WITH_SHARED_LIBRARIES=yes \
	WITH_STATIC_LIBRARIES=no \
	WITH_ADNS=no \
	WITH_SYSTEMD=no \
	WITH_WRAP=no \
	WITH_DOCS=no

ifeq ($(BR2_TOOLCHAIN_HAS_THREADS),y)
MOSQUITTO_MAKE_OPTS += WITH_THREADING=yes
else
MOSQUITTO_MAKE_OPTS += WITH_THREADING=no
endif

ifeq ($(BR2_PACKAGE_OPENSSL),y)
MOSQUITTO_DEPENDENCIES += openssl
MOSQUITTO_MAKE_OPTS += WITH_TLS=yes
else
MOSQUITTO_MAKE_OPTS += WITH_TLS=no
endif

ifeq ($(BR2_PACKAGE_C_ARES),y)
MOSQUITTO_DEPENDENCIES += c-ares
MOSQUITTO_MAKE_OPTS += WITH_SRV=yes
else
MOSQUITTO_MAKE_OPTS += WITH_SRV=no
endif

ifeq ($(BR2_PACKAGE_LIBWEBSOCKETS),y)
MOSQUITTO_DEPENDENCIES += libwebsockets
MOSQUITTO_MAKE_OPTS += WITH_WEBSOCKETS=yes
else
MOSQUITTO_MAKE_OPTS += WITH_WEBSOCKETS=no
endif

# C++ support is only used to create a wrapper library
# Disable it
define MOSQUITTO_DISABLE_CPP
	$(SED) '/-C cpp/d' $(@D)/lib/Makefile
endef
MOSQUITTO_POST_PATCH_HOOKS += MOSQUITTO_DISABLE_CPP

MOSQUITTO_MAKE_DIRS = lib client
ifeq ($(BR2_PACKAGE_MOSQUITTO_BROKER),y)
MOSQUITTO_MAKE_DIRS += src
endif

define MOSQUITTO_BUILD_CMDS
	$(MAKE) -C $(@D) $(TARGET_CONFIGURE_OPTS) DIRS="$(MOSQUITTO_MAKE_DIRS)" \
		$(MOSQUITTO_MAKE_OPTS)
endef

define MOSQUITTO_INSTALL_STAGING_CMDS
	$(MAKE) -C $(@D) $(TARGET_CONFIGURE_OPTS) DIRS="$(MOSQUITTO_MAKE_DIRS)" \
		$(MOSQUITTO_MAKE_OPTS) DESTDIR=$(STAGING_DIR) install
endef

ifeq ($(BR2_PACKAGE_MOSQUITTO_BROKER),y)
define MOSQUITTO_INSTALL_BROKER
	mkdir -p $(TARGET_DIR)/stat/etc/mosquitto
	$(INSTALL) -D -m 0644 $(@D)/mosquitto.conf $(TARGET_DIR)/stat/etc/mosquitto/mosquitto.conf.example
	ln -s /tmp/etc/mosquitto $(TARGET_DIR)/etc/mosquitto
	$(INSTALL) -D -m 0755 package/mosquitto/mosquitto.init $(TARGET_DIR)/etc/init.d/mosquitto
	$(INSTALL) -D -m 0644 package/mosquitto/mosquitto.logrotate $(TARGET_DIR)/etc/logrotate.d/mosquitto
	ln -sf ../../init.d/mosquitto $(TARGET_DIR)/etc/runlevels/default/S58mosquitto
	ln -sf ../../init.d/mosquitto $(TARGET_DIR)/etc/runlevels/default/K02mosquitto
endef
endif

define MOSQUITTO_INSTALL_TARGET_CMDS
	$(MAKE) -C $(@D) $(TARGET_CONFIGURE_OPTS) DIRS="$(MOSQUITTO_MAKE_DIRS)" \
		$(MOSQUITTO_MAKE_OPTS) DESTDIR=$(TARGET_DIR) install
	rm -rf $(TARGET_DIR)/etc/mosquitto
	$(MOSQUITTO_INSTALL_BROKER)
endef

define MOSQUITTO_UNINSTALL_TARGET_CMDS
	## Uninstall client ##
	rm -f $(TARGET_DIR)/usr/bin/mosquitto_pub
	rm -f $(TARGET_DIR)/usr/bin/mosquitto_sub
	rm -f $(TARGET_DIR)/usr/bin/mosquitto_rr
	rm -f $(TARGET_DIR)/usr/lib/libmosquitto.so*
	## Uninstall broker ##
	rm -f $(TARGET_DIR)/usr/sbin/mosquitto
	rm -f $(TARGET_DIR)/usr/bin/mosquitto_passwd
	rm -rf $(TARGET_DIR)/stat/etc/mosquitto
	rm -f $(TARGET_DIR)/etc/mosquitto
	rm -f $(TARGET_DIR)/etc/init.d/mosquitto
	rm -f $(TARGET_DIR)/etc/runlevels/default/S58mosquitto
	rm -f $(TARGET_DIR)/etc/runlevels/default/K02mosquitto
endef

$(eval $(call GENTARGETS,package,mosquitto))
