#############################################################
#
# system-vendor
#
#############################################################

# source included in package
SYSTEM_VENDOR_SOURCE =

define SYSTEM_VENDOR_BUILD_CMDS
	# No build needed
endef

define SYSTEM_VENDOR_INSTALL_TARGET_CMDS
	install -D -m 755 package/system-vendor/system-vendor.sh $(TARGET_DIR)/usr/sbin/system-vendor
	install -D -m 644 package/system-vendor/system-vendor.ids $(TARGET_DIR)/usr/share/system-vendor.ids
	ln -sf /tmp/etc/system-vendor $(TARGET_DIR)/etc/system-vendor
endef

define SYSTEM_VENDOR_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/system-vendor
	rm -f $(TARGET_DIR)/usr/share/system-vendor.ids
	rm -f $(TARGET_DIR)/etc/system-vendor
endef

$(eval $(call GENTARGETS,package,system-vendor))
