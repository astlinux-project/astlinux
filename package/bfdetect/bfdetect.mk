#############################################################
#
# bfdetect
#
##############################################################
BFDETECT_SOURCE:=bfdetect.tar.gz
BFDETECT_SITE:=http://www.beronet.com/download/berofix/tools/
BFDETECT_DIR := $(BUILD_DIR)/bfdetect
BFDETECT_CAT:=zcat
BFDETECT_BINARY := bfdetect
BFDETECT_TARGET_BINARY = usr/bin/$(BFDETECT_BINARY)

$(DL_DIR)/$(BFDETECT_SOURCE):
	 $(WGET) -P $(DL_DIR) $(BFDETECT_SITE)/$(BFDETECT_SOURCE)

$(BFDETECT_DIR)/.unpacked: $(DL_DIR)/$(BFDETECT_SOURCE)
	$(BFDETECT_CAT) $(DL_DIR)/$(BFDETECT_SOURCE) | tar -C $(BUILD_DIR) $(TAR_OPTIONS) -
	touch $(BFDETECT_DIR)/.unpacked
	
$(BFDETECT_DIR)/.configured: $(BFDETECT_DIR)/.unpacked
	touch $(BFDETECT_DIR)/.configured

$(BFDETECT_DIR)/$(BFDETECT_BINARY): $(BFDETECT_DIR)/.configured
	$(MAKE) CC=$(TARGET_CC) CFLAGS='$(TARGET_CFLAGS)' -C $(BFDETECT_DIR) 
	
$(TARGET_DIR)/$(BFDETECT_TARGET_BINARY): $(BFDETECT_DIR)/$(BFDETECT_BINARY)
	$(INSTALL) -D -m 0755 $(BFDETECT_DIR)/$(BFDETECT_BINARY) $(TARGET_DIR)/$(BFDETECT_TARGET_BINARY)
 
	
bfdetect: $(TARGET_DIR)/$(BFDETECT_TARGET_BINARY)

bfdetect-source: $(BFDETECT_DIR)/bfdetect.c

bfdetect-dirclean:
	rm -rf $(BFDETECT_DIR)

bfdetect-clean:
	rm -f $(TARGET_DIR)/$(BFDETECT_TARGET_BINARY)
	-$(MAKE) -C $(BFDETECT_DIR) clean

#############################################################
#
# Toplevel Makefile options
#
#############################################################
ifeq ($(strip $(BR2_PACKAGE_BFDETECT)),y)
TARGETS+=bfdetect
endif

