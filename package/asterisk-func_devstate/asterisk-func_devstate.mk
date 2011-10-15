#############################################################
#
# func_devstate
#
##############################################################
FUNC_DEVSTATE_DIR := $(BUILD_DIR)/func_devstate
FUNC_DEVSTATE_BINARY := func_devstate.so
FUNC_DEVSTATE_TARGET_BINARY = $(ASTERISK_MODULE_DIR)/$(FUNC_DEVSTATE_BINARY)

$(FUNC_DEVSTATE_DIR)/func_devstate.c:
	mkdir -p $(FUNC_DEVSTATE_DIR)
	cp -p package/asterisk-func_devstate/func_devstate.c \
		$(FUNC_DEVSTATE_DIR)/

$(FUNC_DEVSTATE_DIR)/$(FUNC_DEVSTATE_BINARY): $(FUNC_DEVSTATE_DIR)/func_devstate.c | asterisk
	$(MAKE) -C $(FUNC_DEVSTATE_DIR) -f $(ASTERISK_DIR)/Makefile.module \
		all \
		ASTSRC=$(ASTERISK_DIR) \
		LOADABLE_MODS=func_devstate LIBS=

$(TARGET_DIR)/$(FUNC_DEVSTATE_TARGET_BINARY): $(FUNC_DEVSTATE_DIR)/$(FUNC_DEVSTATE_BINARY)
	$(MAKE) -C $(FUNC_DEVSTATE_DIR) -f $(ASTERISK_DIR)/Makefile.module \
		install \
		ASTSRC=$(ASTERISK_DIR) \
		LOADABLE_MODS=func_devstate LIBS= \
		DESTDIR=$(TARGET_DIR)

asterisk-func_devstate: $(TARGET_DIR)/$(FUNC_DEVSTATE_TARGET_BINARY)

asterisk-func_devstate-source: $(FUNC_DEVSTATE_DIR)/func_devstate.c

asterisk-func_devstate-dirclean:
	rm -rf $(FUNC_DEVSTATE_DIR)

asterisk-func_devstate-clean:
	rm -f $(TARGET_DIR)/$(FUNC_DEVSTATE_TARGET_BINARY)

#############################################################
#
# Toplevel Makefile options
#
#############################################################
ifeq ($(strip $(BR2_PACKAGE_ASTERISK_FUNC_DEVSTATE)),y)
TARGETS+=asterisk-func_devstate
endif

