################################################################################
#
# libqrencode
#
################################################################################

LIBQRENCODE_VERSION = 4.0.2
LIBQRENCODE_SOURCE = qrencode-$(LIBQRENCODE_VERSION).tar.gz
LIBQRENCODE_SITE = https://fukuchi.org/works/qrencode
LIBQRENCODE_DEPENDENCIES = host-pkg-config
LIBQRENCODE_INSTALL_STAGING = YES

ifeq ($(BR2_TOOLCHAIN_HAS_THREADS),y)
LIBQRENCODE_CONF_ENV += LIBS='-pthread'
else
LIBQRENCODE_CONF_OPT += --disable-thread-safety
endif

ifeq ($(BR2_PACKAGE_LIBPNG),y)
LIBQRENCODE_CONF_OPT += --with-png
LIBQRENCODE_DEPENDENCIES += libpng
else
LIBQRENCODE_CONF_OPT += --without-png
endif

ifeq ($(BR2_PACKAGE_LIBQRENCODE_TOOLS),y)
LIBQRENCODE_CONF_OPT += --with-tools=yes
else
LIBQRENCODE_CONF_OPT += --with-tools=no
endif

$(eval $(call AUTOTARGETS,package,libqrencode))
