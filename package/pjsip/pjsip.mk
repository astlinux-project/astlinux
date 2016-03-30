################################################################################
#
# pjsip
#
################################################################################

PJSIP_VERSION = 2.4.5
PJSIP_SOURCE = pjproject-$(PJSIP_VERSION).tar.bz2
PJSIP_SITE = http://www.pjsip.org/release/$(PJSIP_VERSION)
PJSIP_INSTALL_STAGING = YES

PJSIP_DEPENDENCIES = libsrtp

define PJSIP_CUSTOM_CONFIG
	cp package/pjsip/asterisk-config_site.h $(@D)/pjlib/include/pj/config_site.h
endef
PJSIP_POST_PATCH_HOOKS += PJSIP_CUSTOM_CONFIG

PJSIP_CONF_ENV = \
	CFLAGS="$(TARGET_CFLAGS) -O2" \
	LDFLAGS="-L$(STAGING_DIR)/usr/lib" \
	LD="$(TARGET_CC)"

PJSIP_CONF_OPT = \
	--with-external-srtp \
	--disable-video \
	--disable-v4l2 \
	--disable-sound \
	--disable-opencore-amr \
	--disable-ilbc-codec \
	--disable-g7221-codec \
	--disable-resample \
	--without-libyuv

ifeq ($(BR2_PACKAGE_OPENSSL),y)
PJSIP_CONF_OPT += --with-ssl=$(STAGING_DIR)/usr
PJSIP_DEPENDENCIES += openssl
else
PJSIP_CONF_OPT += --disable-ssl
endif

ifeq ($(BR2_PACKAGE_UTIL_LINUX_LIBUUID),y)
PJSIP_DEPENDENCIES += util-linux
endif

$(eval $(call AUTOTARGETS,package,pjsip))
