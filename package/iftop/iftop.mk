#############################################################
#
# iftop
#
#############################################################

IFTOP_VERSION = 1.0pre4
IFTOP_SOURCE:=iftop-$(IFTOP_VERSION).tar.gz
IFTOP_SITE = http://www.ex-parrot.com/pdw/iftop/download
IFTOP_DEPENDENCIES = libpcap ncurses

define IFTOP_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 4711 $(@D)/iftop $(TARGET_DIR)/usr/sbin/
endef

define IFTOP_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/iftop
endef

$(eval $(call AUTOTARGETS,package,iftop))
