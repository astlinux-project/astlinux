#############################################################
#
# ngrep
#
#############################################################

NGREP_VERSION = 1.47
NGREP_SOURCE = ngrep-$(NGREP_VERSION).tar.gz
NGREP_SITE = https://astlinux-project.org/files
NGREP_DEPENDENCIES = libpcap pcre
NGREP_AUTORECONF = YES

##
## curl -L -o dl/ngrep-1.47.tar.gz https://github.com/jpr5/ngrep/archive/V1_47.tar.gz
## ./scripts/upload-dl-pair dl/ngrep-1.47.tar.gz
##

NGREP_UNINSTALL_STAGING_OPT = --version

NGREP_CONF_OPT = \
	--disable-pcap-restart \
	--disable-tcpkill \
	--with-dropprivs-user=nobody \
	--with-pcap-includes=$(STAGING_DIR)/usr/include/pcap \
	--enable-pcre \
	--enable-ipv6

define NGREP_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/ngrep $(TARGET_DIR)/usr/bin/ngrep
endef

define NGREP_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/ngrep
endef

$(eval $(call AUTOTARGETS,package,ngrep))
