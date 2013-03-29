#############################################################
#
# fakeroot
#
#############################################################
FAKEROOT_VERSION = 1.18.2
FAKEROOT_SOURCE = fakeroot_$(FAKEROOT_VERSION).orig.tar.bz2
FAKEROOT_SITE = http://snapshot.debian.org/archive/debian/20111201T093630Z/pool/main/f/fakeroot
FAKEROOT_CONF_OPT = --program-prefix=''

# The package for the target cannot be selected (build problems when
# largefile is enabled), but is needed for the host package to work
# due to deficiencies in the package infrastructure.
$(eval $(call AUTOTARGETS,package,fakeroot))
$(eval $(call AUTOTARGETS,package,fakeroot,host))
