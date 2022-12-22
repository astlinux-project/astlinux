################################################################################
#
# linux-firmware
#
################################################################################

LINUX_FIRMWARE_VERSION = 20210919
LINUX_FIRMWARE_SOURCE = linux-firmware-$(LINUX_FIRMWARE_VERSION).tar.xz
LINUX_FIRMWARE_SITE = $(BR2_KERNEL_MIRROR)/linux/kernel/firmware
LINUX_FIRMWARE_DEPENDENCIES = linux

##
## Configured for Linux Kernel 5.10
## Use 'modinfo' to determine the 'firmware:' entries
##

## RealTek RTL-8169 Gigabit Ethernet driver
LINUX_FIRMWARE_FILES += \
	rtl_nic/rtl8105e-1.fw \
	rtl_nic/rtl8106e-1.fw \
	rtl_nic/rtl8106e-2.fw \
	rtl_nic/rtl8107e-1.fw \
	rtl_nic/rtl8107e-2.fw \
	rtl_nic/rtl8125a-3.fw \
	rtl_nic/rtl8125b-2.fw \
	rtl_nic/rtl8168d-1.fw \
	rtl_nic/rtl8168d-2.fw \
	rtl_nic/rtl8168e-1.fw \
	rtl_nic/rtl8168e-2.fw \
	rtl_nic/rtl8168e-3.fw \
	rtl_nic/rtl8168f-1.fw \
	rtl_nic/rtl8168f-2.fw \
	rtl_nic/rtl8168fp-3.fw \
	rtl_nic/rtl8168g-2.fw \
	rtl_nic/rtl8168g-3.fw \
	rtl_nic/rtl8168h-1.fw \
	rtl_nic/rtl8168h-2.fw \
	rtl_nic/rtl8402-1.fw \
	rtl_nic/rtl8411-1.fw \
	rtl_nic/rtl8411-2.fw

## Broadcom Tigon3 ethernet driver
LINUX_FIRMWARE_FILES += \
	tigon/tg3_tso5.bin \
	tigon/tg3_tso.bin \
	tigon/tg3.bin

define LINUX_FIRMWARE_INSTALL_FILES
	cd $(@D) && \
		$(TAR) cf install.tar $(sort $(LINUX_FIRMWARE_FILES)) && \
		$(TAR) xf install.tar -C $(TARGET_DIR)/lib/firmware
endef

define LINUX_FIRMWARE_INSTALL_TARGET_CMDS
	mkdir -p $(TARGET_DIR)/lib/firmware
	$(LINUX_FIRMWARE_INSTALL_FILES)
endef

$(eval $(call GENTARGETS,package,linux-firmware))
