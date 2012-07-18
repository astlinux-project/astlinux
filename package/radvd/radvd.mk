#############################################################
#
# radvd
#
#############################################################
RADVD_VERSION = 1.9.1
RADVD_SOURCE = radvd-$(RADVD_VERSION).tar.gz
RADVD_SITE = http://www.litech.org/radvd/dist/
RADVD_DEPENDENCIES:=flex libdaemon host-flex host-pkg-config

RADVD_CONF_OPT= \
	--program-prefix=''

define RADVD_INSTALL_INITSCRIPT
	$(INSTALL) -D -m 0755 package/radvd/radvd.init $(TARGET_DIR)/etc/init.d/radvd
	ln -sf /tmp/etc/radvd.conf $(TARGET_DIR)/etc/radvd.conf
endef

RADVD_POST_INSTALL_TARGET_HOOKS += RADVD_INSTALL_INITSCRIPT

$(eval $(call AUTOTARGETS,package,radvd))
