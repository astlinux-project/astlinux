#############################################################
#
# fonulator
#
#############################################################
FONULATOR_VERSION:=2.0.3
FONULATOR_SITE:=http://support.red-fone.com/downloads/fonulator
FONULATOR_SOURCE:=fonulator-$(FONULATOR_VERSION).tar.gz

FONULATOR_DEPENDENCIES = libargtable2 libfb

FONULATOR_CONF_ENV = \
	ac_cv_func_malloc_0_nonnull=yes \
	ac_cv_file__usr_lib_libargtable2_a=yes

FONULATOR_CONF_OPT = \
	--with-shared-libfb

define FONULATOR_INSTALL_TARGET_CMDS
	$(INSTALL) -D $(@D)/fonulator $(TARGET_DIR)/usr/sbin/
	$(INSTALL) -D $(@D)/redfone_sample.conf $(TARGET_DIR)/stat/etc/redfone.conf.sample
	$(INSTALL) -D -m 0755 package/fonulator/fonulator.init $(TARGET_DIR)/etc/init.d/fonulator
#	$(INSTALL) -D -m 0755 package/fonulator/setup-redfone.sh $(TARGET_DIR)/usr/sbin/setup-redfone
	ln -sf /tmp/etc/redfone.conf $(TARGET_DIR)/etc/redfone.conf
	ln -sf /tmp/etc/redfone2.conf $(TARGET_DIR)/etc/redfone2.conf
	ln -sf ../../init.d/fonulator $(TARGET_DIR)/etc/runlevels/default/S15fonulator
	ln -sf ../../init.d/fonulator $(TARGET_DIR)/etc/runlevels/default/K70fonulator
endef

define FONULATOR_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/fonulator
	rm -f $(TARGET_DIR)/stat/etc/redfone.conf.sample
	rm -f $(TARGET_DIR)/etc/init.d/fonulator
#	rm -f $(TARGET_DIR)/usr/sbin/setup-redfone
	rm -f $(TARGET_DIR)/etc/redfone.conf
	rm -f $(TARGET_DIR)/etc/redfone2.conf
	rm -f $(TARGET_DIR)/etc/runlevels/default/S15fonulator
	rm -f $(TARGET_DIR)/etc/runlevels/default/K70fonulator
endef

$(eval $(call AUTOTARGETS,package,fonulator))
