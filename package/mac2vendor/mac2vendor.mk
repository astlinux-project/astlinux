#############################################################
#
# mac2vendor
#
##############################################################
MAC2VENDOR_VERSION := 2014-11-12
MAC2VENDOR_DATA := oui-$(MAC2VENDOR_VERSION).txt
MAC2VENDOR_DATA_ORIG := oui.txt
MAC2VENDOR_SITE := http://standards.ieee.org/develop/regauth/oui
MAC2VENDOR_DIR := $(BUILD_DIR)/mac2vendor
MAC2VENDOR_BINARY := usr/sbin/mac2vendor

$(DL_DIR)/$(MAC2VENDOR_DATA):
	rm -f $(DL_DIR)/$(MAC2VENDOR_DATA_ORIG)
	$(WGET) -P $(DL_DIR) $(MAC2VENDOR_SITE)/$(MAC2VENDOR_DATA_ORIG)
	cp -a $(DL_DIR)/$(MAC2VENDOR_DATA_ORIG) $(DL_DIR)/$(MAC2VENDOR_DATA)

$(MAC2VENDOR_DIR)/.data: $(DL_DIR)/$(MAC2VENDOR_DATA)
	mkdir -p $(MAC2VENDOR_DIR)/oui-db
	for i in 0 1 2 3 4 5 6 7 8 9 A B C D E F; do \
	  sed 's/^ *//' $(DL_DIR)/$(MAC2VENDOR_DATA) | \
	  grep "^[0-9A-F]\{5\}$$i " | \
	  sed 's/ [^(]*.base 16.[^0-9a-zA-Z]*/~/' > $(MAC2VENDOR_DIR)/oui-db/xxxxx$$i ; \
	  chmod a-w $(MAC2VENDOR_DIR)/oui-db/xxxxx$$i ; \
	done
	touch $@

$(TARGET_DIR)/$(MAC2VENDOR_BINARY): $(MAC2VENDOR_DIR)/.data
	$(INSTALL) -D -m 0755 package/mac2vendor/mac2vendor $(TARGET_DIR)/$(MAC2VENDOR_BINARY)
	cp -a $(MAC2VENDOR_DIR)/oui-db $(TARGET_DIR)/usr/share/oui-db

mac2vendor: $(TARGET_DIR)/$(MAC2VENDOR_BINARY)

mac2vendor-clean:
	rm -f $(TARGET_DIR)/$(MAC2VENDOR_BINARY)
	rm -rf $(TARGET_DIR)/usr/share/oui-db

mac2vendor-dirclean:
	rm -rf $(MAC2VENDOR_DIR)

#############################################################
#
# Toplevel Makefile options
#
#############################################################
ifeq ($(strip $(BR2_PACKAGE_MAC2VENDOR)),y)
TARGETS+=mac2vendor
endif
