################################################################################
#
# lxc
#
################################################################################

LXC_VERSION = 3.2.1
LXC_SITE = https://linuxcontainers.org/downloads/lxc
LXC_DEPENDENCIES = host-pkg-config
LXC_INSTALL_STAGING = YES

LXC_CONF_OPT = \
	--localstatedir=/var \
	--with-runtime-path=/var/run \
	--with-distro=buildroot \
	--disable-apparmor \
	--disable-seccomp \
	--disable-selinux \
	--disable-werror \
	--disable-doc \
	--disable-api-docs \
	--disable-examples \
	--disable-pam \
	$(if $(BR2_PACKAGE_BASH),,--disable-bash)

ifeq ($(BR2_PACKAGE_LIBCAP),y)
LXC_CONF_OPT += --enable-capabilities
LXC_DEPENDENCIES += libcap
else
LXC_CONF_OPT += --disable-capabilities
endif

ifeq ($(BR2_PACKAGE_OPENSSL),y)
LXC_CONF_OPT += --enable-openssl
LXC_DEPENDENCIES += openssl
else
LXC_CONF_OPT += --disable-openssl
endif

$(eval $(call AUTOTARGETS,package,lxc))
