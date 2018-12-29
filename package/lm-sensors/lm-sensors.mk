#############################################################
#
# lm-sensors
#
#############################################################
LM_SENSORS_VERSION = 3.5.0
LM_SENSORS_SOURCE = lm_sensors-$(LM_SENSORS_VERSION).tar.gz
LM_SENSORS_SITE = https://s3.amazonaws.com/files.astlinux-project
LM_SENSORS_INSTALL_STAGING = YES
LM_SENSORS_DEPENDENCIES = host-bison host-flex

##
## curl -L -o dl/lm_sensors-3.5.0.tar.gz https://github.com/lm-sensors/lm-sensors/archive/V3-5-0.tar.gz
## ./scripts/upload-dl-pair dl/lm_sensors-3.5.0.tar.gz
##

LM_SENSORS_BINS_ = bin/sensors-conf-convert
LM_SENSORS_BINS_$(BR2_PACKAGE_LM_SENSORS_SENSORS) += bin/sensors
LM_SENSORS_BINS_$(BR2_PACKAGE_LM_SENSORS_FANCONTROL) += sbin/fancontrol
LM_SENSORS_BINS_$(BR2_PACKAGE_LM_SENSORS_ISADUMP) += sbin/isadump
LM_SENSORS_BINS_$(BR2_PACKAGE_LM_SENSORS_ISASET) += sbin/isaset
LM_SENSORS_BINS_$(BR2_PACKAGE_LM_SENSORS_PWMCONFIG) += sbin/pwmconfig
LM_SENSORS_BINS_$(BR2_PACKAGE_LM_SENSORS_SENSORS_DETECT) += sbin/sensors-detect

define LM_SENSORS_BUILD_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) $(TARGET_CONFIGURE_OPTS) MACHINE=$(KERNEL_ARCH) \
		PREFIX=/usr -C $(@D)
endef

define LM_SENSORS_INSTALL_STAGING_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) -C $(@D) PREFIX=/usr DESTDIR=$(STAGING_DIR) install
	rm -f $(addprefix $(STAGING_DIR)/usr/,$(LM_SENSORS_BINS_) $(LM_SENSORS_BINS_y))
endef

define LM_SENSORS_UNINSTALL_STAGING_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) -C $(@D) PREFIX=/usr DESTDIR=$(STAGING_DIR) uninstall
endef

define LM_SENSORS_INSTALL_TARGET_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) -C $(@D) PREFIX=/usr DESTDIR=$(TARGET_DIR) install
	$(INSTALL) -D -m 0755 package/lm-sensors/lm-sensors.init $(TARGET_DIR)/etc/init.d/lmsensors
	mv $(TARGET_DIR)/etc/sensors3.conf $(TARGET_DIR)/stat/etc/sensors.conf.default
	ln -sf /tmp/etc/sensors3.conf $(TARGET_DIR)/etc/sensors3.conf
	rm -f $(addprefix $(TARGET_DIR)/usr/,$(LM_SENSORS_BINS_))
	rm -rf $(TARGET_DIR)/etc/sensors.d
endef

define LM_SENSORS_UNINSTALL_TARGET_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) -C $(@D) PREFIX=/usr DESTDIR=$(TARGET_DIR) uninstall
endef

define LM_SENSORS_CLEAN_CMDS
	-$(MAKE) -C $(@D) clean
endef

$(eval $(call GENTARGETS,package,lm-sensors))
