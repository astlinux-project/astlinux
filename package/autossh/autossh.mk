################################################################################
#
# autossh
#
################################################################################

AUTOSSH_VERSION = 1.4g
AUTOSSH_SITE = https://www.harding.motd.ca/autossh
AUTOSSH_SOURCE = autossh-$(AUTOSSH_VERSION).tgz

# Fix AC_ARG_WITH code generation for --with-ssh
AUTOSSH_AUTORECONF = YES

AUTOSSH_CONF_OPT = \
	--with-ssh=/usr/bin/ssh

define AUTOSSH_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/autossh $(TARGET_DIR)/usr/bin/autossh
endef

$(eval $(call AUTOTARGETS,package,autossh))
