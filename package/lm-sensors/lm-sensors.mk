#############################################################
#
# lm-sensors
#
#############################################################

LM_SENSORS_VERSION = 3-6-0
LM_SENSORS_SOURCE = lm-sensors-$(LM_SENSORS_VERSION).tar.gz
LM_SENSORS_SITE = https://github.com/lm-sensors/lm-sensors/archive/V$(LM_SENSORS_VERSION)
LM_SENSORS_INSTALL_STAGING = YES
LM_SENSORS_DEPENDENCIES = host-bison host-flex

LM_SENSORS_BINS_ = bin/sensors-conf-convert
LM_SENSORS_BINS_$(BR2_PACKAGE_LM_SENSORS_SENSORS) += bin/sensors
LM_SENSORS_BINS_$(BR2_PACKAGE_LM_SENSORS_FANCONTROL) += sbin/fancontrol
LM_SENSORS_BINS_$(BR2_PACKAGE_LM_SENSORS_ISADUMP) += sbin/isadump
LM_SENSORS_BINS_$(BR2_PACKAGE_LM_SENSORS_ISASET) += sbin/isaset
LM_SENSORS_BINS_$(BR2_PACKAGE_LM_SENSORS_PWMCONFIG) += sbin/pwmconfig
LM_SENSORS_BINS_$(BR2_PACKAGE_LM_SENSORS_SENSORS_DETECT) += sbin/sensors-detect

LM_SENSORS_MAKE_OPTS = \
	$(TARGET_CONFIGURE_OPTS) \
	PREFIX=/usr

define LM_SENSORS_BUILD_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) $(LM_SENSORS_MAKE_OPTS) -C $(@D)
endef

define LM_SENSORS_INSTALL_STAGING_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) -C $(@D) $(LM_SENSORS_MAKE_OPTS) DESTDIR=$(STAGING_DIR) install
	rm -f $(addprefix $(STAGING_DIR)/usr/,$(LM_SENSORS_BINS_) $(LM_SENSORS_BINS_y))
endef

define LM_SENSORS_UNINSTALL_STAGING_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) -C $(@D) $(LM_SENSORS_MAKE_OPTS) DESTDIR=$(STAGING_DIR) uninstall
endef

define LM_SENSORS_INSTALL_TARGET_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) -C $(@D) $(LM_SENSORS_MAKE_OPTS) DESTDIR=$(TARGET_DIR) install
	$(INSTALL) -D -m 0755 package/lm-sensors/lm-sensors.init $(TARGET_DIR)/etc/init.d/lmsensors
	mv $(TARGET_DIR)/etc/sensors3.conf $(TARGET_DIR)/stat/etc/sensors.conf.default
	ln -sf /tmp/etc/sensors3.conf $(TARGET_DIR)/etc/sensors3.conf
	rm -f $(addprefix $(TARGET_DIR)/usr/,$(LM_SENSORS_BINS_))
	rm -rf $(TARGET_DIR)/etc/sensors.d
endef

define LM_SENSORS_UNINSTALL_TARGET_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) -C $(@D) $(LM_SENSORS_MAKE_OPTS) DESTDIR=$(TARGET_DIR) uninstall
endef

define LM_SENSORS_CLEAN_CMDS
	-$(MAKE) -C $(@D) clean
endef

$(eval $(call GENTARGETS,package,lm-sensors))
