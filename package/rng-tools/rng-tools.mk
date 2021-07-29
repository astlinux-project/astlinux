#############################################################
#
# rng-tools
#
#############################################################

RNG_TOOLS_VERSION = 6.5
RNG_TOOLS_SOURCE = rng-tools-$(RNG_TOOLS_VERSION).tar.gz
RNG_TOOLS_SITE = https://s3.amazonaws.com/files.astlinux-project
RNG_TOOLS_DEPENDENCIES = host-pkg-config libsysfs

RNG_TOOLS_JITTERENTROPY_VERSION = 3.1.0
RNG_TOOLS_JITTERENTROPY_SOURCE = rng-tools-jitterentropy-$(RNG_TOOLS_JITTERENTROPY_VERSION).tar.gz
RNG_TOOLS_JITTERENTROPY_SITE = https://s3.amazonaws.com/files.astlinux-project

##
## curl -L -o dl/rng-tools-6.5.tar.gz https://github.com/nhorman/rng-tools/archive/v6.5.tar.gz
## ./scripts/upload-dl-pair dl/rng-tools-6.5.tar.gz
## curl -L -o dl/rng-tools-jitterentropy-3.1.0.tar.gz https://github.com/smuellerDD/jitterentropy-library/archive/v3.1.0.tar.gz
## ./scripts/upload-dl-pair dl/rng-tools-jitterentropy-3.1.0.tar.gz
##

define RNG_TOOLS_JITTERENTROPY_DOWNLOAD
	$(call DOWNLOAD,$(RNG_TOOLS_JITTERENTROPY_SITE),$(RNG_TOOLS_JITTERENTROPY_SOURCE))
endef
RNG_TOOLS_POST_DOWNLOAD_HOOKS += RNG_TOOLS_JITTERENTROPY_DOWNLOAD

define RNG_TOOLS_JITTERENTROPY_EXTRACT
	$(INFLATE$(suffix $(RNG_TOOLS_JITTERENTROPY_SOURCE))) $(DL_DIR)/$(RNG_TOOLS_JITTERENTROPY_SOURCE) | \
	$(TAR) $(TAR_STRIP_COMPONENTS)=1 -C $(@D)/jitterentropy-library $(TAR_OPTIONS) -
endef
RNG_TOOLS_POST_EXTRACT_HOOKS += RNG_TOOLS_JITTERENTROPY_EXTRACT

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

define RNG_TOOLS_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/rngtest $(TARGET_DIR)/usr/bin/rngtest
	$(INSTALL) -m 0755 -D $(@D)/rngd $(TARGET_DIR)/usr/sbin/rngd
	$(INSTALL) -m 0755 -D package/rng-tools/rngd.init $(TARGET_DIR)/etc/init.d/rngd
	ln -sf ../../init.d/rngd $(TARGET_DIR)/etc/runlevels/default/S00rngd
	ln -sf ../../init.d/rngd $(TARGET_DIR)/etc/runlevels/default/K00rngd
endef

RNG_TOOLS_UNINSTALL_STAGING_OPT = --version

define RNG_TOOLS_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/rngtest
	rm -f $(TARGET_DIR)/usr/sbin/rngd
	rm -f $(TARGET_DIR)/etc/init.d/rngd
	rm -f $(TARGET_DIR)/etc/runlevels/default/S00rngd
	rm -f $(TARGET_DIR)/etc/runlevels/default/K00rngd
endef

$(eval $(call AUTOTARGETS,package,rng-tools))
