#############################################################
#
# rp-pppoe
#
#############################################################

RP_PPPOE_VERSION = 3.11
RP_PPPOE_SITE = http://www.roaringpenguin.com/files/download
RP_PPPOE_SUBDIR = src
RP_PPPOE_DEPENDENCIES = pppd
RP_PPPOE_TARGET_FILES = pppoe pppoe-server pppoe-relay pppoe-sniff
RP_PPPOE_TARGET_SCRIPTS = pppoe-connect pppoe-start pppoe-stop pppoe-status
RP_PPPOE_MAKE_OPT = PLUGIN_DIR=/usr/lib/pppd/$(PPPD_VERSION)
RP_PPPOE_CONF_ENV = \
	ac_cv_func_setvbuf_reversed=no \
	ac_cv_linux_kernel_pppoe=yes \
	rpppoe_cv_pack_bitfields=rev \
	PPPD_H=$(PPPD_DIR)/pppd/pppd.h

RP_PPPOE_CONF_OPT = \
	--disable-debugging

define RP_PPPOE_INSTALL_TARGET_CMDS
	for ff in $(RP_PPPOE_TARGET_FILES); do \
		$(INSTALL) -m 0755 $(@D)/src/$$ff $(TARGET_DIR)/usr/sbin/$$ff; \
	done
	for ff in $(RP_PPPOE_TARGET_SCRIPTS); do \
		$(INSTALL) -m 0755 $(@D)/scripts/$$ff $(TARGET_DIR)/usr/sbin/$$ff; \
	done
	$(INSTALL) -m 0755 package/rp-pppoe/pppoe-restart $(TARGET_DIR)/usr/sbin/pppoe-restart
	# work-around so that we don't clobber the existing link
	$(SED) 's@ln -s /etc/ppp/resolv.conf /etc/resolv.conf@: # ln -sf /etc/ppp/resolv.conf /tmp/etc/resolv.conf@' \
		-e 's@rm -f /etc/resolv.conf@# &@' \
		$(TARGET_DIR)/usr/sbin/pppoe-connect
endef

define RP_PPPOE_UNINSTALL_TARGET_CMDS
	for ff in $(RP_PPPOE_TARGET_FILES); do \
		rm -f $(TARGET_DIR)/usr/sbin/$$ff; \
	done
	for ff in $(RP_PPPOE_TARGET_SCRIPTS); do \
		rm -f $(TARGET_DIR)/usr/sbin/$$ff; \
	done
	rm -f $(TARGET_DIR)/usr/sbin/pppoe-restart
endef

$(eval $(call AUTOTARGETS,package,rp-pppoe))
