################################################################################
#
# pjsip-ast20
#
################################################################################

PJSIP_AST20_VERSION = 2.15.1
PJSIP_AST20_SOURCE = pjproject-$(PJSIP_AST20_VERSION).tar.gz
PJSIP_AST20_SITE = https://github.com/pjsip/pjproject/archive/$(PJSIP_AST20_VERSION)

PJSIP_AST20_INSTALL_STAGING = YES

define PJSIP_AST20_CUSTOM_CONFIG
	cp package/pjsip-ast20/asterisk-config_site.h $(@D)/pjlib/include/pj/config_site.h
endef
PJSIP_AST20_POST_PATCH_HOOKS += PJSIP_AST20_CUSTOM_CONFIG

PJSIP_AST20_CONF_ENV = \
	CFLAGS="$(TARGET_CFLAGS) -O2 -Wno-unused-but-set-variable -Wno-unused-variable -Wno-unused-label -Wno-unused-function -Wno-strict-aliasing" \
	LDFLAGS="-L$(STAGING_DIR)/usr/lib" \
	LD="$(TARGET_CC)"

PJSIP_AST20_CONF_OPT = \
	--enable-epoll \
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
	--disable-resample \
	--disable-ffmpeg \
	--disable-openh264 \
	--disable-ipp \
	--disable-libwebrtc \
	--disable-libsrtp \
	--disable-upnp \
	--without-external-pa \
	--without-external-srtp

ifeq ($(BR2_PACKAGE_OPENSSL),y)
PJSIP_AST20_CONF_OPT += --with-ssl=$(STAGING_DIR)/usr
PJSIP_AST20_DEPENDENCIES += openssl
else
PJSIP_AST20_CONF_OPT += --disable-ssl
endif

ifeq ($(BR2_PACKAGE_UTIL_LINUX_LIBUUID),y)
PJSIP_AST20_DEPENDENCIES += util-linux
endif

# disable build of test binaries
PJSIP_AST20_MAKE_OPT = lib

$(eval $(call AUTOTARGETS,package,pjsip-ast20))
