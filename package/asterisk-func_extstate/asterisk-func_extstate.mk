#############################################################
#
# func_extstate
#
##############################################################
FUNC_EXTSTATE_DIR := $(BUILD_DIR)/func_extstate
FUNC_EXTSTATE_BINARY := func_extstate.so
FUNC_EXTSTATE_TARGET_BINARY = usr/lib/asterisk/modules/$(FUNC_EXTSTATE_BINARY)

$(FUNC_EXTSTATE_DIR)/func_extstate.c:
	mkdir -p $(FUNC_EXTSTATE_DIR)
	cp -p package/asterisk-func_extstate/func_extstate.c \
		$(FUNC_EXTSTATE_DIR)/

$(FUNC_EXTSTATE_DIR)/$(FUNC_EXTSTATE_BINARY): $(FUNC_EXTSTATE_DIR)/func_extstate.c | asterisk
	$(MAKE) -C $(FUNC_EXTSTATE_DIR) -f $(ASTERISK_DIR)/Makefile.module \
		all \
		ASTSRC=$(ASTERISK_DIR) \
		LOADABLE_MODS=func_extstate LIBS=

$(TARGET_DIR)/$(FUNC_EXTSTATE_TARGET_BINARY): $(FUNC_EXTSTATE_DIR)/$(FUNC_EXTSTATE_BINARY)
	$(MAKE) -C $(FUNC_EXTSTATE_DIR) -f $(ASTERISK_DIR)/Makefile.module \
		install \
		ASTSRC=$(ASTERISK_DIR) \
		LOADABLE_MODS=func_extstate LIBS= \
		DESTDIR=$(TARGET_DIR)

asterisk-func_extstate: $(TARGET_DIR)/$(FUNC_EXTSTATE_TARGET_BINARY)

asterisk-func_extstate-source: $(FUNC_EXTSTATE_DIR)/func_extstate.c

asterisk-func_extstate-dirclean:
	rm -rf $(FUNC_EXTSTATE_DIR)

asterisk-func_extstate-clean:
	rm -f $(TARGET_DIR)/$(FUNC_EXTSTATE_TARGET_BINARY)

#############################################################
#
# Toplevel Makefile options
#
#############################################################
ifeq ($(strip $(BR2_PACKAGE_ASTERISK_FUNC_EXTSTATE)),y)
TARGETS+=asterisk-func_extstate
endif

