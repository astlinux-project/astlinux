#############################################################
#
# zoneinfo
#
##############################################################
ZONEINFO_VERSION := 
ZONEINFO_SOURCE := tzcode2011g.tar.gz
ZONEINFO_DATA := tzdata2011g.tar.gz
ZONEINFO_SITE := ftp://elsie.nci.nih.gov/pub
ZONEINFO_DIR := $(BUILD_DIR)/zoneinfo
ZONEINFO_BINARY := usr/share/zoneinfo/.tzcompiled

$(DL_DIR)/$(ZONEINFO_SOURCE):
	$(WGET) -P $(DL_DIR) $(ZONEINFO_SITE)/$(ZONEINFO_SOURCE)

$(DL_DIR)/$(ZONEINFO_DATA):
	$(WGET) -P $(DL_DIR) $(ZONEINFO_SITE)/$(ZONEINFO_DATA)

$(ZONEINFO_DIR)/.source: $(DL_DIR)/$(ZONEINFO_SOURCE) $(DL_DIR)/$(ZONEINFO_DATA)
	mkdir $(ZONEINFO_DIR)
	zcat $(DL_DIR)/$(ZONEINFO_SOURCE) | tar -C $(ZONEINFO_DIR) $(TAR_OPTIONS) -
	zcat $(DL_DIR)/$(ZONEINFO_DATA) | tar -C $(ZONEINFO_DIR) $(TAR_OPTIONS) -
	touch $@

$(TARGET_DIR)/$(ZONEINFO_BINARY): $(ZONEINFO_DIR)/.source
	(cd $(ZONEINFO_DIR); \
	  $(MAKE1) TZDIR=$(TARGET_DIR)/usr/share/zoneinfo posix_only \
	)
	touch $@

zoneinfo: $(TARGET_DIR)/$(ZONEINFO_BINARY)

zoneinfo-source: $(DL_DIR)/$(ZONEINFO_SOURCE) $(DL_DIR)/$(ZONEINFO_DATA)

zoneinfo-clean:
	rm -rf $(TARGET_DIR)/usr/share/zoneinfo

zoneinfo-dirclean:
	rm -rf $(ZONEINFO_DIR)

#############################################################
#
# Toplevel Makefile options
#
#############################################################
ifeq ($(strip $(BR2_PACKAGE_ZONEINFO)),y)
TARGETS+=zoneinfo
endif
