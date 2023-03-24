#############################################################
#
# dmidecode
#
#############################################################

DMIDECODE_VERSION = 3.5
DMIDECODE_SITE = https://download-mirror.savannah.gnu.org/releases/dmidecode
DMIDECODE_SOURCE = dmidecode-$(DMIDECODE_VERSION).tar.xz

define DMIDECODE_BUILD_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) -C $(@D) $(TARGET_CONFIGURE_OPTS)
endef

define DMIDECODE_INSTALL_TARGET_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) -C $(@D) prefix=/usr DESTDIR=$(TARGET_DIR) install
endef

$(eval $(call GENTARGETS,package,dmidecode))
