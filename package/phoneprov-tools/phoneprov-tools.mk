#############################################################
#
# phoneprov-tools
#
#############################################################

# source included in package
PHONEPROV_TOOLS_SOURCE =

define PHONEPROV_TOOLS_BUILD_CMDS
	# No build needed
endef

define PHONEPROV_TOOLS_INSTALL_TARGET_CMDS
	install -D -m 755 package/phoneprov-tools/phoneprov-build $(TARGET_DIR)/usr/sbin/
	install -D -m 755 package/phoneprov-tools/phoneprov-massdeployment $(TARGET_DIR)/usr/sbin/
	install -D -m 644 package/phoneprov-tools/massdeployment.conf $(TARGET_DIR)/stat/etc/phoneprov/massdeployment.conf
	rsync -a --exclude=".svn" package/phoneprov-tools/templates/ $(TARGET_DIR)/stat/etc/phoneprov/templates/
endef

define PHONEPROV_TOOLS_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/phoneprov-build
	rm -f $(TARGET_DIR)/usr/sbin/phoneprov-massdeployment
	rm -rf $(TARGET_DIR)/stat/etc/phoneprov
endef

$(eval $(call GENTARGETS,package,phoneprov-tools))
