#############################################################
#
# libmnl
#
#############################################################

LIBMNL_VERSION = 1.0.5
LIBMNL_SOURCE = libmnl-$(LIBMNL_VERSION).tar.bz2
LIBMNL_SITE = https://netfilter.org/projects/libmnl/files
LIBMNL_INSTALL_STAGING = YES

$(eval $(call AUTOTARGETS,package,libmnl))
