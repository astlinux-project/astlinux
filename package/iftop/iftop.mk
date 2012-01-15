#############################################################
#
# iftop
#
#############################################################

IFTOP_VERSION = 1.0pre2
IFTOP_SOURCE:=iftop-$(IFTOP_VERSION).tar.gz
IFTOP_SITE = http://www.ex-parrot.com/~pdw/iftop/download
IFTOP_DEPENDENCIES = libpcap ncurses

IFTOP_MAKE_OPT = CC='$(TARGET_CC)' LD='$(TARGET_LD)' -C $(@D) iftop

IFTOP_CONF_OPT += \
	--prefix=/

define IFTOP_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/iftop $(TARGET_DIR)/usr/bin/iftop
endef

define IFTOP_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/iftop
endef

$(eval $(call AUTOTARGETS,package,iftop))
