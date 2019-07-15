#############################################################
#
# ipsec-tools
#
#############################################################

IPSEC_TOOLS_VERSION = 0.8.2
IPSEC_TOOLS_SOURCE = ipsec-tools-$(IPSEC_TOOLS_VERSION).tar.bz2
IPSEC_TOOLS_SITE = http://downloads.sourceforge.net/project/ipsec-tools/ipsec-tools/$(IPSEC_TOOLS_VERSION)
IPSEC_TOOLS_INSTALL_STAGING = YES
IPSEC_TOOLS_MAKE = $(MAKE1)
IPSEC_TOOLS_DEPENDENCIES = host-pkg-config openssl host-bison flex host-flex
# we patch configure.ac
IPSEC_TOOLS_AUTORECONF = YES

# configure hardcodes -Werror, so override CFLAGS on make invocation
IPSEC_TOOLS_MAKE_OPT = CFLAGS='$(TARGET_CFLAGS)'

IPSEC_TOOLS_CONF_ENV = ac_cv_va_copy=yes

IPSEC_TOOLS_CONF_OPT = \
	--enable-hybrid \
	--without-libpam \
	--disable-gssapi \
	--localstatedir=/var \
	--with-kernel-headers=$(STAGING_DIR)/usr/include

ifeq ($(BR2_PACKAGE_IPSEC_TOOLS_ADMINPORT),y)
IPSEC_TOOLS_CONF_OPT+= --enable-adminport
else
IPSEC_TOOLS_CONF_OPT+= --disable-adminport
endif

ifeq ($(BR2_PACKAGE_IPSEC_TOOLS_NATT),y)
IPSEC_TOOLS_CONF_OPT+= --enable-natt
else
IPSEC_TOOLS_CONF_OPT+= --disable-natt
endif

ifeq ($(BR2_PACKAGE_IPSEC_TOOLS_FRAG),y)
IPSEC_TOOLS_CONF_OPT+= --enable-frag
else
IPSEC_TOOLS_CONF_OPT+= --disable-frag
endif

ifeq ($(BR2_PACKAGE_IPSEC_TOOLS_STATS),y)
IPSEC_TOOLS_CONF_OPT+= --enable-stats
else
IPSEC_TOOLS_CONF_OPT+= --disable-stats
endif

ifeq ($(BR2_INET_IPV6),y)
IPSEC_TOOLS_CONF_OPT+= --enable-ipv6
else
IPSEC_TOOLS_CONF_OPT+= --disable-ipv6
endif

ifneq ($(BR2_PACKAGE_IPSEC_TOOLS_READLINE),y)
IPSEC_TOOLS_CONF_OPT+= --without-readline
else
IPSEC_TOOLS_DEPENDENCIES += readline
endif

ifeq ($(BR2_PACKAGE_IPSEC_SECCTX_DISABLE),y)
IPSEC_TOOLS_CONF_OPT+= --enable-security-context=no
endif
ifeq ($(BR2_PACKAGE_IPSEC_SECCTX_ENABLE),y)
IPSEC_TOOLS_CONF_OPT+= --enable-security-context=yes
endif
ifeq ($(BR2_PACKAGE_IPSEC_SECCTX_KERNEL),y)
IPSEC_TOOLS_CONF_OPT+= --enable-security-context=kernel
endif

IPSEC_TOOLS_CONF_OPT+= --enable-dpd

define IPSEC_TOOLS_INSTALL_SCRIPT
	$(INSTALL) -D -m 755 package/ipsec-tools/racoon.init $(TARGET_DIR)/etc/init.d/racoon
	$(INSTALL) -D -m 755 package/ipsec-tools/racoon-ipsec $(TARGET_DIR)/usr/sbin/racoon-ipsec
	$(INSTALL) -D -m 755 package/ipsec-tools/ipsec-xauth-up-down.sh $(TARGET_DIR)/usr/sbin/ipsec-xauth-up-down
	ln -sf /tmp/etc/racoon.conf $(TARGET_DIR)/etc/racoon.conf
	ln -sf /tmp/etc/psk.txt $(TARGET_DIR)/etc/psk.txt
endef

IPSEC_TOOLS_POST_INSTALL_TARGET_HOOKS += IPSEC_TOOLS_INSTALL_SCRIPT

$(eval $(call AUTOTARGETS,package,ipsec-tools))
