#############################################################
#
# stunnel
#
#############################################################

STUNNEL_VERSION = 4.44
STUNNEL_SITE = http://ftp.nluug.nl/pub/networking/stunnel/archive/4.x
STUNNEL_DEPENDENCIES = openssl

STUNNEL_CONF_OPT += \
	--with-ssl=$(STAGING_DIR)/usr \
	--localstatedir=/var \
	--disable-libwrap \
	--with-threads=fork

define STUNNEL_INSTALL_TARGET_CMDS
	ln -sf /tmp/etc/stunnel.conf $(TARGET_DIR)/etc/stunnel.conf
	$(INSTALL) -m 0755 -D package/stunnel/stunnel.init $(TARGET_DIR)/etc/init.d/stunnel
	$(INSTALL) -m 0755 -D $(@D)/src/stunnel $(TARGET_DIR)/usr/sbin/stunnel
endef

$(eval $(call AUTOTARGETS,package,stunnel))
