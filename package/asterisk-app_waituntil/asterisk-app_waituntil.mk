#############################################################
#
# app_waituntil
#
##############################################################
APP_WAITUNTIL_DIR := $(BUILD_DIR)/app_waituntil
APP_WAITUNTIL_BINARY := app_waituntil.so
APP_WAITUNTIL_TARGET_BINARY = $(ASTERISK_MODULE_DIR)/$(APP_WAITUNTIL_BINARY)

$(APP_WAITUNTIL_DIR)/app_waituntil.c:
	mkdir -p $(APP_WAITUNTIL_DIR)
	cp -p package/asterisk-app_waituntil/app_waituntil.c \
		$(APP_WAITUNTIL_DIR)/

$(APP_WAITUNTIL_DIR)/$(APP_WAITUNTIL_BINARY): $(APP_WAITUNTIL_DIR)/app_waituntil.c | asterisk
	$(MAKE) -C $(APP_WAITUNTIL_DIR) -f $(ASTERISK_DIR)/Makefile.module \
		all \
		ASTSRC=$(ASTERISK_DIR) \
		LOADABLE_MODS=app_waituntil LIBS=

$(TARGET_DIR)/$(APP_WAITUNTIL_TARGET_BINARY): $(APP_WAITUNTIL_DIR)/$(APP_WAITUNTIL_BINARY)
	$(MAKE) -C $(APP_WAITUNTIL_DIR) -f $(ASTERISK_DIR)/Makefile.module \
		install \
		ASTSRC=$(ASTERISK_DIR) \
		LOADABLE_MODS=app_waituntil LIBS= \
		DESTDIR=$(TARGET_DIR)

asterisk-app_waituntil: $(TARGET_DIR)/$(APP_WAITUNTIL_TARGET_BINARY)

asterisk-app_waituntil-source: $(APP_WAITUNTIL_DIR)/app_waituntil.c

asterisk-app_waituntil-dirclean:
	rm -rf $(APP_WAITUNTIL_DIR)

asterisk-app_waituntil-clean:
	rm -f $(TARGET_DIR)/$(APP_WAITUNTIL_TARGET_BINARY)

#############################################################
#
# Toplevel Makefile options
#
#############################################################
ifeq ($(strip $(BR2_PACKAGE_ASTERISK_APP_WAITUNTIL)),y)
TARGETS+=asterisk-app_waituntil
endif

