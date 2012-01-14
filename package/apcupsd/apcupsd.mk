#############################################################
#
# apcupsd
#
#############################################################
APCUPSD_VERSION:=3.14.10
APCUPSD_SOURCE:=apcupsd-$(APCUPSD_VERSION).tar.gz
APCUPSD_SITE:=http://$(BR2_SOURCEFORGE_MIRROR).dl.sourceforge.net/sourceforge/apcupsd
APCUPSD_DIR:=$(BUILD_DIR)/apcupsd-$(APCUPSD_VERSION)
APCUPSD_CAT:=zcat
APCUPSD_BINARY:=src/apcupsd
APCUPSD_TARGET_BINARY:=usr/sbin/apcupsd

$(DL_DIR)/$(APCUPSD_SOURCE):
	$(WGET) -P $(DL_DIR) $(APCUPSD_SITE)/$(APCUPSD_SOURCE)

$(APCUPSD_DIR)/.unpacked: $(DL_DIR)/$(APCUPSD_SOURCE)
	zcat $(DL_DIR)/$(APCUPSD_SOURCE) | tar -C $(BUILD_DIR) $(TAR_OPTIONS) -
	toolchain/patch-kernel.sh $(APCUPSD_DIR) package/apcupsd apcupsd\*.patch
	touch $@

$(APCUPSD_DIR)/.configured: $(APCUPSD_DIR)/.unpacked | libusb
	(cd $(APCUPSD_DIR); \
	PATH=$(STAGING_DIR)/bin:$$PATH; \
	ac_cv_func_setpgrp_void=yes \
	./configure \
	--target=$(GNU_TARGET_NAME) \
	--host=$(GNU_TARGET_NAME) \
	--build=$(GNU_HOST_NAME) \
	--prefix=/usr \
	--exec-prefix=/usr \
	--sbindir=/usr/sbin \
	--sysconfdir=/etc/apcupsd \
	--enable-usb \
	--with-distname=unknown \
	CPPFLAGS='$(TARGET_CFLAGS)' \
	)
	touch $@

$(APCUPSD_DIR)/$(APCUPSD_BINARY): $(APCUPSD_DIR)/.configured
	PATH=$(STAGING_DIR)/bin:$$PATH \
	$(MAKE1) CC=$(TARGET_CC) -C $(APCUPSD_DIR) VERBOSE=1

$(TARGET_DIR)/$(APCUPSD_TARGET_BINARY): $(APCUPSD_DIR)/$(APCUPSD_BINARY)
	if [ -h $(TARGET_DIR)/etc/apcupsd ]; then \
		rm $(TARGET_DIR)/etc/apcupsd ; \
	fi ; \
	if [ ! -d $(TARGET_DIR)/etc/apcupsd ]; then \
		mkdir -p $(TARGET_DIR)/etc/apcupsd ; \
	fi
	PATH=$(STAGING_DIR)/bin:$$PATH \
	$(MAKE1) -C $(APCUPSD_DIR) DESTDIR=$(TARGET_DIR) install
	@rm -f $(TARGET_DIR)/usr/sbin/smtp
	@rm -rf $(TARGET_DIR)/stat/etc/apcupsd
	mv -f $(TARGET_DIR)/etc/apcupsd $(TARGET_DIR)/stat/etc/apcupsd
	# Install custom email alerts
	$(INSTALL) -m 0444 package/apcupsd/scripts/astlinux.sh $(TARGET_DIR)/stat/etc/apcupsd/astlinux.sh
	for i in changeme commfailure commok offbattery onbattery; do \
	  $(INSTALL) -m 0744 package/apcupsd/scripts/$$i $(TARGET_DIR)/stat/etc/apcupsd/$$i ; \
	done
	#
	$(INSTALL) -D -m 0755 package/apcupsd/apcupsd.init $(TARGET_DIR)/etc/init.d/apcupsd
	ln -sf /tmp/etc/apcupsd $(TARGET_DIR)/etc/apcupsd

apcupsd: $(TARGET_DIR)/$(APCUPSD_TARGET_BINARY)

apcupsd-source: $(APCUPSD_DIR)/.unpacked

apcupsd-clean:
	$(MAKE1) -C $(APCUPSD_DIR) uninstall DESTDIR=$(TARGET_DIR)
	-$(MAKE) -C $(APCUPSD_DIR) clean DESTDIR=$(TARGET_DIR)

apcupsd-dirclean:
	rm -rf $(APCUPSD_DIR)

#############################################################
#
# Toplevel Makefile options
#
#############################################################
ifeq ($(strip $(BR2_PACKAGE_APCUPSD)),y)
TARGETS+=apcupsd
endif

