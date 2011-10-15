#############################################################
#
# app_nvfax
#
##############################################################
APP_NVFAX_DIR := $(BUILD_DIR)/app_nvfax
APP_NVFAXDETECT_BINARY := app_nv_faxdetect.so
APP_NVFAXDETECT_TARGET_BINARY = $(ASTERISK_MODULE_DIR)/$(APP_NVFAXDETECT_BINARY)
APP_NVBACKGROUNDDETECT_BINARY := app_nv_backgrounddetect.so
APP_NVBACKGROUNDDETECT_TARGET_BINARY = $(ASTERISK_MODULE_DIR)/$(APP_NVBACKGROUNDDETECT_BINARY)

$(APP_NVFAX_DIR)/app_nv_faxdetect.c:
	mkdir -p $(APP_NVFAX_DIR)
	cp -p package/asterisk-app_nvfax/app_nv_faxdetect.c \
		$(APP_NVFAX_DIR)/

$(APP_NVFAX_DIR)/app_nv_backgrounddetect.c:
	mkdir -p $(APP_NVFAX_DIR)
	cp -p package/asterisk-app_nvfax/app_nv_backgrounddetect.c \
		$(APP_NVFAX_DIR)/

$(APP_NVFAX_DIR)/$(APP_NVFAXDETECT_BINARY): $(APP_NVFAX_DIR)/app_nv_faxdetect.c | asterisk
	$(MAKE) -C $(APP_NVFAX_DIR) -f $(ASTERISK_DIR)/Makefile.module \
		all \
		ASTSRC=$(ASTERISK_DIR) \
		LOADABLE_MODS=app_nv_faxdetect LIBS=

$(APP_NVFAX_DIR)/$(APP_NVBACKGROUNDDETECT_BINARY): $(APP_NVFAX_DIR)/app_nv_backgrounddetect.c | asterisk
	$(MAKE) -C $(APP_NVFAX_DIR) -f $(ASTERISK_DIR)/Makefile.module \
		all \
		ASTSRC=$(ASTERISK_DIR) \
		LOADABLE_MODS=app_nv_backgrounddetect LIBS=

$(TARGET_DIR)/$(APP_NVFAXDETECT_TARGET_BINARY): $(APP_NVFAX_DIR)/$(APP_NVFAXDETECT_BINARY)
	$(MAKE) -C $(APP_NVFAX_DIR) -f $(ASTERISK_DIR)/Makefile.module \
		install \
		ASTSRC=$(ASTERISK_DIR) \
		LOADABLE_MODS=app_nv_faxdetect LIBS= \
		DESTDIR=$(TARGET_DIR)

$(TARGET_DIR)/$(APP_NVBACKGROUNDDETECT_TARGET_BINARY): $(APP_NVFAX_DIR)/$(APP_NVBACKGROUNDDETECT_BINARY)
	$(MAKE) -C $(APP_NVFAX_DIR) -f $(ASTERISK_DIR)/Makefile.module \
		install \
		ASTSRC=$(ASTERISK_DIR) \
		LOADABLE_MODS=app_nv_backgrounddetect LIBS= \
		DESTDIR=$(TARGET_DIR)

asterisk-app_nvfax: $(TARGET_DIR)/$(APP_NVFAXDETECT_TARGET_BINARY) \
		$(TARGET_DIR)/$(APP_NVBACKGROUNDDETECT_TARGET_BINARY)

asterisk-app_nvfax-source: $(APP_NVFAX_DIR)/app_nv_faxdetect.c $(APP_NVFAX_DIR)/app_nv_backgrounddetect.c

asterisk-app_nvfax-dirclean:
	rm -rf $(APP_NVFAX_DIR)

asterisk-app_nvfax-clean:
	rm -f $(TARGET_DIR)/$(APP_NVFAXDETECT_TARGET_BINARY)
	rm -f $(TARGET_DIR)/$(APP_NVBACKGROUNDDETECT_TARGET_BINARY)

#############################################################
#
# Toplevel Makefile options
#
#############################################################
ifeq ($(strip $(BR2_PACKAGE_ASTERISK_APP_NVFAX)),y)
TARGETS+=asterisk-app_nvfax
endif

