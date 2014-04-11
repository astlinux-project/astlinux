#############################################################
#
# stunnel
#
#############################################################

STUNNEL_VERSION = 5.01
STUNNEL_SITE = http://ftp.nluug.nl/pub/networking/stunnel/archive/5.x
STUNNEL_DEPENDENCIES = openssl

# We're patching configure.ac
STUNNEL_AUTORECONF = YES

STUNNEL_CONF_OPT += \
	--with-ssl=$(STAGING_DIR)/usr \
	--localstatedir=/var \
	--disable-libwrap \
	--disable-fips \
	--with-threads=fork

define STUNNEL_INSTALL_TARGET_CMDS
	ln -snf /tmp/etc/stunnel $(TARGET_DIR)/etc/stunnel
	$(INSTALL) -m 0755 -D package/stunnel/stunnel.init $(TARGET_DIR)/etc/init.d/stunnel
	$(INSTALL) -m 0755 -D $(@D)/src/stunnel $(TARGET_DIR)/usr/sbin/stunnel
endef

$(eval $(call AUTOTARGETS,package,stunnel))
