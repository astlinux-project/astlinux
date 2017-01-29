################################################################################
#
# sngrep
#
################################################################################

SNGREP_VERSION = 1.4.2
SNGREP_SOURCE = sngrep-$(SNGREP_VERSION).tar.gz
SNGREP_SITE = http://files.astlinux-project.org
#SNGREP_SITE = https://github.com/irontec/sngrep/releases
SNGREP_AUTORECONF = YES
SNGREP_DEPENDENCIES = libpcap ncurses

SNGREP_CONF_OPT = \
	--disable-unicode \
	--enable-ipv6

# openssl and gnutls can't be enabled at the same time.
ifeq ($(BR2_PACKAGE_OPENSSL),y)
SNGREP_DEPENDENCIES += openssl
SNGREP_CONF_OPT += --with-openssl --without-gnutls
# gnutls support also requires libgcrypt
else ifeq ($(BR2_PACKAGE_GNUTLS)$(BR2_PACKAGE_LIBGCRYPT),yy)
SNGREP_DEPENDENCIES += gnutls
SNGREP_CONF_OPT += --with-gnutls --without-openssl
else
SNGREP_CONF_OPT += --without-gnutls --without-openssl
endif

ifeq ($(BR2_PACKAGE_PCRE),y)
SNGREP_DEPENDENCIES += pcre
SNGREP_CONF_OPT += --with-pcre
else
SNGREP_CONF_OPT += --without-pcre
endif

$(eval $(call AUTOTARGETS,package,sngrep))
