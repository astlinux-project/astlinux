################################################################################
#
# libunistring
#
################################################################################

LIBUNISTRING_VERSION = 0.9.10
LIBUNISTRING_SITE = $(BR2_GNU_MIRROR)/libunistring
LIBUNISTRING_SOURCE = libunistring-$(LIBUNISTRING_VERSION).tar.xz
LIBUNISTRING_INSTALL_STAGING = YES

ifeq ($(BR2_TOOLCHAIN_HAS_THREADS),y)
LIBUNISTRING_CONF_OPT += --enable-threads=posix
else
LIBUNISTRING_CONF_OPT += --disable-threads
endif

$(eval $(call AUTOTARGETS,package,libunistring))
$(eval $(call AUTOTARGETS,package,libunistring,host))
