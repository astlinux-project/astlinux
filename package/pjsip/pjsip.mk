################################################################################
#
# pjsip
#
################################################################################

PJSIP_VERSION = 2.12.1
PJSIP_SOURCE = pjproject-$(PJSIP_VERSION).tar.bz2
PJSIP_SITE = https://raw.githubusercontent.com/asterisk/third-party/master/pjproject/$(PJSIP_VERSION)

PJSIP_INSTALL_STAGING = YES

define PJSIP_CUSTOM_CONFIG
	cp package/pjsip/asterisk-config_site.h $(@D)/pjlib/include/pj/config_site.h
endef
PJSIP_POST_PATCH_HOOKS += PJSIP_CUSTOM_CONFIG

PJSIP_CONF_ENV = \
	CFLAGS="$(TARGET_CFLAGS) -O2 -Wno-unused-but-set-variable -Wno-unused-variable -Wno-unused-label -Wno-unused-function -Wno-strict-aliasing" \
	LDFLAGS="-L$(STAGING_DIR)/usr/lib" \
	LD="$(TARGET_CC)"

PJSIP_CONF_OPT = \
	--disable-speex-codec \
	--disable-speex-aec \
	--disable-bcg729 \
	--disable-gsm-codec \
	--disable-ilbc-codec \
	--disable-l16-codec \
	--disable-g711-codec \
	--disable-g722-codec \
	--disable-g7221-codec \
	--disable-opencore-amr \
	--disable-silk \
	--disable-opus \
	--disable-video \
	--disable-v4l2 \
	--disable-sound \
	--disable-ext-sound \
	--disable-sdl \
	--disable-libyuv \
	--disable-libwebrtc \
	--disable-resample \
	--disable-ffmpeg \
	--disable-openh264 \
	--disable-ipp \
	--without-external-pa \
	--without-external-srtp

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
