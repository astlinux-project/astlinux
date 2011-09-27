#############################################################
#
# stunnel
#
#############################################################

STUNNEL_VERSION = 4.36
STUNNEL_SITE = http://ftp.nluug.nl/pub/networking/stunnel/obsolete/4.x/
STUNNEL_DEPENDENCIES = openssl
STUNNEL_INSTALL_TARGET_OPT = DESTDIR=$(TARGET_DIR) --version

STUNNEL_CONF_OPT += \
	--with-ssl=$(STAGING_DIR)/usr \
	--with-threads=fork

define STUNNEL_INSTALL_CONF_SCRIPT
	ln -sf /tmp/etc/stunnel.conf $(TARGET_DIR)/etc/stunnel.conf
	$(INSTALL) -m 0755 -D package/stunnel/stunnel.init $(TARGET_DIR)/etc/init.d/stunnel
	$(INSTALL) -m 0755 -D $(@D)/src/stunnel $(TARGET_DIR)/usr/sbin/stunnel
endef

STUNNEL_POST_INSTALL_TARGET_HOOKS += STUNNEL_INSTALL_CONF_SCRIPT

$(eval $(call AUTOTARGETS,package,stunnel))
