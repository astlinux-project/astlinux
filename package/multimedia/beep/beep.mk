#############################################################
#
# beep
#
#############################################################

BEEP_VERSION = 1.4.12
BEEP_SOURCE:=beep-$(BEEP_VERSION).tar.gz
BEEP_SITE = https://github.com/spkr-beep/beep/archive/v$(BEEP_VERSION)

define BEEP_CONFIGURE_CMDS
        @echo "No configure"
endef

BEEP_MAKE_OPT = CC='$(TARGET_CC)' LD='$(TARGET_LD)' CFLAGS='$(TARGET_CFLAGS)'

define BEEP_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/beep $(TARGET_DIR)/usr/bin/beep
endef

define BEEP_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/beep
endef

$(eval $(call AUTOTARGETS,package/multimedia,beep))
