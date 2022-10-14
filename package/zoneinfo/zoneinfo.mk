#############################################################
#
# zoneinfo
#
##############################################################

ZONEINFO_VERSION := 2022e
ZONEINFO_DATA := tzdata$(ZONEINFO_VERSION).tar.gz
ZONEINFO_SOURCE := tzcode$(ZONEINFO_VERSION).tar.gz
ZONEINFO_SITE := https://www.iana.org/time-zones/repository/releases
ZONEINFO_DIR := $(BUILD_DIR)/zoneinfo
ZONEINFO_BINARY := usr/share/zoneinfo/.tzversion

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
	  $(MAKE1) TZDIR=$(TARGET_DIR)/usr/share/zoneinfo ZFLAGS="-b fat" posix_only \
	)
	$(INSTALL) -D -m 0644 $(ZONEINFO_DIR)/zone.tab $(TARGET_DIR)/usr/share/zoneinfo/zone.tab
	$(INSTALL) -D -m 0644 $(ZONEINFO_DIR)/iso3166.tab $(TARGET_DIR)/usr/share/zoneinfo/iso3166.tab
	echo "$(ZONEINFO_VERSION)" > $(TARGET_DIR)/$(ZONEINFO_BINARY)

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
