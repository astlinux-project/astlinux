################################################################################
#
# dahdi-tools
#
################################################################################

DAHDI_TOOLS_VERSION = 3.2.0
DAHDI_TOOLS_SITE = http://downloads.asterisk.org/pub/telephony/dahdi-tools/releases

DAHDI_TOOLS_DEPENDENCIES = libusb newt dahdi-linux

DAHDI_TOOLS_INSTALL_STAGING = YES
DAHDI_TOOLS_AUTORECONF = YES

# Buildroot globally exports PERL with the value it has on the host, so we need
# to override it with the location where it will be on the target.
#DAHDI_TOOLS_CONF_ENV = PERL=/usr/bin/perl

DAHDI_TOOLS_CONF_OPT = \
	--without-pcap \
	--without-libusbx \
	--without-selinux \
	--without-ppp \
	--with-dahdi=$(STAGING_DIR)/usr \
	--with-usb=$(STAGING_DIR)/usr \
	--with-newt=$(STAGING_DIR)/usr \
	--with-perllib=/usr/local/share/perl

define DAHDI_TOOLS_POST_INSTALL
	rm -rf $(TARGET_DIR)/usr/lib/dracut
	$(INSTALL) -m 0755 -D package/dahdi-tools/dahdi.init $(TARGET_DIR)/etc/init.d/dahdi
	mv $(TARGET_DIR)/etc/dahdi $(TARGET_DIR)/stat/etc/dahdi
	ln -s /tmp/etc/dahdi $(TARGET_DIR)/etc/dahdi
	ln -sf ../../init.d/dahdi $(TARGET_DIR)/etc/runlevels/default/S02dahdi
	ln -sf ../../init.d/dahdi $(TARGET_DIR)/etc/runlevels/default/K93dahdi
endef
DAHDI_TOOLS_POST_INSTALL_TARGET_HOOKS = DAHDI_TOOLS_POST_INSTALL

define DAHDI_TOOLS_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/etc/init.d/dahdi
	rm -rf $(TARGET_DIR)/stat/etc/dahdi
	rm -f $(TARGET_DIR)/etc/dahdi
	rm -f $(TARGET_DIR)/etc/runlevels/default/S02dahdi
	rm -f $(TARGET_DIR)/etc/runlevels/default/K93dahdi
endef

$(eval $(call AUTOTARGETS,package,dahdi-tools))
