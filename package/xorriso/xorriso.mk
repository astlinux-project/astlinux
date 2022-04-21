#############################################################
#
# xorriso - HOST-Only
#
# Used by: ./scripts/build-runnix-iso
#
#############################################################

XORRISO_VERSION = 1.5.4.pl02
XORRISO_SOURCE = xorriso-$(XORRISO_VERSION).tar.gz
XORRISO_SITE = https://www.gnu.org/software/xorriso

# Disable everything until we actually need those features, and add the correct
# host libraries
HOST_XORRISO_CONF_OPT = \
	--enable-zlib \
	--disable-xattr-h-pref-attr \
	--disable-libbz2 \
	--disable-libcdio \
	--disable-libreadline \
	--disable-libedit \
	--disable-libacl

HOST_XORRISO_DEPENDENCIES = host-zlib

$(eval $(call AUTOTARGETS,package,xorriso,host))
