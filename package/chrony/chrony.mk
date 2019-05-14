################################################################################
#
# chrony
#
################################################################################

CHRONY_VERSION = 3.5
CHRONY_SITE = https://download.tuxfamily.org/chrony
CHRONY_DEPENDENCIES = libcap

CHRONY_CONF_OPT = \
	--host-system=Linux \
	--host-release="" \
	--host-machine=$(BR2_ARCH) \
	--prefix=/usr \
	--with-sendmail=/usr/sbin/sendmail \
	--with-user=ntp \
	--disable-ipv6 \
	--disable-phc \
	--without-seccomp \
	--without-tomcrypt

ifeq ($(BR2_PACKAGE_LIBNSS),y)
CHRONY_DEPENDENCIES += host-pkgconf libnss
else
CHRONY_CONF_OPT += --without-nss
endif

ifeq ($(BR2_PACKAGE_READLINE),y)
CHRONY_DEPENDENCIES += readline
CHRONY_CONF_OPT += --without-editline
else ifeq ($(BR2_PACKAGE_LIBEDIT),y)
CHRONY_DEPENDENCIES += libedit
CHRONY_CONF_OPT += --without-readline
else
CHRONY_CONF_OPT += --without-editline --without-readline
endif

# If pps-tools is available, build it before so the package can use it
# (HAVE_SYS_TIMEPPS_H).
ifeq ($(BR2_PACKAGE_PPS_TOOLS),y)
CHRONY_DEPENDENCIES += pps-tools
else
CHRONY_CONF_OPT += --disable-pps
endif

define CHRONY_CONFIGURE_CMDS
	cd $(@D) && $(TARGET_CONFIGURE_OPTS) ./configure $(CHRONY_CONF_OPT)
endef

define CHRONY_BUILD_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) -C $(@D)
endef

define CHRONY_INSTALL_TARGET_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) -C $(@D) DESTDIR="$(TARGET_DIR)" install
	$(INSTALL) -D -m 755 package/chrony/ntpd.init $(TARGET_DIR)/etc/init.d/ntpd
	ln -sf /tmp/etc/chrony.conf $(TARGET_DIR)/etc/chrony.conf
endef

define CHRONY_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/chronyd
	rm -f $(TARGET_DIR)/usr/bin/chronyc
	rm -f $(TARGET_DIR)/etc/init.d/ntpd
	rm -f $(TARGET_DIR)/etc/chrony.conf
endef

$(eval $(call GENTARGETS,package,chrony))
