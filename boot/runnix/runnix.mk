#############################################################
#
# runnix
#
#############################################################
RUNNIX_VER:=0.4-7583
RUNNIX_SOURCE:=runnix-$(RUNNIX_VER).tar.gz
RUNNIX_SITE:=http://mirror.astlinux.org/runnix4
RUNNIX_DIR:=$(BUILD_DIR)/runnix-$(RUNNIX_VER)
RUNNIX_CAT:=zcat
RUNFS_DIR:=$(BUILD_DIR)/runfs

RUNNIX_NDEV:=$(patsubst "%",%,$(BR2_TARGET_RUNNIX_NDEV))

$(DL_DIR)/$(RUNNIX_SOURCE):
	$(WGET) -P $(DL_DIR) $(RUNNIX_SITE)/$(RUNNIX_SOURCE)

$(RUNNIX_DIR)/.unpacked: $(DL_DIR)/$(RUNNIX_SOURCE) | host-fdisk
	$(RUNNIX_CAT) $(DL_DIR)/$(RUNNIX_SOURCE) | tar -C $(BUILD_DIR) $(TAR_OPTIONS) -
	touch $(RUNNIX_DIR)/.unpacked

$(RUNFS_DIR)/runnix: $(RUNNIX_DIR)/.unpacked
	mkdir -p $(RUNFS_DIR)
	cp -af $(RUNNIX_DIR)/rootfs_vfat/* $(RUNFS_DIR)
	rm -f $(RUNFS_DIR)/*.sample
ifneq ($(RUNNIX_NDEV),)
 ifneq ($(RUNNIX_NDEV),eth0)
	$(SED) 's/^NDEV="eth[0-9]"$$/NDEV="$(RUNNIX_NDEV)"/' \
		$(RUNFS_DIR)/os/default.conf
 endif
endif
	touch -c $(RUNFS_DIR)/runnix

runnix: $(RUNFS_DIR)/runnix

runfs-dirclean:
	rm -rf $(RUNFS_DIR)

runnix-dirclean:
	rm -rf $(RUNNIX_DIR)

runfs: runnix

#############################################################
#
# Toplevel Makefile options
#
#############################################################
ifeq ($(strip $(BR2_TARGET_RUNNIX)),y)
TARGETS+=runnix
endif

