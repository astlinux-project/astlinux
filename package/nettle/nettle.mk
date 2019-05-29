################################################################################
#
# nettle
#
################################################################################

NETTLE_VERSION = 3.4.1
NETTLE_SITE = http://www.lysator.liu.se/~nisse/archive
NETTLE_DEPENDENCIES = gmp
NETTLE_INSTALL_STAGING = YES
# don't include openssl support for (unused) examples as it has problems
# with static linking
NETTLE_CONF_OPT = --disable-openssl
NETTLE_CFLAGS += -std=c99
NETTLE_CONF_ENV = CFLAGS="$(TARGET_CFLAGS) $(NETTLE_CFLAGS)"

$(eval $(call AUTOTARGETS,package,nettle))
