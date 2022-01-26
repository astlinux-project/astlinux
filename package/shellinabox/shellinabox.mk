#############################################################
#
# shellinabox
#
#############################################################
SHELLINABOX_VERSION = 2.20
SHELLINABOX_SOURCE = shellinabox-$(SHELLINABOX_VERSION).tar.gz
#SHELLINABOX_SITE = https://github.com/shellinabox/shellinabox
SHELLINABOX_SITE = https://astlinux-project.org/files
SHELLINABOX_DEPENDENCIES = zlib openssl

SHELLINABOX_AUTORECONF = YES

SHELLINABOX_CONF_OPT = \
	--enable-ssl \
	--disable-pam \
	--disable-runtime-loading

define SHELLINABOX_GREEN_ON_BLACK
	cp $(@D)/shellinabox/white-on-black.css $(@D)/shellinabox/green-on-black.css
	$(SED) 's/white/green/g' \
	    -e 's/#ffffff/#00ff00/g' \
		$(@D)/shellinabox/green-on-black.css
endef
SHELLINABOX_POST_BUILD_HOOKS = SHELLINABOX_GREEN_ON_BLACK

define SHELLINABOX_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 0755 $(@D)/shellinaboxd $(TARGET_DIR)/usr/bin/shellinaboxd
	$(INSTALL) -D -m 0755 package/shellinabox/shellinaboxd.init $(TARGET_DIR)/etc/init.d/shellinaboxd
	$(INSTALL) -D -m 0644 $(@D)/shellinabox/black-on-white.css $(TARGET_DIR)/usr/share/shellinabox/00xBlack_on_White.css
	$(INSTALL) -D -m 0644 $(@D)/shellinabox/white-on-black.css $(TARGET_DIR)/usr/share/shellinabox/01-White_on_Black.css
	$(INSTALL) -D -m 0644 $(@D)/shellinabox/green-on-black.css $(TARGET_DIR)/usr/share/shellinabox/02-Green_on_Black.css
endef

define SHELLINABOX_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/shellinaboxd
	rm -f $(TARGET_DIR)/etc/init.d/shellinaboxd
	rm -rf $(TARGET_DIR)/usr/share/shellinabox
endef

$(eval $(call AUTOTARGETS,package,shellinabox))
