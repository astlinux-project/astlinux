#############################################################
#
# ngrep
#
#############################################################

NGREP_VERSION = 1.48.3
NGREP_SOURCE = ngrep-$(NGREP_VERSION).tar.gz
NGREP_SITE = https://github.com/jpr5/ngrep/archive/v$(NGREP_VERSION)
NGREP_DEPENDENCIES = libpcap pcre2

NGREP_UNINSTALL_STAGING_OPT = --version

NGREP_CONF_ENV = \
	NGREP_PCRE2_CONFIG_SCRIPT="$(STAGING_DIR)/usr/bin/pcre2-config"

NGREP_CONF_OPT = \
	--disable-pcap-restart \
	--disable-tcpkill \
	--with-dropprivs-user=nobody \
	--with-pcap-includes=$(STAGING_DIR)/usr/include \
	--enable-pcre2 \
	--enable-ipv6

define NGREP_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/ngrep $(TARGET_DIR)/usr/bin/ngrep
endef

define NGREP_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/ngrep
endef

$(eval $(call AUTOTARGETS,package,ngrep))
