#############################################################
#
# beep
#
#############################################################

BEEP_VERSION = 1.3
BEEP_SOURCE:=beep-$(BEEP_VERSION).tar.gz
BEEP_SITE = http://www.johnath.com/beep

define BEEP_CONFIGURE_CMDS
        @echo "No configure"
endef

BEEP_MAKE_OPT = CC='$(TARGET_CC)' LD='$(TARGET_LD)' -C $(@D) beep

define BEEP_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/beep $(TARGET_DIR)/usr/bin/beep
endef

define BEEP_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/beep
endef

$(eval $(call AUTOTARGETS,package/multimedia,beep))
