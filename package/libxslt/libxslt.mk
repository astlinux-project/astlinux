#############################################################
#
# libxslt
#
#############################################################

LIBXSLT_VERSION = 1.1.35
LIBXSLT_SOURCE = libxslt-$(LIBXSLT_VERSION).tar.xz
LIBXSLT_SITE = https://download.gnome.org/sources/libxslt/1.1
LIBXSLT_INSTALL_STAGING = YES

LIBXSLT_DEPENDENCIES = host-pkg-config libxml2

# Keep 'xsltproc' from being called while installing docs
LIBXSLT_CONF_ENV = \
	ac_cv_path_XSLTPROC=true

LIBXSLT_CONF_OPT = \
	--with-gnu-ld \
	--without-debug \
	--without-python \
	--with-libxml-prefix=$(STAGING_DIR)/usr

# If we have enabled libgcrypt then use it, else disable crypto support.
ifeq ($(BR2_PACKAGE_LIBGCRYPT),y)
LIBXSLT_DEPENDENCIES += libgcrypt
LIBXSLT_CONF_ENV += LIBGCRYPT_CONFIG=$(STAGING_DIR)/usr/bin/libgcrypt-config
else
LIBXSLT_CONF_OPT += --without-crypto
endif

HOST_LIBXSLT_CONF_OPT = --without-debug --without-python --without-crypto

HOST_LIBXSLT_DEPENDENCIES = host-pkg-config host-libxml2

define LIBXSLT_XSLT_CONFIG_FIXUP
	$(SED) "s,^prefix=.*,prefix=\'$(STAGING_DIR)/usr\',g" $(STAGING_DIR)/usr/bin/xslt-config
	$(SED) "s,^exec_prefix=.*,exec_prefix=\'$(STAGING_DIR)/usr\',g" $(STAGING_DIR)/usr/bin/xslt-config
	$(SED) "s,^includedir=.*,includedir=\'$(STAGING_DIR)/usr/include\',g" $(STAGING_DIR)/usr/bin/xslt-config
endef

LIBXSLT_POST_INSTALL_STAGING_HOOKS += LIBXSLT_XSLT_CONFIG_FIXUP

define LIBXSLT_REMOVE_CONFIG_SCRIPTS
	rm -f $(TARGET_DIR)/usr/bin/xslt-config
	rm -f $(TARGET_DIR)/usr/lib/xsltConf.sh
endef

LIBXSLT_POST_INSTALL_TARGET_HOOKS += LIBXSLT_REMOVE_CONFIG_SCRIPTS

$(eval $(call AUTOTARGETS,package,libxslt))
$(eval $(call AUTOTARGETS,package,libxslt,host))
