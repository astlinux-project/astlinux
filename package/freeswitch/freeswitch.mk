#############################################################
#
# freeswitch
#
##############################################################

# Manual snapshots generated from:
# git clone git://git.freeswitch.org/freeswitch.git
#
FREESWITCH_SITE := https://astlinux-project.org/files
FREESWITCH_VERSION := 2011-10-18
FREESWITCH_SOURCE := freeswitch-$(FREESWITCH_VERSION).tar.gz
FREESWITCH_DIR := $(BUILD_DIR)/freeswitch-$(FREESWITCH_VERSION)
FREESWITCH_INSTALL_DIR := /usr/local/freeswitch
FREESWITCH_BINARY := .libs/freeswitch
FREESWITCH_TARGET_BINARY := $(FREESWITCH_INSTALL_DIR)/bin/freeswitch

$(DL_DIR)/$(FREESWITCH_SOURCE):
	$(WGET) -P $(DL_DIR) $(FREESWITCH_SITE)/$(FREESWITCH_SOURCE)

$(FREESWITCH_DIR)/.source: $(DL_DIR)/$(FREESWITCH_SOURCE)
	zcat $(DL_DIR)/$(FREESWITCH_SOURCE) | tar -C $(BUILD_DIR) $(TAR_OPTIONS) -
	toolchain/patch-kernel.sh $(FREESWITCH_DIR) package/freeswitch/ freeswitch\*.patch
	touch $@

$(FREESWITCH_DIR)/configure: $(FREESWITCH_DIR)/.source
	(cd $(FREESWITCH_DIR); \
		$(TARGET_CONFIGURE_OPTS) CC_FOR_BUILD=$(HOSTCC) \
		config_BUILD_CC=$(HOSTCC) \
		CFLAGS="$(TARGET_CFLAGS)" \
		./bootstrap.sh \
	)

$(FREESWITCH_DIR)/.configured: $(FREESWITCH_DIR)/configure | libpcap openssl zlib
	(cd $(FREESWITCH_DIR); rm -rf config.cache; \
		$(TARGET_CONFIGURE_OPTS) CC_FOR_BUILD=$(HOSTCC) \
		CFLAGS="$(TARGET_CFLAGS)" \
		config_BUILD_CC=$(HOSTCC) \
		config_TARGET_CC=$(TARGET_CC) \
		config_TARGET_CFLAGS="$(TARGET_CFLAGS)" \
		config_TARGET_LINK=$(TARGET_CC) \
		BUILD_CC=$(HOSTCC) \
		ac_cv_file__dev_zero=yes \
		ac_cv_func_setpgrp_void=yes \
		apr_cv_tcp_nodelay_with_cork=yes \
		ac_cv_file_dbd_apr_dbd_mysql_c=no \
		ac_cv_file___dev_urandom_=yes \
		ac_cv_va_copy=C99 \
		ac_cv_sizeof_ssize_t=4 \
		ac_cv_func_malloc_0_nonnull=yes \
		apr_cv_mutex_recursive=yes \
		ac_cv_func_pthread_rwlock_init=yes \
		apr_cv_type_rwlock_t=yes \
		ac_cv_pcap_where_inc=$(STAGING_DIR)/include \
		ac_cv_pcap_where_lib=$(STAGING_DIR)/lib \
		with_ssl=$(STAGING_DIR) \
		ac_cv_file__dev_urandom=yes \
		ac_cv_dev_urandom=yes \
		ac_cv_file__dev_random=no \
		apr_cv_process_shared_works=yes \
		apr_cv_mutex_robust_shared=no \
		./configure \
		--target=$(GNU_TARGET_NAME) \
		--host=$(GNU_TARGET_NAME) \
		--build=$(GNU_HOST_NAME) \
		--prefix=$(FREESWITCH_INSTALL_DIR) \
	)
	cp -f package/freeswitch/freeswitch-modules.conf $(FREESWITCH_DIR)/modules.conf
	touch $@

$(FREESWITCH_DIR)/$(FREESWITCH_BINARY): $(FREESWITCH_DIR)/.configured
	$(MAKE1) -C $(FREESWITCH_DIR) $(TARGET_CONFIGURE_OPTS)

$(TARGET_DIR)/$(FREESWITCH_TARGET_BINARY): $(FREESWITCH_DIR)/$(FREESWITCH_BINARY)
	$(MAKE1) -C $(FREESWITCH_DIR) $(TARGET_CONFIGURE_OPTS) DESTDIR=$(TARGET_DIR) install
ifeq ($(strip $(BR2_PACKAGE_FREESWITCH_SOUNDS)),y)
	$(MAKE1) -C $(FREESWITCH_DIR) $(TARGET_CONFIGURE_OPTS) DESTDIR=$(TARGET_DIR) \
	sounds-install moh-install
endif
	rm -rf $(TARGET_DIR)/$(FREESWITCH_INSTALL_DIR)/mod/*.la \
		$(TARGET_DIR)/$(FREESWITCH_INSTALL_DIR)/mod/*.a $(TARGET_DIR)/$(FREESWITCH_INSTALL_DIR)/lib/*.la \
		$(TARGET_DIR)/$(FREESWITCH_INSTALL_DIR)/lib/*.a $(TARGET_DIR)/$(FREESWITCH_INSTALL_DIR)/include
	# Hack for now
	cp -a $(TARGET_DIR)/$(FREESWITCH_INSTALL_DIR)/lib/* $(TARGET_DIR)/usr/lib/
	# Links to some FS bins in PATH
	ln -sf $(FREESWITCH_INSTALL_DIR)/bin/freeswitch $(TARGET_DIR)/usr/sbin/freeswitch
	ln -sf $(FREESWITCH_INSTALL_DIR)/bin/fs_cli $(TARGET_DIR)/usr/sbin/fs_cli
	$(INSTALL) -D -m 0755 package/freeswitch/freeswitch.init $(TARGET_DIR)/etc/init.d/freeswitch
	ln -sf ../../init.d/freeswitch $(TARGET_DIR)/etc/runlevels/default/S60freeswitch
	ln -sf ../../init.d/freeswitch $(TARGET_DIR)/etc/runlevels/default/K00freeswitch

freeswitch: $(TARGET_DIR)/$(FREESWITCH_TARGET_BINARY)

freeswitch-source: $(DL_DIR)/$(FREESWITCH_SOURCE)

freeswitch-unpack: $(FREESWITCH_DIR)/.source

freeswitch-clean:
	-$(MAKE1) -C $(FREESWITCH_DIR) clean
	rm -rf $(TARGET_DIR)/$(FREESWITCH_INSTALL_DIR) $(TARGET_DIR)/usr/lib/libfreeswitch.so* \
		$(TARGET_DIR)/usr/sbin/freeswitch $(TARGET_DIR)/usr/sbin/fs_cli \
		$(TARGET_DIR)/etc/init.d/freeswitch
	rm -f $(TARGET_DIR)/etc/runlevels/default/S60freeswitch
	rm -f $(TARGET_DIR)/etc/runlevels/default/K00freeswitch

freeswitch-dirclean:
	rm -rf $(FREESWITCH_DIR)

#############################################################
#
# Toplevel Makefile options
#
#############################################################
ifeq ($(strip $(BR2_PACKAGE_FREESWITCH)),y)
TARGETS+=freeswitch
endif
