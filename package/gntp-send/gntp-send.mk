#############################################################
#
# gntp-send
#
#############################################################

GNTP_SEND_VERSION = 0.3.4
GNTP_SEND_SOURCE = gntp-send-$(GNTP_SEND_VERSION).tar.gz
GNTP_SEND_SITE = https://github.com/mattn/gntp-send/releases/download/$(GNTP_SEND_VERSION)

GNTP_SEND_INSTALL_STAGING = YES

define GNTP_SEND_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/lib/libgrowl.so*
	rm -f $(TARGET_DIR)/usr/bin/gntp-send
endef

$(eval $(call AUTOTARGETS,package,gntp-send))
