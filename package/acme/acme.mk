################################################################################
#
# acme
#
################################################################################

ACME_VERSION = 2.9.0
ACME_SOURCE = acme.sh-$(ACME_VERSION).tar.gz
ACME_SITE = https://github.com/acmesh-official/acme.sh/archive/$(ACME_VERSION)

ACME_DNSAPI_FILES = \
	dns_acmedns.sh dns_acmeproxy.sh dns_active24.sh dns_ad.sh dns_ali.sh \
	dns_autodns.sh dns_aws.sh dns_azure.sh dns_cf.sh \
	dns_clouddns.sh dns_cloudns.sh dns_cn.sh dns_conoha.sh dns_cx.sh \
	dns_da.sh dns_ddnss.sh dns_desec.sh dns_df.sh dns_dgon.sh \
	dns_dnsimple.sh dns_doapi.sh dns_domeneshop.sh dns_dp.sh dns_dpi.sh \
	dns_dreamhost.sh dns_duckdns.sh dns_durabledns.sh dns_dyn.sh dns_dynu.sh \
	dns_easydns.sh dns_euserv.sh dns_exoscale.sh dns_freedns.sh dns_gandi_livedns.sh \
	dns_gd.sh dns_gdnsdk.sh dns_he.sh dns_hexonet.sh dns_hostingde.sh dns_infoblox.sh \
	dns_internetbs.sh dns_ipv64.sh dns_jd.sh dns_kinghost.sh dns_leaseweb.sh dns_linode.sh \
	dns_linode_v4.sh dns_loopia.sh dns_lua.sh dns_me.sh dns_miab.sh \
	dns_misaka.sh dns_mydnsjp.sh dns_namecheap.sh dns_namecom.sh dns_namesilo.sh \
	dns_nederhost.sh dns_neodigit.sh dns_netcup.sh dns_nic.sh dns_nsd.sh \
	dns_nsone.sh dns_nw.sh dns_one.sh dns_online.sh dns_openprovider.sh \
	dns_opnsense.sh dns_ovh.sh dns_pdns.sh dns_pointhq.sh dns_rackspace.sh \
	dns_rcode0.sh dns_regru.sh dns_schlundtech.sh dns_selectel.sh dns_servercow.sh \
	dns_tele3.sh dns_ultra.sh dns_unoeuro.sh dns_variomedia.sh dns_vscale.sh \
	dns_vultr.sh dns_yandex.sh dns_zilore.sh dns_zone.sh dns_zonomi.sh

define ACME_DNSAPI_INSTALL_FILES
	mkdir -p $(TARGET_DIR)/stat/etc/acme/dnsapi
	cd $(@D)/dnsapi && \
		$(TAR) cf install.tar $(sort $(ACME_DNSAPI_FILES)) && \
		$(TAR) xf install.tar -C $(TARGET_DIR)/stat/etc/acme/dnsapi
endef

define ACME_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 0644 package/acme/deploy/astlinux.sh $(TARGET_DIR)/stat/etc/acme/deploy/astlinux.sh
	$(INSTALL) -D -m 0644 package/acme/deploy/custom.sh $(TARGET_DIR)/stat/etc/acme/deploy/custom.sh
	$(INSTALL) -D -m 0644 $(@D)/deploy/ssh.sh $(TARGET_DIR)/stat/etc/acme/deploy/ssh.sh
	$(INSTALL) -D -m 0644 $(@D)/notify/mail.sh $(TARGET_DIR)/stat/etc/acme/notify/mail.sh
	$(INSTALL) -D -m 0755 package/acme/acme-client.sh $(TARGET_DIR)/usr/sbin/acme-client
	$(INSTALL) -D -m 0755 $(@D)/acme.sh $(TARGET_DIR)/stat/etc/acme/acme.sh
	$(ACME_DNSAPI_INSTALL_FILES)
	ln -sf /mnt/kd/acme $(TARGET_DIR)/etc/acme
	# Make the scripts non-executable, they are sourced by acme.sh
	find $(TARGET_DIR)/stat/etc/acme/dnsapi/ -name '*.sh' -print0 | xargs -0 chmod 644
endef

define ACME_UNINSTALL_TARGET_CMDS
	rm -f  $(TARGET_DIR)/usr/sbin/acme-client
	rm -f  $(TARGET_DIR)/etc/acme
	rm -rf $(TARGET_DIR)/stat/etc/acme
endef

$(eval $(call GENTARGETS,package,acme))
