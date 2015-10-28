#############################################################
#
# ntp
#
#############################################################
NTP_VERSION = 4.2.8p4
NTP_SOURCE = ntp-$(NTP_VERSION).tar.gz
NTP_SITE = --no-check-certificate https://www.eecis.udel.edu/~ntp/ntp_spool/ntp4/ntp-4.2
NTP_DEPENDENCIES = host-bison host-flex host-pkg-config

NTP_CONF_OPT = \
	--with-shared \
	--program-transform-name=s,,, \
	--with-yielding-select=yes \
	--enable-ipv6=no \
	--without-ntpsnmpd

ifeq ($(BR2_PACKAGE_OPENSSL),y)
NTP_CONF_OPT += --with-crypto
NTP_DEPENDENCIES += openssl
else
NTP_CONF_OPT += --without-crypto
endif

NTP_INSTALL_FILES_$(BR2_PACKAGE_NTP_NTP_KEYGEN) += util/ntp-keygen
NTP_INSTALL_FILES_$(BR2_PACKAGE_NTP_NTP_WAIT) += scripts/ntp-wait/ntp-wait
NTP_INSTALL_FILES_$(BR2_PACKAGE_NTP_NTPDATE) += ntpdate/ntpdate
NTP_INSTALL_FILES_$(BR2_PACKAGE_NTP_NTPDC) += ntpdc/ntpdc
NTP_INSTALL_FILES_$(BR2_PACKAGE_NTP_NTPQ) += ntpq/ntpq
NTP_INSTALL_FILES_$(BR2_PACKAGE_NTP_NTPTRACE) += scripts/ntptrace/ntptrace
NTP_INSTALL_FILES_$(BR2_PACKAGE_NTP_SNTP) += sntp/sntp
NTP_INSTALL_FILES_$(BR2_PACKAGE_NTP_TICKADJ) += util/tickadj

define NTP_INSTALL_TARGET_CMDS
	$(if $(BR2_PACKAGE_NTP_NTPD), install -m 755 $(@D)/ntpd/ntpd $(TARGET_DIR)/usr/sbin/ntpd)
	test -z "$(NTP_INSTALL_FILES_y)" || install -m 755 $(addprefix $(@D)/,$(NTP_INSTALL_FILES_y)) $(TARGET_DIR)/usr/bin/
	$(if $(BR2_PACKAGE_NTP_NTPD), install -m 755 package/ntp/ntpd.init $(TARGET_DIR)/etc/init.d/ntpd)
	ln -sf /tmp/etc/ntpd.conf $(TARGET_DIR)/etc/ntpd.conf
endef

define NTP_UNINSTALL_TARGET_CMDS
	rm $(TARGET_DIR)/usr/sbin/ntpd
	rm -f $(addprefix $(TARGET_DIR)/usr/bin/,$(NTP_INSTALL_FILES_y))
	rm $(TARGET_DIR)/etc/init.d/ntpd
endef

$(eval $(call AUTOTARGETS,package,ntp))
