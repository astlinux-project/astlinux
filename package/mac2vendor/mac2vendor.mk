################################################################################
#
# mac2vendor
#
################################################################################

MAC2VENDOR_VERSION = 2025-01-10
MAC2VENDOR_SOURCE = oui-$(MAC2VENDOR_VERSION).txt
MAC2VENDOR_SITE = https://astlinux-project.org/files

##
## curl -L -o dl/oui-2025-01-10.txt https://standards-oui.ieee.org/oui.txt
## ./scripts/upload-dl-pair dl/oui-2025-01-10.txt
##

define MAC2VENDOR_EXTRACT_CMDS
	mkdir -p $(@D)/oui-db
	for i in 0 1 2 3 4 5 6 7 8 9 A B C D E F; do \
	  sed -e 's/^ *//' -e 's/\r$$//' $(DL_DIR)/$(MAC2VENDOR_SOURCE) | \
	  grep "^[0-9A-F]\{5\}$$i " | \
	  sed 's/ [^(]*.base 16.[^0-9a-zA-Z]*/~/' | \
	  sed '/^......~$$/d' > $(@D)/oui-db/xxxxx$$i ; \
	done
endef

define MAC2VENDOR_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 0755 package/mac2vendor/mac2vendor $(TARGET_DIR)/usr/sbin/mac2vendor
	$(INSTALL) -d -m 0755 $(TARGET_DIR)/usr/share/oui-db
	cp $(@D)/oui-db/* $(TARGET_DIR)/usr/share/oui-db/
	chmod a-w $(TARGET_DIR)/usr/share/oui-db/*
endef

define MAC2VENDOR_UNINSTALL_TARGET_CMDS
	rm -f  $(TARGET_DIR)/usr/sbin/mac2vendor
	rm -rf $(TARGET_DIR)/usr/share/oui-db
endef

$(eval $(call GENTARGETS,package,mac2vendor))
