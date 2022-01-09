#############################################################
#
# asterisk-codec-silk
#
##############################################################
ifeq ($(BR2_PACKAGE_ASTERISK_v13se),y)
ASTERISK_CODEC_SILK_VERSION := 13.0_1.0.3-x86_64
ASTERISK_CODEC_SILK_SITE := https://downloads.digium.com/pub/telephony/codec_silk/asterisk-13.0/x86-64
endif
ifeq ($(BR2_PACKAGE_ASTERISK_v16),y)
ASTERISK_CODEC_SILK_VERSION := 16.0_1.0.3-x86_64
ASTERISK_CODEC_SILK_SITE := https://downloads.digium.com/pub/telephony/codec_silk/asterisk-16.0/x86-64
endif
ifeq ($(BR2_PACKAGE_ASTERISK_v18),y)
ASTERISK_CODEC_SILK_VERSION := 18.0_1.0.3-x86_64
ASTERISK_CODEC_SILK_SITE := https://downloads.digium.com/pub/telephony/codec_silk/asterisk-18.0/x86-64
endif
ASTERISK_CODEC_SILK_SOURCE := codec_silk-$(ASTERISK_CODEC_SILK_VERSION).tar.gz
ASTERISK_CODEC_SILK_DIR := $(BUILD_DIR)/codec_silk-$(ASTERISK_CODEC_SILK_VERSION)
ASTERISK_CODEC_SILK_BINARY := usr/lib/asterisk/modules/codec_silk.so

$(DL_DIR)/$(ASTERISK_CODEC_SILK_SOURCE):
ifeq ($(ASTERISK_CODEC_SILK_VERSION),)
	@echo "Asterisk SILK CODEC not supported with the selected version of Asterisk"
	@exit 1
endif
	$(WGET) -P $(DL_DIR) $(ASTERISK_CODEC_SILK_SITE)/$(ASTERISK_CODEC_SILK_SOURCE)

$(ASTERISK_CODEC_SILK_DIR)/.source: $(DL_DIR)/$(ASTERISK_CODEC_SILK_SOURCE)
	zcat $(DL_DIR)/$(ASTERISK_CODEC_SILK_SOURCE) | tar -C $(BUILD_DIR) $(TAR_OPTIONS) -
	touch $@

$(TARGET_DIR)/$(ASTERISK_CODEC_SILK_BINARY): $(ASTERISK_CODEC_SILK_DIR)/.source | asterisk
	$(INSTALL) -D -m 0755 $(ASTERISK_CODEC_SILK_DIR)/codec_silk.so $(TARGET_DIR)/$(ASTERISK_CODEC_SILK_BINARY)

asterisk-codec-silk: $(TARGET_DIR)/$(ASTERISK_CODEC_SILK_BINARY)

asterisk-codec-silk-clean:
	rm -f $(TARGET_DIR)/$(ASTERISK_CODEC_SILK_BINARY)

asterisk-codec-silk-dirclean:
	rm -rf $(ASTERISK_CODEC_SILK_DIR)

#############################################################
#
# Toplevel Makefile options
#
#############################################################
ifeq ($(strip $(BR2_PACKAGE_ASTERISK_CODEC_SILK)),y)
TARGETS+=asterisk-codec-silk
endif
