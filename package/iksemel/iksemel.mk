#############################################################
#
# iksemel
#
#############################################################
IKSEMEL_VERSION = 1.5-pre1
IKSEMEL_SOURCE = iksemel-$(IKSEMEL_VERSION).tar.gz
#IKSEMEL_SITE = http://iksemel.googlecode.com/files
IKSEMEL_SITE = https://s3.amazonaws.com/files.astlinux-project
IKSEMEL_DEPENDENCIES = openssl

IKSEMEL_INSTALL_STAGING = YES

IKSEMEL_CONF_OPT = \
	--disable-python

define IKSEMEL_CONFIGURE_PREFLIGHT
	( cd $(@D) ; \
	  $(HOST_CONFIGURE_OPTS) \
	  ./autogen.sh ; \
	)
endef
IKSEMEL_PRE_CONFIGURE_HOOKS += IKSEMEL_CONFIGURE_PREFLIGHT

define IKSEMEL_INSTALL_TARGET_CMDS
        cp -a $(STAGING_DIR)/usr/lib/libiksemel.so* $(TARGET_DIR)/usr/lib/
endef

define IKSEMEL_UNINSTALL_TARGET_CMDS
        rm -f $(TARGET_DIR)/usr/lib/libiksemel.so*
endef

$(eval $(call AUTOTARGETS,package,iksemel))
