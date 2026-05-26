#############################################################
#
# sipgrep
#
#############################################################

SIPGREP_VERSION = 2.2.0
SIPGREP_SOURCE = sipgrep-$(SIPGREP_VERSION).tar.gz
SIPGREP_SITE = https://github.com/sipcapture/sipgrep/archive/$(SIPGREP_VERSION)
SIPGREP_DEPENDENCIES = libpcap pcre2
SIPGREP_AUTORECONF = YES

SIPGREP_UNINSTALL_STAGING_OPT = --version

SIPGREP_CONF_OPT += \
	--enable-ipv6

define SIPGREP_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/src/sipgrep $(TARGET_DIR)/usr/bin/sipgrep
endef

define SIPGREP_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/sipgrep
endef

$(eval $(call AUTOTARGETS,package,sipgrep))
