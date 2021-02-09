################################################################################
#
# exfat
#
################################################################################

EXFAT_VERSION = 1.3.0
EXFAT_SITE = https://github.com/relan/exfat/releases/download/v$(EXFAT_VERSION)
EXFAT_SOURCE = fuse-exfat-$(EXFAT_VERSION).tar.gz
EXFAT_DEPENDENCIES = host-pkg-config libfuse

$(eval $(call AUTOTARGETS,package,exfat))
