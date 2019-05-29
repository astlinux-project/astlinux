################################################################################
#
# pixman
#
################################################################################

PIXMAN_VERSION = 0.38.4
PIXMAN_SOURCE = pixman-$(PIXMAN_VERSION).tar.bz2
PIXMAN_SITE = http://xorg.freedesktop.org/releases/individual/lib

PIXMAN_INSTALL_STAGING = YES
PIXMAN_DEPENDENCIES = host-pkg-config

# For 0001-Disable-tests.patch
PIXMAN_AUTORECONF = YES

# don't build gtk based demos
PIXMAN_CONF_OPT = --disable-gtk

$(eval $(call AUTOTARGETS,package,pixman))
$(eval $(call AUTOTARGETS,package,pixman,host))
