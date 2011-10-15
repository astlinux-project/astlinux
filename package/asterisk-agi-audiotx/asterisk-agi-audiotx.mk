#############################################################
#
# asterisk-agi-audiotx
#
# http://downloads.sourceforge.net/agi-audiotx/asterisk-agi-audiotx-0.3.tar.gz
#
##############################################################
ASTERISK-AGI-AUDIOTX_VER:=0.3
ASTERISK-AGI-AUDIOTX_SOURCE:=asterisk-agi-audiotx-$(ASTERISK-AGI-AUDIOTX_VER).tar.gz
ASTERISK-AGI-AUDIOTX_SITE:=http://downloads.sourceforge.net/agi-audiotx
ASTERISK-AGI-AUDIOTX_DIR:=$(BUILD_DIR)/asterisk-agi-audiotx-$(ASTERISK-AGI-AUDIOTX_VER)
ASTERISK-AGI-AUDIOTX_CAT:=zcat
ASTERISK-AGI-AUDIOTX_TARGET_DIR=$(TARGET_DIR)/usr/lib/asterisk/modules/

$(DL_DIR)/$(ASTERISK-AGI-AUDIOTX_SOURCE):
	$(WGET) -P $(DL_DIR) $(ASTERISK-AGI-AUDIOTX_SITE)/$(ASTERISK-AGI-AUDIOTX_SOURCE)

$(ASTERISK-AGI-AUDIOTX_DIR)/.unpacked: $(DL_DIR)/$(ASTERISK-AGI-AUDIOTX_SOURCE)
	$(ASTERISK-AGI-AUDIOTX_CAT) $(DL_DIR)/$(ASTERISK-AGI-AUDIOTX_SOURCE) | tar -C $(BUILD_DIR) $(TAR_OPTIONS) -
	touch $@

$(ASTERISK-AGI-AUDIOTX_DIR)/res_agi-audiotx.so: $(ASTERISK-AGI-AUDIOTX_DIR)/.unpacked | asterisk
	$(MAKE) -C $(ASTERISK-AGI-AUDIOTX_DIR) ASTERISKINCLUDE=-I$(ASTERISK_DIR)/include CC=$(TARGET_CC)

$(ASTERISK-AGI-AUDIOTX_TARGET_DIR)/res_agi-audiotx.so: $(ASTERISK-AGI-AUDIOTX_DIR)/res_agi-audiotx.so
	$(INSTALL) -D -m 0755 $(ASTERISK-AGI-AUDIOTX_DIR)/res_agi-audiotx.so $(ASTERISK-AGI-AUDIOTX_TARGET_DIR)/res_agi-audiotx.so

asterisk-agi-audiotx: $(ASTERISK-AGI-AUDIOTX_TARGET_DIR)/res_agi-audiotx.so

asterisk-agi-audiotx-clean:
	rm -f $(ASTERISK-AGI-AUDIOTX_TARGET_DIR)/res_agi-audiotx.so
	$(MAKE) -C $(ASTERISK-AGI-AUDIOTX_DIR) clean

asterisk-agi-audiotx-dirclean:
	rm -rf $(ASTERISK-AGI-AUDIOTX_DIR)

#############################################################
#
# Toplevel Makefile options
#
#############################################################
ifeq ($(strip $(BR2_PACKAGE_ASTERISK-AGI-AUDIOTX)),y)
TARGETS+=asterisk-agi-audiotx
endif

