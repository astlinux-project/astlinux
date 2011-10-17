#############################################################
#
# ngrep
#
#############################################################

NGREP_VERSION = 1.45
NGREP_SOURCE = ngrep-$(NGREP_VERSION).tar.bz2
NGREP_SITE = http://$(BR2_SOURCEFORGE_MIRROR).dl.sourceforge.net/sourceforge/ngrep
NGREP_DEPENDENCIES = libpcap

NGREP_UNINSTALL_STAGING_OPT = --version

NGREP_CONF_OPT += \
	--with-pcap-includes=$(LIBPCAP_DIR) \
	--enable-ipv6

NGREP_MAKE_OPT = CC='$(TARGET_CC)' MAKEFLAGS="" -C $(@D)

define NGREP_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/ngrep $(TARGET_DIR)/usr/sbin/ngrep
endef

define NGREP_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/ngrep
endef

$(eval $(call AUTOTARGETS,package,ngrep))
