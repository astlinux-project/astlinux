################################################################################
#
# jansson
#
################################################################################

JANSSON_VERSION = 2.9
JANSSON_SITE = http://www.digip.org/jansson/releases
JANSSON_INSTALL_STAGING = YES

JANSSON_CONF_ENV = LIBS="-lm"

$(eval $(call AUTOTARGETS,package,jansson))
