################################################################################
#
# liburiparser
#
################################################################################

LIBURIPARSER_VERSION = 0.8.1
LIBURIPARSER_SOURCE = uriparser-$(LIBURIPARSER_VERSION).tar.bz2
LIBURIPARSER_SITE = http://sourceforge.net/projects/uriparser/files/Sources/$(LIBURIPARSER_VERSION)
LIBURIPARSER_INSTALL_STAGING = YES

LIBURIPARSER_CONF_OPT = \
		--disable-test \
		--disable-doc

ifeq ($(BR2_USE_WCHAR),)
LIBURIPARSER_CONF_OPT += --disable-wchar_t
endif

$(eval $(call AUTOTARGETS,package,liburiparser))
