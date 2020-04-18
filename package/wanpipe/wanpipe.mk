#############################################################
#
# wanpipe
#
#############################################################
WANPIPE_VER:= 7.0.20
WANPIPE_SITE:= ftp://ftp.sangoma.com/linux/current_wanpipe
WANPIPE_SOURCE:=wanpipe-$(WANPIPE_VER).tgz
WANPIPE_DIR:=$(BUILD_DIR)/wanpipe-$(WANPIPE_VER)
WANPIPE_CAT:=zcat
WANPIPE_BINARY:=util/wanconfig/wanconfig
WANPIPE_TARGET_DIR:=usr/sbin
WANPIPE_TARGET_BINARY:=$(WANPIPE_TARGET_DIR)/wanconfig

WANPIPE_PREREQS:=flex dahdi-linux
WANPIPE_CONFIGURE:=\
	ARCH=$(KERNEL_ARCH) \
	ZAPDIR="$(DAHDI_LINUX_DIR)"

$(DL_DIR)/$(WANPIPE_SOURCE):
	$(WGET) -P $(DL_DIR) $(WANPIPE_SITE)/$(WANPIPE_SOURCE)

$(WANPIPE_DIR)/.unpacked: $(DL_DIR)/$(WANPIPE_SOURCE)
	$(WANPIPE_CAT) $(DL_DIR)/$(WANPIPE_SOURCE) | tar -C $(BUILD_DIR) $(TAR_OPTIONS) -
	find $(WANPIPE_DIR) -name .svn | xargs -r rm -rf
	touch $@

$(WANPIPE_DIR)/.patched: $(WANPIPE_DIR)/.unpacked
	toolchain/patch-kernel.sh $(WANPIPE_DIR) package/wanpipe/ wanpipe\*.patch
	touch $@

$(WANPIPE_DIR)/.built: $(WANPIPE_DIR)/.patched | $(WANPIPE_PREREQS)
	# Build and install 'libsangoma'
	(cd $(WANPIPE_DIR)/api/libsangoma; rm -rf config.cache configure; \
		/bin/sh ./bootstrap; \
		$(TARGET_CONFIGURE_OPTS) \
		./configure \
		--target=$(GNU_TARGET_NAME) \
		--host=$(GNU_TARGET_NAME) \
		--build=$(GNU_HOST_NAME) \
		--prefix=/usr \
	)
	$(MAKE) -C $(WANPIPE_DIR)/api/libsangoma CC="$(TARGET_CC)" CXX="$(TARGET_CXX)" \
		$(WANPIPE_CONFIGURE)
	$(MAKE) -C $(WANPIPE_DIR)/api/libsangoma \
		DESTDIR=$(STAGING_DIR) \
		install
	$(MAKE) -C $(WANPIPE_DIR)/api/libsangoma \
		DESTDIR=$(TARGET_DIR) \
		install
	# Finished 'libsangoma'
	$(MAKE) -C $(WANPIPE_DIR) \
		HOSTCC=gcc CC="$(TARGET_CC)" CXX="$(TARGET_CXX)" \
		WARCH=$(KERNEL_ARCH) \
		KVER=$(LINUX_VERSION_PROBED) \
		KDIR=$(LINUX_DIR) \
		KINSTDIR=/lib/modules/$(LINUX_VERSION_PROBED)/kernel \
		PWD=$(WANPIPE_DIR) \
		INSTALLPREFIX=$(TARGET_DIR) \
		$(WANPIPE_CONFIGURE) \
		KMOD=$(TARGET_DIR)/lib/modules/$(LINUX_VERSION_PROBED) \
		EXTRA_FLAGS="-DCONFIG_RPS" \
		ASTBROOT=$(STAGING_DIR)/usr \
		all_kmod_dahdi 
	$(MAKE) -C $(WANPIPE_DIR)/util/wanconfig CC="$(TARGET_CC)" CXX="$(TARGET_CXX)" \
		SYSINC=$(WANPIPE_DIR)/patches/kdrivers/include \
		$(WANPIPE_CONFIGURE)
	$(MAKE) -C $(WANPIPE_DIR)/util/wan_aftup CC="$(TARGET_CC)" CXX="$(TARGET_CXX)" \
		SYSINC=$(WANPIPE_DIR)/patches/kdrivers/include \
		EXTRA_FLAGS="-I$(STAGING_DIR)/usr/include" \
		$(WANPIPE_CONFIGURE)
	$(MAKE) -C $(WANPIPE_DIR)/util/wancfg CC="$(TARGET_CC)" CXX="$(TARGET_CXX)" \
		SYSINC=$(WANPIPE_DIR)/patches/kdrivers/include \
		EXTRA_FLAGS="-I$(STAGING_DIR)/usr/include" \
		EXTRA_FLAGS+=" -Wno-write-strings" \
		DAHDI_ISSUES=YES \
		$(WANPIPE_CONFIGURE)
	$(MAKE) -C $(WANPIPE_DIR)/util/lxdialog CC="$(TARGET_CC)" CXX="$(TARGET_CXX)" \
		SYSINC=$(STAGING_DIR)/usr/include \
		ASTBROOT=$(STAGING_DIR)/usr \
		$(WANPIPE_CONFIGURE)
	$(MAKE) -C $(WANPIPE_DIR)/util/wanec_client CC="$(TARGET_CC)" CXX="$(TARGET_CXX)" \
		WANINCDIR=$(STAGING_DIR)/usr/include \
		$(WANPIPE_CONFIGURE)
	$(MAKE) -C $(WANPIPE_DIR)/util/wanpipemon CC="$(TARGET_CC)" CXX="$(TARGET_CXX)" \
		SYSINC=$(STAGING_DIR)/usr/include \
		$(WANPIPE_CONFIGURE)
	touch $@

$(TARGET_DIR)/$(WANPIPE_TARGET_BINARY): $(WANPIPE_DIR)/.built
	$(MAKE) -C $(WANPIPE_DIR) \
		HOSTCC=gcc CC="$(TARGET_CC)" CXX="$(TARGET_CXX)" \
		WARCH=$(KERNEL_ARCH) \
		KVER=$(LINUX_VERSION_PROBED) \
		KDIR=$(LINUX_DIR) \
		KINSTDIR=/lib/modules/$(LINUX_VERSION_PROBED)/kernel \
		PWD=$(WANPIPE_DIR) \
		INSTALLPREFIX=$(TARGET_DIR) \
		KMOD=$(TARGET_DIR)/lib/modules/$(LINUX_VERSION_PROBED) \
		$(WANPIPE_CONFIGURE) \
		install_kmod install_etc
	$(MAKE) -C $(WANPIPE_DIR)/util/wan_aftup CC="$(TARGET_CC)" CXX="$(TARGET_CXX)" \
		INSTALLPREFIX=$(TARGET_DIR) \
		$(WANPIPE_CONFIGURE) \
		install
	$(DEPMOD) -ae -F $(LINUX_DIR)/System.map -b $(TARGET_DIR) $(LINUX_VERSION_PROBED)
	$(INSTALL) -D -m 0755 -s $(WANPIPE_DIR)/$(WANPIPE_BINARY) $(TARGET_DIR)/$(WANPIPE_TARGET_BINARY)
	ln -sf ../../etc/wanpipe/util/wan_aftup/wan_aftup $(TARGET_DIR)/usr/sbin/wan_aftup
	$(INSTALL) -D -m 0755 -s $(WANPIPE_DIR)/util/wancfg/wancfg $(TARGET_DIR)/usr/sbin/wancfg
	$(INSTALL) -D -m 0755 -s $(WANPIPE_DIR)/util/lxdialog/lxdialog $(TARGET_DIR)/usr/sbin/wanpipe_lxdialog
	$(INSTALL) -D -m 0755 -s $(WANPIPE_DIR)/util/wanec_client/wan_ec_client $(TARGET_DIR)/usr/sbin/wan_ec_client
	$(INSTALL) -D -m 0755 -s $(WANPIPE_DIR)/util/wanpipemon/wanpipemon $(TARGET_DIR)/usr/sbin/wanpipemon
	$(INSTALL) -m 0755 $(WANPIPE_DIR)/util/wancfg_zaptel/wancfg_dahdi $(TARGET_DIR)/usr/sbin/wancfg_dahdi
	$(INSTALL) -m 0755 package/wanpipe/wanrouter.init $(TARGET_DIR)/etc/init.d/wanrouter
	ln -sf ../../init.d/wanrouter $(TARGET_DIR)/etc/runlevels/default/S00wanrouter
	ln -sf ../../init.d/wanrouter $(TARGET_DIR)/etc/runlevels/default/K98wanrouter
	## Install and Cleanup wancfg_zaptel directory
	cp -af $(WANPIPE_DIR)/util/wancfg_zaptel $(TARGET_DIR)/etc/wanpipe
	for i in setup-sangoma clean.sh install.sh uninstall.sh Makefile; do \
	  rm -f $(TARGET_DIR)/etc/wanpipe/wancfg_zaptel/$$i ; \
	done
	## Edit wanrouter.rc default configuration
	$(SED) 's:^WAN_LOCK=.*$$:WAN_LOCK=/var/lock/wanrouter:' \
	    -e 's:^WAN_LOCK_DIR=.*$$:WAN_LOCK_DIR=/var/lock:' \
		$(TARGET_DIR)/etc/wanpipe/wanrouter.rc
	## Cleanup Target /etc/wanpipe
	rm -rf $(TARGET_DIR)/etc/wanpipe/api
	## Move to /stat/etc/wanpipe
	mv $(TARGET_DIR)/etc/wanpipe $(TARGET_DIR)/stat/etc/
	##
	ln -sf /tmp/etc/wanpipe $(TARGET_DIR)/etc/wanpipe

wanpipe: $(TARGET_DIR)/$(WANPIPE_TARGET_BINARY)

wanpipe-source: $(WANPIPE_DIR)/.patched

wanpipe-clean:
	-$(MAKE1) -C $(WANPIPE_DIR) \
		KVER=$(LINUX_VERSION_PROBED) \
		KDIR=$(LINUX_DIR) \
		KINSTDIR=/lib/modules/$(LINUX_VERSION_PROBED)/kernel \
		PWD=$(WANPIPE_DIR) \
		$(TARGET_CONFIGURE_OPTS) \
		INSTALLPREFIX=$(TARGET_DIR) \
		KMOD=$(TARGET_DIR)/lib/modules/$(LINUX_VERSION_PROBED) \
		WARCH=$(KERNEL_ARCH) \
		$(WANPIPE_CONFIGURE) \
		clean
	rm -rf $(TARGET_DIR)/etc/wanpipe $(TARGET_DIR)/stat/etc/wanpipe
	rm -f $(TARGET_DIR)/$(WANPIPE_TARGET_BINARY)
	rm -f $(TARGET_DIR)/usr/sbin/wanrouter
	rm -f $(TARGET_DIR)/usr/sbin/wan_aftup
	rm -f $(TARGET_DIR)/usr/sbin/wancfg
	rm -f $(TARGET_DIR)/usr/sbin/wanpipe_lxdialog
	rm -f $(TARGET_DIR)/usr/sbin/wan_ec_client
	rm -f $(TARGET_DIR)/usr/sbin/wanpipemon
	rm -f $(TARGET_DIR)/usr/sbin/wancfg_dahdi
	rm -f $(TARGET_DIR)/etc/init.d/wanrouter
	rm -f $(TARGET_DIR)/etc/runlevels/default/S00wanrouter
	rm -f $(TARGET_DIR)/etc/runlevels/default/K98wanrouter
	rm -f $(WANPIPE_DIR)/.built

wanpipe-dirclean:
	rm -rf $(WANPIPE_DIR)

#############################################################
#
# Toplevel Makefile options
#
#############################################################
ifeq ($(strip $(BR2_PACKAGE_WANPIPE)),y)
TARGETS+=wanpipe
endif
