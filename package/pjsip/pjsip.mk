################################################################################
#
# pjsip
#
################################################################################

PJSIP_VERSION = 2.3
PJSIP_SOURCE = pjproject-$(PJSIP_VERSION).tar.bz2
PJSIP_SITE = http://www.pjsip.org/release/$(PJSIP_VERSION)
PJSIP_INSTALL_STAGING = YES

PJSIP_DEPENDENCIES = libsrtp

PJSIP_CONF_ENV = \
	CFLAGS="$(TARGET_CFLAGS) -O2 -DPJ_HAS_IPV6=1 -DNDEBUG" \
	LDFLAGS="-L$(STAGING_DIR)/usr/lib" \
	LD="$(TARGET_CC)"

PJSIP_CONF_OPT = \
	--with-external-srtp \
	--disable-sound \
	--disable-resample \
	--disable-video \
	--disable-opencore-amr

ifeq ($(BR2_PACKAGE_OPENSSL),y)
PJSIP_CONF_OPT += --with-ssl=$(STAGING_DIR)/usr
PJSIP_DEPENDENCIES += openssl
else
PJSIP_CONF_OPT += --disable-ssl
endif

$(eval $(call AUTOTARGETS,package,pjsip))
