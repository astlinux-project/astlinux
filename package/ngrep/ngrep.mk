#############################################################
#
# ngrep
#
#############################################################

NGREP_VERSION = 1.45
NGREP_SOURCE = ngrep-$(NGREP_VERSION).tar.bz2
NGREP_SITE = http://$(BR2_SOURCEFORGE_MIRROR).dl.sourceforge.net/sourceforge/ngrep
NGREP_DEPENDENCIES = libpcap pcre

NGREP_UNINSTALL_STAGING_OPT = --version

NGREP_CONF_OPT += \
	--with-pcap-includes=$(STAGING_DIR)/usr/include/pcap \
	--with-pcre=$(STAGING_DIR)/usr \
	--enable-pcre \
	--enable-ipv6

define NGREP_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/ngrep $(TARGET_DIR)/usr/bin/ngrep
endef

define NGREP_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/ngrep
endef

$(eval $(call AUTOTARGETS,package,ngrep))
