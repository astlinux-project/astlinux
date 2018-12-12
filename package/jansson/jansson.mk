################################################################################
#
# jansson
#
################################################################################

JANSSON_VERSION = 2.12
JANSSON_SOURCE = jansson-$(JANSSON_VERSION).tar.bz2
JANSSON_SITE = https://raw.githubusercontent.com/asterisk/third-party/master/jansson/$(JANSSON_VERSION)
#JANSSON_SITE = http://www.digip.org/jansson/releases

JANSSON_INSTALL_STAGING = YES

JANSSON_CONF_ENV = LIBS="-lm"

$(eval $(call AUTOTARGETS,package,jansson))
