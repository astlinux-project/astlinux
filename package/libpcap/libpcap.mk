#############################################################
#
# libpcap
#
#############################################################

LIBPCAP_VERSION = 1.10.3
LIBPCAP_SITE = https://www.tcpdump.org/release
LIBPCAP_SOURCE = libpcap-$(LIBPCAP_VERSION).tar.gz
LIBPCAP_INSTALL_STAGING = YES
LIBPCAP_DEPENDENCIES = zlib host-flex host-bison host-pkg-config

LIBPCAP_CONF_ENV = \
	ac_cv_header_linux_wireless_h=yes \
	CFLAGS="$(LIBPCAP_CFLAGS)"

LIBPCAP_CFLAGS = $(TARGET_CFLAGS)

LIBPCAP_CONF_OPT = \
	--disable-yydebug \
	--with-pcap=linux \
	--without-dag \
	--without-dpdk \
	--disable-dbus \
	--disable-bluetooth

# Omit -rpath from pcap-config output
define LIBPCAP_CONFIG_REMOVE_RPATH
	$(SED) 's/^V_RPATH_OPT=.*/V_RPATH_OPT=""/g' $(@D)/pcap-config
endef
LIBPCAP_POST_BUILD_HOOKS = LIBPCAP_CONFIG_REMOVE_RPATH

ifeq ($(BR2_PACKAGE_LIBUSB),y)
LIBPCAP_CONF_OPT += --enable-usb
LIBPCAP_DEPENDENCIES += libusb
else
LIBPCAP_CONF_OPT += --disable-usb
endif

ifeq ($(BR2_PACKAGE_LIBNL),y)
LIBPCAP_DEPENDENCIES += libnl
LIBPCAP_CONF_OPT += --with-libnl
else
LIBPCAP_CONF_OPT += --without-libnl
endif

define LIBPCAP_STAGING_FIXUP_PCAP_CONFIG
        $(SED) "s,prefix=\"/usr\",prefix=\"$(STAGING_DIR)/usr\",g" $(STAGING_DIR)/usr/bin/pcap-config
endef
LIBPCAP_POST_INSTALL_STAGING_HOOKS += LIBPCAP_STAGING_FIXUP_PCAP_CONFIG

define LIBPCAP_TARGET_REMOVE_PCAP_CONFIG
        rm -f $(TARGET_DIR)/usr/bin/pcap-config
endef
LIBPCAP_POST_INSTALL_TARGET_HOOKS += LIBPCAP_TARGET_REMOVE_PCAP_CONFIG

$(eval $(call AUTOTARGETS,package,libpcap))
