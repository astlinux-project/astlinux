#############################################################
#
# rng-tools
#
#############################################################

RNG_TOOLS_VERSION = 6.3.1
RNG_TOOLS_SOURCE = rng-tools-$(RNG_TOOLS_VERSION).tar.gz
RNG_TOOLS_SITE = https://s3.amazonaws.com/files.astlinux-project
RNG_TOOLS_DEPENDENCIES = host-pkg-config libsysfs

##
## curl -L -o dl/rng-tools-6.3.1.tar.gz https://github.com/nhorman/rng-tools/archive/v6.3.1.tar.gz
## ./scripts/upload-dl-pair dl/rng-tools-6.3.1.tar.gz
##

define RNG_TOOLS_CONFIGURE_PREFLIGHT
	( cd $(@D) ; \
	  $(HOST_CONFIGURE_OPTS) \
	  ./autogen.sh ; \
	)
endef
RNG_TOOLS_PRE_CONFIGURE_HOOKS += RNG_TOOLS_CONFIGURE_PREFLIGHT

RNG_TOOLS_CONF_OPT = \
	--without-nistbeacon

ifeq ($(BR2_PACKAGE_LIBGCRYPT),y)
RNG_TOOLS_DEPENDENCIES += libgcrypt
else
RNG_TOOLS_CONF_OPT += --without-libgcrypt
endif

define RNG_TOOLS_POST_INSTALL
	$(INSTALL) -m 0755 -D package/rng-tools/rngd.init $(TARGET_DIR)/etc/init.d/rngd
	ln -sf ../../init.d/rngd $(TARGET_DIR)/etc/runlevels/default/S00rngd
	ln -sf ../../init.d/rngd $(TARGET_DIR)/etc/runlevels/default/K00rngd
endef
RNG_TOOLS_POST_INSTALL_TARGET_HOOKS = RNG_TOOLS_POST_INSTALL

define RNG_TOOLS_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/rngtest
	rm -f $(TARGET_DIR)/usr/sbin/rngd
	rm -f $(TARGET_DIR)/etc/init.d/rngd
	rm -f $(TARGET_DIR)/etc/runlevels/default/S00rngd
	rm -f $(TARGET_DIR)/etc/runlevels/default/K00rngd
endef

$(eval $(call AUTOTARGETS,package,rng-tools))
