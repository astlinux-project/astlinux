#############################################################
#
# misdn-digium
#   mISDN
#   mISDNuser
#
#############################################################
MISDN_DIGIUM_ISDN_VER:=1.1.3
MISDN_DIGIUM_USER_VER:=1.1.3
MISDN_DIGIUM_ISDN_SOURCE:=mISDN-digium-$(MISDN_DIGIUM_ISDN_VER).tar.gz
MISDN_DIGIUM_USER_SOURCE:=mISDNuser-digium-$(MISDN_DIGIUM_USER_VER).tar.gz
MISDN_DIGIUM_SITE:=http://svn.digium.com/svn/thirdparty
MISDN_DIGIUM_DIR:=$(BUILD_DIR)/misdn-digium
MISDN_DIGIUM_CAT:=zcat

$(DL_DIR)/$(MISDN_DIGIUM_ISDN_SOURCE):
	svn -q co $(MISDN_DIGIUM_SITE)/mISDN/tags/$(MISDN_DIGIUM_ISDN_VER) dl/mISDN
	find dl/mISDN -name ".svn" -print0 -prune | xargs -0 rm -rf
	tar -zcf $@ -C dl mISDN
	rm -rf dl/mISDN

$(DL_DIR)/$(MISDN_DIGIUM_USER_SOURCE):
	svn -q co $(MISDN_DIGIUM_SITE)/mISDNuser/tags/$(MISDN_DIGIUM_USER_VER) dl/mISDNuser
	find dl/mISDNuser -name ".svn" -print0 -prune | xargs -0 rm -rf
	tar -zcf $@ -C dl mISDNuser
	rm -rf dl/mISDNuser

$(MISDN_DIGIUM_DIR)/.unpacked: $(DL_DIR)/$(MISDN_DIGIUM_ISDN_SOURCE) \
			  $(DL_DIR)/$(MISDN_DIGIUM_USER_SOURCE)
	mkdir -p $(MISDN_DIGIUM_DIR)
	# this needs to be deleted eventually
	ln -sf $(BUILD_DIR)/linux-$(LINUX_VERSION) $(BUILD_DIR)/linux
	$(MISDN_DIGIUM_CAT) $(DL_DIR)/$(MISDN_DIGIUM_ISDN_SOURCE) | tar -C $(MISDN_DIGIUM_DIR) $(TAR_OPTIONS) -
	$(MISDN_DIGIUM_CAT) $(DL_DIR)/$(MISDN_DIGIUM_USER_SOURCE) | tar -C $(MISDN_DIGIUM_DIR) $(TAR_OPTIONS) -
	touch $@

$(MISDN_DIGIUM_DIR)/.patched: $(MISDN_DIGIUM_DIR)/.unpacked
	toolchain/patch-kernel.sh $(MISDN_DIGIUM_DIR)/mISDN package/misdn-digium/ misdn-digium\*.patch
	toolchain/patch-kernel.sh $(MISDN_DIGIUM_DIR)/mISDNuser package/misdn-digium/ muser-digium\*.patch
	touch $@

$(MISDN_DIGIUM_DIR)/.built1: $(MISDN_DIGIUM_DIR)/.patched | linux bc bash pciutils usbutils
	rm -f $(STAGING_DIR)/include/linux/mISDN*
	rm -f $(BUILD_DIR)/linux/include/linux/mISDN*
	#for i in $(LINUX_DIR)/include/linux/mISDN*; do \
	#  mv $$i $$i.v2 ; \
	#done
	linux32 $(MAKE) BASEDIR=$(MISDN_DIGIUM_DIR)/mISDN MODS=$(BUILD_DIR) \
		INSTALL_PREFIX=$(TARGET_DIR) CC=$(TARGET_CC) $(TARGET_CONFIGURE_OPTS) -C $(MISDN_DIGIUM_DIR)/mISDN
	#for i in $(LINUX_DIR)/include/linux/mISDN*.v2; do \
	#  mv $$i `dirname $$i`/`basename $$i .v2`; \
	#done
	touch $@

$(MISDN_DIGIUM_DIR)/.installed1: $(MISDN_DIGIUM_DIR)/.built1
	linux32 $(MAKE) LINUX_VER=$(LINUX_VERSION_PROBED) MODS=$(BUILD_DIR) \
		INSTALL_BUILD=$(LINUX_DIR) BASEDIR=$(MISDN_DIGIUM_DIR)/mISDN \
		CC=$(TARGET_CC) INSTALL_PREFIX=$(TARGET_DIR) \
		DEPMOD=$(HOST_DIR)/usr/sbin/depmod \
		$(TARGET_CONFIGURE_OPTS) -C $(MISDN_DIGIUM_DIR)/mISDN install
	mkdir -p $(STAGING_DIR)/usr/include/linux
	rm -f $(TARGET_DIR)/etc/modprobe.d/mISDN
	rm -rf $(TARGET_DIR)/etc/modules.d
	cp $(MISDN_DIGIUM_DIR)/mISDN/include/linux/*.h $(STAGING_DIR)/usr/include/linux
	touch $@

$(MISDN_DIGIUM_DIR)/.built2: $(MISDN_DIGIUM_DIR)/.patched
	$(MAKE1) MISDNDIR=$(MISDN_DIGIUM_DIR)/mISDN \
		MISDNUSERDIR=$(MISDN_DIGIUM_DIR)/mISDNuser \
		MODS=$(BUILD_DIR) CC=$(TARGET_CC) \
		INSTALL_PREFIX=$(TARGET_DIR) \
		-C $(MISDN_DIGIUM_DIR)/mISDNuser install
	touch $@

$(MISDN_DIGIUM_DIR)/.installed2: $(MISDN_DIGIUM_DIR)/.built2
	mkdir -p $(STAGING_DIR)/usr/include/mISDNuser
	cp $(MISDN_DIGIUM_DIR)/mISDNuser/include/*.h $(STAGING_DIR)/usr/include/mISDNuser
	cp $(MISDN_DIGIUM_DIR)/mISDNuser/i4lnet/*.h $(STAGING_DIR)/usr/include/mISDNuser
	ln -sf ../../usr/sbin/misdn-init $(TARGET_DIR)/etc/init.d/misdn-init
	rm -f $(TARGET_DIR)/usr/lib/libmISDN*.a
	rm -f $(TARGET_DIR)/usr/lib/libisdn*.a
	rm -f $(TARGET_DIR)/usr/lib/libsuppserv*.a
	cp $(TARGET_DIR)/usr/lib/libmISDN.so $(STAGING_DIR)/usr/lib
	cp $(TARGET_DIR)/usr/lib/libisdnnet.so $(STAGING_DIR)/usr/lib
	cp $(TARGET_DIR)/usr/lib/libsuppserv.so $(STAGING_DIR)/usr/lib
	cp $(MISDN_DIGIUM_DIR)/mISDNuser/suppserv/suppserv.h $(STAGING_DIR)/usr/include/mISDNuser
	touch $@

misdn-digium: $(MISDN_DIGIUM_DIR)/.installed1 $(MISDN_DIGIUM_DIR)/.installed2

misdn-digium-clean:
	rm -rf $(MISDN_DIGIUM_DIR)

misdn-digium-dirclean:
	rm -rf $(MISDN_DIGIUM_DIR)

misdn-digium-source: $(MISDN_DIGIUM_DIR)/.patched

#############################################################
#
# Toplevel Makefile options
#
#############################################################
ifeq ($(strip $(BR2_PACKAGE_MISDN_DIGIUM)),y)
TARGETS+=misdn-digium
endif
