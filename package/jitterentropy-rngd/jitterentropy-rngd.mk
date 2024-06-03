################################################################################
#
# jitterentropy-rngd
#
################################################################################

JITTERENTROPY_RNGD_VERSION = 1.2.8
JITTERENTROPY_RNGD_SOURCE = jitterentropy-rngd-$(JITTERENTROPY_RNGD_VERSION).tar.gz
JITTERENTROPY_RNGD_SITE = https://github.com/smuellerDD/jitterentropy-rngd/archive/v$(JITTERENTROPY_RNGD_VERSION)

define JITTERENTROPY_RNGD_BUILD_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) CC="$(TARGET_CC)" -C $(@D)
endef

define JITTERENTROPY_RNGD_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/jitterentropy-rngd $(TARGET_DIR)/usr/sbin/jitterentropy-rngd
	$(INSTALL) -m 0755 -D package/jitterentropy-rngd/jitterentropy-rngd.init $(TARGET_DIR)/etc/init.d/jitterentropy-rngd
	ln -sf ../../init.d/jitterentropy-rngd $(TARGET_DIR)/etc/runlevels/default/S00jitterentropy-rngd
	ln -sf ../../init.d/jitterentropy-rngd $(TARGET_DIR)/etc/runlevels/default/K00jitterentropy-rngd
endef

define JITTERENTROPY_RNGD_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/jitterentropy-rngd
	rm -f $(TARGET_DIR)/etc/init.d/jitterentropy-rngd
	rm -f $(TARGET_DIR)/etc/runlevels/default/S00jitterentropy-rngd
	rm -f $(TARGET_DIR)/etc/runlevels/default/K00jitterentropy-rngd
endef

$(eval $(call GENTARGETS,package,jitterentropy-rngd))
