#############################################################
#
# asterisk-app_notify
#
# http://www.mezzo.net/asterisk/app_notify.html
#
#############################################################
APPNOTIFY_VER:=2.0rc1
APPNOTIFY_SOURCE:=app_notify-$(APPNOTIFY_VER).tgz
APPNOTIFY_SITE:=http://www.mezzo.net/asterisk
APPNOTIFY_DIR:=$(BUILD_DIR)/app_notify-$(APPNOTIFY_VER)
APPNOTIFY_CAT:=zcat
APPNOTIFY_TARGET_DIR=$(TARGET_DIR)/usr/lib/asterisk/modules

$(DL_DIR)/$(APPNOTIFY_SOURCE):
	$(WGET) -P $(DL_DIR) $(APPNOTIFY_SITE)/$(APPNOTIFY_SOURCE)

$(APPNOTIFY_DIR)/.unpacked: $(DL_DIR)/$(APPNOTIFY_SOURCE)
	$(APPNOTIFY_CAT) $(DL_DIR)/$(APPNOTIFY_SOURCE) | tar -C $(BUILD_DIR) $(TAR_OPTIONS) -
	touch $@

$(APPNOTIFY_DIR)/.patched: $(APPNOTIFY_DIR)/.unpacked
	toolchain/patch-kernel.sh $(APPNOTIFY_DIR) package/asterisk-app_notify/ asterisk-app_notify\*.patch
	touch $@

$(APPNOTIFY_DIR)/app_notify.so: $(APPNOTIFY_DIR)/.patched | asterisk
	$(MAKE) -C $(APPNOTIFY_DIR) -f $(ASTERISK_DIR)/Makefile.module \
		all \
		ASTSRC=$(ASTERISK_DIR) \
		SOLINK="-shared -fPIC" \
		LOADABLE_MODS=app_notify LIBS=

$(APPNOTIFY_TARGET_DIR)/app_notify.so: $(APPNOTIFY_DIR)/app_notify.so
	$(MAKE) -C $(APPNOTIFY_DIR) -f $(ASTERISK_DIR)/Makefile.module \
		install \
		ASTSRC=$(ASTERISK_DIR) \
		LOADABLE_MODS=app_notify LIBS= \
		DESTDIR=$(TARGET_DIR)

asterisk-app_notify: $(APPNOTIFY_TARGET_DIR)/app_notify.so

asterisk-app_notify-source: $(APPNOTIFY_DIR)/.patched

asterisk-app_notify-clean:
	rm -f $(APPNOTIFY_TARGET_DIR)/app_notify.so
	$(MAKE) -C $(APPNOTIFY_DIR) clean

asterisk-app_notify-dirclean:
	rm -f $(APPNOTIFY_TARGET_DIR)/app_notify.so
	rm -rf $(APPNOTIFY_DIR)

#############################################################
#
# Toplevel Makefile options
#
#############################################################
ifeq ($(strip $(BR2_PACKAGE_ASTERISK_APP_NOTIFY)),y)
TARGETS+=asterisk-app_notify
endif

