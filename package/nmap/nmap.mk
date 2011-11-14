#############################################################
#
# nmap 
#
#############################################################

NMAP_VER:=4.76
NMAP_DIR:=$(BUILD_DIR)/nmap-$(NMAP_VER)
NMAP_SITE:=http://nmap.org/dist
NMAP_SOURCE:=nmap-$(NMAP_VER).tar.bz2
NMAP_CAT:=bzcat

$(DL_DIR)/$(NMAP_SOURCE):
	 $(WGET) -P $(DL_DIR) $(NMAP_SITE)/$(NMAP_SOURCE)

nmap-source: $(NMAP_DIR)/.unpacked

$(NMAP_DIR)/.unpacked: $(DL_DIR)/$(NMAP_SOURCE)
	$(NMAP_CAT) $(DL_DIR)/$(NMAP_SOURCE) | tar -C $(BUILD_DIR) $(TAR_OPTIONS) -
	toolchain/patch-kernel.sh $(NMAP_DIR) package/nmap/ nmap\*.patch
	touch $@

$(NMAP_DIR)/.configured: $(NMAP_DIR)/.unpacked | libpcap openssl
	( \
		cd $(NMAP_DIR) ; \
		autoconf ; \
		BUILD_CC=$(TARGET_CC) HOSTCC="$(HOSTCC)" \
		$(TARGET_CONFIGURE_OPTS) \
		CFLAGS='$(TARGET_CFLAGS)' \
		ac_cv_linux_vers=2 \
		./configure \
		--target=$(GNU_TARGET_NAME) \
		--host=$(GNU_TARGET_NAME) \
		--build=$(GNU_HOST_NAME) \
		--libdir=$(STAGING_DIR)/lib \
		--prefix=/usr \
		--includedir=$(STAGING_DIR)/include \
		--with-liblua=included \
		--without-zenmap \
		--with-libpcap=included \
		--with-pcap=linux \
	)
	touch $@

$(NMAP_DIR)/nmap: $(NMAP_DIR)/.configured
	$(MAKE) CC="$(TARGET_CC)" -C $(NMAP_DIR)
	
$(TARGET_DIR)/usr/sbin/nmap: $(NMAP_DIR)/nmap
	$(INSTALL) -D -m 0755 $(NMAP_DIR)/nmap $(TARGET_DIR)/usr/sbin/nmap
ifeq ($(strip $(BR2_PACKAGE_NMAP_DB)),y)
	mkdir -p $(TARGET_DIR)/usr/share/nmap
	$(INSTALL) -D -m 0644 $(NMAP_DIR)/nmap-os-db $(NMAP_DIR)/nmap-mac-prefixes \
	$(NMAP_DIR)/nmap-services $(NMAP_DIR)/nmap-service-probes \
	$(NMAP_DIR)/nmap-protocols $(NMAP_DIR)/nmap-rpc $(TARGET_DIR)/usr/share/nmap
endif

nmap: $(TARGET_DIR)/usr/sbin/nmap

nmap-clean:
	rm -f $(TARGET_DIR)/usr/sbin/nmap
	rm -rf $(TARGET_DIR)/usr/share/nmap
	-$(MAKE) -C $(NMAP_DIR) clean

nmap-dirclean:
	rm -rf $(NMAP_DIR)

#############################################################
#
# Toplevel Makefile options
#
#############################################################
ifeq ($(strip $(BR2_PACKAGE_NMAP)),y)
TARGETS+=nmap
endif
