################################################################################
#
# libzip
#
################################################################################

LIBZIP_VERSION = 1.6.1
LIBZIP_SITE = https://libzip.org/download
LIBZIP_SOURCE = libzip-$(LIBZIP_VERSION).tar.xz
LIBZIP_INSTALL_STAGING = YES
LIBZIP_DEPENDENCIES = zlib

LIBZIP_CONF_OPT += -DENABLE_LZMA=OFF

ifeq ($(BR2_PACKAGE_BZIP2),y)
LIBZIP_DEPENDENCIES += bzip2
else
LIBZIP_CONF_OPT += -DENABLE_BZIP2=OFF
endif

ifeq ($(BR2_PACKAGE_OPENSSL),y)
LIBZIP_DEPENDENCIES += openssl
LIBZIP_CONF_OPT += -DENABLE_OPENSSL=ON
else
LIBZIP_CONF_OPT += -DENABLE_OPENSSL=OFF
endif

$(eval $(call CMAKETARGETS,package,libzip))
