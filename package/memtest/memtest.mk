#############################################################
#
# memtest86
#
#############################################################
#
MEMTEST_VER:=4.20
MEMTEST_SITE:=http://www.memtest.org/download
MEMTEST_SOURCE:=$(MEMTEST_VER)/memtest86+-$(MEMTEST_VER).tar.gz
MEMTEST_DIR:=$(BUILD_DIR)/memtest86+-$(MEMTEST_VER)
MEMTEST_CAT:=zcat
MEMTEST_BIN:=memtest.bin

$(DL_DIR)/$(notdir $(MEMTEST_SOURCE)):
	 $(WGET) -P $(DL_DIR) $(MEMTEST_SITE)/$(MEMTEST_SOURCE)

$(MEMTEST_DIR)/.unpacked: $(DL_DIR)/$(notdir $(MEMTEST_SOURCE))
	mkdir -p $(MEMTEST_DIR)
	$(MEMTEST_CAT) $(DL_DIR)/$(notdir $(MEMTEST_SOURCE)) | tar -C $(BUILD_DIR) $(TAR_OPTIONS) -
	touch $@

$(MEMTEST_DIR)/.configured: $(MEMTEST_DIR)/.unpacked
	touch $@

$(MEMTEST_DIR)/$(MEMTEST_BIN): $(MEMTEST_DIR)/.configured
	$(TARGET_MAKE_ENV) $(MAKE1) -C $(MEMTEST_DIR) CC=$(TARGET_CC) memtest.bin

memtest: $(MEMTEST_DIR)/$(MEMTEST_BIN)

memtest-source: $(MEMTEST_DIR)/.unpacked

memtest-dirclean:
	rm -rf $(MEMTEST_DIR)

#############################################################
#
# Toplevel Makefile options
#
#############################################################
ifeq ($(strip $(BR2_PACKAGE_MEMTEST)),y)
TARGETS+=memtest
endif
