#############################################################
#
# mdnsresponder
#
##############################################################
MDNSRESPONDER_VERSION := 107.6
MDNSRESPONDER_SOURCE := mDNSResponder-$(MDNSRESPONDER_VERSION).tar.gz
#MDNSRESPONDER_SITE := http://www.opensource.apple.com/tarballs/mDNSResponder
MDNSRESPONDER_SITE := https://astlinux-project.org/files
MDNSRESPONDER_DIR := $(BUILD_DIR)/mDNSResponder-$(MDNSRESPONDER_VERSION)
MDNSRESPONDER_BINARY := mDNSPosix/build/prod/mDNSProxyResponderPosix
MDNSRESPONDER_TARGET_BINARY := usr/sbin/mDNSProxyResponderPosix

$(DL_DIR)/$(MDNSRESPONDER_SOURCE):
	$(WGET) -P $(DL_DIR) $(MDNSRESPONDER_SITE)/$(MDNSRESPONDER_SOURCE)

$(MDNSRESPONDER_DIR)/.source: $(DL_DIR)/$(MDNSRESPONDER_SOURCE)
	zcat $(DL_DIR)/$(MDNSRESPONDER_SOURCE) | tar -C $(BUILD_DIR) $(TAR_OPTIONS) -
	toolchain/patch-kernel.sh $(MDNSRESPONDER_DIR) package/mdnsresponder/ mdnsresponder\*.patch
	touch $@

$(MDNSRESPONDER_DIR)/.configured: $(MDNSRESPONDER_DIR)/.source
	touch $@

$(MDNSRESPONDER_DIR)/$(MDNSRESPONDER_BINARY): $(MDNSRESPONDER_DIR)/.configured
	$(MAKE) CC=$(TARGET_CC) os="linux" LD="$(TARGET_CC) -shared" LOCALBASE="/usr" -C $(MDNSRESPONDER_DIR)/mDNSPosix

$(TARGET_DIR)/$(MDNSRESPONDER_TARGET_BINARY): $(MDNSRESPONDER_DIR)/$(MDNSRESPONDER_BINARY)
	$(INSTALL) -s -D -m 0755 $(MDNSRESPONDER_DIR)/$(MDNSRESPONDER_BINARY) $(TARGET_DIR)/$(MDNSRESPONDER_TARGET_BINARY)
	$(INSTALL) -D -m 0755 package/mdnsresponder/mdns.init $(TARGET_DIR)/etc/init.d/mdns
	ln -sf ../../init.d/mdns $(TARGET_DIR)/etc/runlevels/default/S92mdns
	ln -sf ../../init.d/mdns $(TARGET_DIR)/etc/runlevels/default/K05mdns

mdnsresponder: $(TARGET_DIR)/$(MDNSRESPONDER_TARGET_BINARY)

mdnsresponder-source: $(MDNSRESPONDER_DIR)/.source

mdnsresponder-clean:
	rm -f $(TARGET_DIR)/$(MDNSRESPONDER_TARGET_BINARY)
	rm -f $(TARGET_DIR)/etc/init.d/mdns
	rm -f $(TARGET_DIR)/etc/runlevels/default/S92mdns
	rm -f $(TARGET_DIR)/etc/runlevels/default/K05mdns
	-$(MAKE) -C $(MDNSRESPONDER_DIR)/mDNSPosix os=linux clean

mdnsresponder-dirclean:
	rm -rf $(MDNSRESPONDER_DIR)

#############################################################
#
# Toplevel Makefile options
#
#############################################################
ifeq ($(strip $(BR2_PACKAGE_MDNSRESPONDER)),y)
TARGETS+=mdnsresponder
endif
