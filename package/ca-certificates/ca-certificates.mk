################################################################################
#
# ca-certificates
#
################################################################################

CA_CERTIFICATES_VERSION = 2021-01-19
CA_CERTIFICATES_SOURCE = cacert-$(CA_CERTIFICATES_VERSION).pem
CA_CERTIFICATES_SITE = https://curl.haxx.se/ca

define CA_CERTIFICATES_EXTRACT_CMDS
	cp $(DL_DIR)/$(CA_CERTIFICATES_SOURCE) $(@D)/cacert.pem
endef

define CA_CERTIFICATES_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -d $(TARGET_DIR)/usr/lib/ssl/certs
	$(INSTALL) -m 0755 -d $(TARGET_DIR)/etc/ssl/certs
	$(INSTALL) -m 0444 -D $(@D)/cacert.pem $(TARGET_DIR)/usr/share/ca-certificates/ca-bundle.crt
	ln -sf /usr/share/ca-certificates/ca-bundle.crt $(TARGET_DIR)/usr/lib/ssl/certs/ca-bundle.crt
	ln -sf /usr/share/ca-certificates/ca-bundle.crt $(TARGET_DIR)/usr/lib/ssl/cert.pem
	ln -sf /usr/share/ca-certificates/ca-bundle.crt $(TARGET_DIR)/etc/ssl/certs/ca-certificates.crt
endef

define CA_CERTIFICATES_UNINSTALL_TARGET_CMDS
	rm -f  $(TARGET_DIR)/usr/lib/ssl/certs/ca-bundle.crt
	rm -f  $(TARGET_DIR)/usr/lib/ssl/cert.pem
	rm -f  $(TARGET_DIR)/etc/ssl/certs/ca-certificates.crt
	rm -rf $(TARGET_DIR)/usr/share/ca-certificates
endef

$(eval $(call GENTARGETS,package,ca-certificates))
