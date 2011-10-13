/*
 * zaphfc.h - Dahdi driver for HFC-S PCI A based ISDN BRI cards
 *
 * Dahdi port by Jose A. Deniz <odicha@hotmail.com>
 *
 * Copyright (C) 2009 Jose A. Deniz
 * Copyright (C) 2006 headissue GmbH; Jens Wilke
 * Copyright (C) 2004 Daniele Orlandi
 * Copyright (C) 2002, 2003, 2004, Junghanns.NET GmbH
 *
 * Jens Wilke <jw_vzaphfc@headissue.com>
 *
 * Orginal author of this code is
 * Daniele "Vihai" Orlandi <daniele@orlandi.com>
 *
 * Major rewrite of the driver made by
 * Klaus-Peter Junghanns <kpj@junghanns.net>
 *
 * This program is free software and may be modified and
 * distributed under the terms of the GNU Public License.
 *
 */

#ifndef _HFC_ZAPHFC_H
#define _HFC_ZAPHFC_H

#include <asm/io.h>

#define hfc_DRIVER_NAME "vzaphfc"
#define hfc_DRIVER_PREFIX hfc_DRIVER_NAME ": "
#define hfc_DRIVER_DESCR "HFC-S PCI A ISDN"
#define hfc_DRIVER_VERSION "1.42"
#define hfc_DRIVER_STRING hfc_DRIVER_DESCR " (V" hfc_DRIVER_VERSION ")"

#define hfc_MAX_BOARDS 32

#ifndef PCI_DMA_32BIT
#define PCI_DMA_32BIT	0x00000000ffffffffULL
#endif

#ifndef PCI_VENDOR_ID_SITECOM
#define PCI_VENDOR_ID_SITECOM 0x182D
#endif

#ifndef PCI_DEVICE_ID_SITECOM_3069
#define PCI_DEVICE_ID_SITECOM_3069 0x3069
#endif

#define hfc_RESET_DELAY 20

#define hfc_CLKDEL_TE	0x0f	/* CLKDEL in TE mode */
#define hfc_CLKDEL_NT	0x6c	/* CLKDEL in NT mode */

/* PCI memory mapped I/O */

#define hfc_PCI_MEM_SIZE	0x0100
#define hfc_PCI_MWBA		0x80

/* GCI/IOM bus monitor registers */

#define hfc_C_I       0x08
#define hfc_TRxR      0x0C
#define hfc_MON1_D    0x28
#define hfc_MON2_D    0x2C


/* GCI/IOM bus timeslot registers */

#define hfc_B1_SSL    0x80
#define hfc_B2_SSL    0x84
#define hfc_AUX1_SSL  0x88
#define hfc_AUX2_SSL  0x8C
#define hfc_B1_RSL    0x90
#define hfc_B2_RSL    0x94
#define hfc_AUX1_RSL  0x98
#define hfc_AUX2_RSL  0x9C

/* GCI/IOM bus data registers */

#define hfc_B1_D      0xA0
#define hfc_B2_D      0xA4
#define hfc_AUX1_D    0xA8
#define hfc_AUX2_D    0xAC

/* GCI/IOM bus configuration registers */

#define hfc_MST_EMOD  0xB4
#define hfc_MST_MODE	 0xB8
#define hfc_CONNECT 	 0xBC


/* Interrupt and status registers */

#define hfc_FIFO_EN   0x44
#define hfc_TRM       0x48
#define hfc_B_MODE    0x4C
#define hfc_CHIP_ID   0x58
#define hfc_CIRM  	 0x60
#define hfc_CTMT	 0x64
#define hfc_INT_M1  	 0x68
#define hfc_INT_M2  	 0x6C
#define hfc_INT_S1  	 0x78
#define hfc_INT_S2  	 0x7C
#define hfc_STATUS  	 0x70

/* S/T section registers */

#define hfc_STATES  	 0xC0
#define hfc_SCTRL  	 0xC4
#define hfc_SCTRL_E   0xC8
#define hfc_SCTRL_R   0xCC
#define hfc_SQ  	 0xD0
#define hfc_CLKDEL  	 0xDC
#define hfc_B1_REC    0xF0
#define hfc_B1_SEND   0xF0
#define hfc_B2_REC    0xF4
#define hfc_B2_SEND   0xF4
#define hfc_D_REC     0xF8
#define hfc_D_SEND    0xF8
#define hfc_E_REC     0xFC

/* Bits and values in various HFC PCI registers */

/* bits in status register (READ) */
#define hfc_STATUS_PCI_PROC   0x02
#define hfc_STATUS_NBUSY	0x04
#define hfc_STATUS_TIMER_ELAP 0x10
#define hfc_STATUS_STATINT	  0x20
#define hfc_STATUS_FRAMEINT	  0x40
#define hfc_STATUS_ANYINT	  0x80

/* bits in CTMT (Write) */
#define hfc_CTMT_TRANSB1	0x01
#define hfc_CTMT_TRANSB2	0x02
#define hfc_CTMT_TIMER_CLEAR	0x80
#define hfc_CTMT_TIMER_MASK	0x1C
#define hfc_CTMT_TIMER_3_125	(0x01 << 2)
#define hfc_CTMT_TIMER_6_25	(0x02 << 2)
#define hfc_CTMT_TIMER_12_5	(0x03 << 2)
#define hfc_CTMT_TIMER_25	(0x04 << 2)
#define hfc_CTMT_TIMER_50	(0x05 << 2)
#define hfc_CTMT_TIMER_400	(0x06 << 2)
#define hfc_CTMT_TIMER_800	(0x07 << 2)
#define hfc_CTMT_AUTO_TIMER	0x20

/* bits in CIRM (Write) */
#define hfc_CIRM_AUX_MSK    0x07
#define hfc_CIRM_RESET  	  0x08
#define hfc_CIRM_B1_REV     0x40
#define hfc_CIRM_B2_REV     0x80

/* bits in INT_M1 and INT_S1 */
#define hfc_INTS_B1TRANS  0x01
#define hfc_INTS_B2TRANS  0x02
#define hfc_INTS_DTRANS   0x04
#define hfc_INTS_B1REC    0x08
#define hfc_INTS_B2REC    0x10
#define hfc_INTS_DREC     0x20
#define hfc_INTS_L1STATE  0x40
#define hfc_INTS_TIMER    0x80

/* bits in INT_M2 */
#define hfc_M2_PROC_TRANS    0x01
#define hfc_M2_GCI_I_CHG     0x02
#define hfc_M2_GCI_MON_REC   0x04
#define hfc_M2_IRQ_ENABLE    0x08
#define hfc_M2_PMESEL        0x80

/* bits in STATES */
#define hfc_STATES_STATE_MASK     0x0F
#define hfc_STATES_LOAD_STATE    0x10
#define hfc_STATES_ACTIVATE	     0x20
#define hfc_STATES_DO_ACTION     0x40
#define hfc_STATES_NT_G2_G3      0x80

/* bits in HFCD_MST_MODE */
#define hfc_MST_MODE_MASTER	     0x01
#define hfc_MST_MODE_SLAVE         0x00
/* remaining bits are for codecs control */

/* bits in HFCD_SCTRL */
#define hfc_SCTRL_B1_ENA	     0x01
#define hfc_SCTRL_B2_ENA	     0x02
#define hfc_SCTRL_MODE_TE        0x00
#define hfc_SCTRL_MODE_NT        0x04
#define hfc_SCTRL_LOW_PRIO	     0x08
#define hfc_SCTRL_SQ_ENA	     0x10
#define hfc_SCTRL_TEST	     0x20
#define hfc_SCTRL_NONE_CAP	     0x40
#define hfc_SCTRL_PWR_DOWN	     0x80

/* bits in SCTRL_E  */
#define hfc_SCTRL_E_AUTO_AWAKE    0x01
#define hfc_SCTRL_E_DBIT_1        0x04
#define hfc_SCTRL_E_IGNORE_COL    0x08
#define hfc_SCTRL_E_CHG_B1_B2     0x80

/* bits in SCTRL_R  */
#define hfc_SCTRL_R_B1_ENA	     0x01
#define hfc_SCTRL_R_B2_ENA	     0x02

/* bits in FIFO_EN register */
#define hfc_FIFOEN_B1TX   0x01
#define hfc_FIFOEN_B1RX   0x02
#define hfc_FIFOEN_B2TX   0x04
#define hfc_FIFOEN_B2RX   0x08
#define hfc_FIFOEN_DTX    0x10
#define hfc_FIFOEN_DRX    0x20

#define hfc_FIFOEN_B1     (hfc_FIFOEN_B1TX|hfc_FIFOEN_B1RX)
#define hfc_FIFOEN_B2     (hfc_FIFOEN_B2TX|hfc_FIFOEN_B2RX)
#define hfc_FIFOEN_D      (hfc_FIFOEN_DTX|hfc_FIFOEN_DRX)

/* bits in the CONNECT register */
#define	hfc_CONNECT_B1_HFC_from_ST		0x00
#define	hfc_CONNECT_B1_HFC_from_GCI		0x01
#define hfc_CONNECT_B1_ST_from_HFC		0x00
#define hfc_CONNECT_B1_ST_from_GCI		0x02
#define hfc_CONNECT_B1_GCI_from_HFC		0x00
#define hfc_CONNECT_B1_GCI_from_ST		0x04

#define	hfc_CONNECT_B2_HFC_from_ST		0x00
#define	hfc_CONNECT_B2_HFC_from_GCI		0x08
#define hfc_CONNECT_B2_ST_from_HFC		0x00
#define hfc_CONNECT_B2_ST_from_GCI		0x10
#define hfc_CONNECT_B2_GCI_from_HFC		0x00
#define hfc_CONNECT_B2_GCI_from_ST		0x20

/* bits in the TRM register */
#define hfc_TRM_TRANS_INT_00	0x00
#define hfc_TRM_TRANS_INT_01	0x01
#define hfc_TRM_TRANS_INT_10	0x02
#define hfc_TRM_TRANS_INT_11	0x04
#define hfc_TRM_ECHO		0x20
#define hfc_TRM_B1_PLUS_B2	0x40
#define hfc_TRM_IOM_TEST_LOOP	0x80

/* bits in the __SSL and __RSL registers */
#define	hfc_SRSL_STIO		0x40
#define hfc_SRSL_ENABLE		0x80
#define hfc_SRCL_SLOT_MASK	0x1f

/* FIFO memory definitions */

#define hfc_FIFO_SIZE   0x8000

#define hfc_UGLY_FRAMEBUF 0x2000

#define hfc_TX_FIFO_PRELOAD (DAHDI_CHUNKSIZE + 2)
#define hfc_RX_FIFO_PRELOAD 4

/* HDLC STUFF */
#define hfc_HDLC_BUF_LEN	32
/* arbitrary, just the max # of byts we will send to DAHDI per call */


/* NOTE: FIFO pointers are not declared volatile because accesses to the
 *       FIFOs are inherently safe.
 */

#ifdef DEBUG
extern int debug_level;
#endif

struct hfc_chan;

struct hfc_chan_simplex {
	struct hfc_chan_duplex *chan;

	u8 zaptel_buffer[DAHDI_CHUNKSIZE];

	u8 ugly_framebuf[hfc_UGLY_FRAMEBUF];
	int ugly_framebuf_size;
	u16 ugly_framebuf_off;

	void *z1_base, *z2_base;
	void *fifo_base;
	void *z_base;
	u16 z_min;
	u16 z_max;
	u16 fifo_size;

	u8 *f1, *f2;
	u8 f_min;
	u8 f_max;
	u8 f_num;

	unsigned long long frames;
	unsigned long long bytes;
	unsigned long long fifo_full;
	unsigned long long crc;
	unsigned long long fifo_underrun;
};

enum hfc_chan_status {
	free,
	open_framed,
	open_voice,
	sniff_aux,
	loopback,
};

struct hfc_chan_duplex {
	struct hfc_card *card;

	char *name;
	int number;

	enum hfc_chan_status status;
	int open_by_netdev;
	int open_by_zaptel;

	unsigned short protocol;

	spinlock_t lock;

	struct hfc_chan_simplex rx;
	struct hfc_chan_simplex tx;

};

typedef struct hfc_card {
	int cardnum;
	struct pci_dev *pcidev;
	struct dahdi_hfc *ztdev;
	struct proc_dir_entry *proc_dir;
	char proc_dir_name[32];

	struct proc_dir_entry *proc_info;
	struct proc_dir_entry *proc_fifos;
	struct proc_dir_entry *proc_bufs;

	unsigned long io_bus_mem;
	void __iomem *io_mem;

	dma_addr_t fifo_bus_mem;
	void *fifo_mem;
	void *fifos;

	int nt_mode;
	int sync_loss_reported;
	int late_irqs;

	u8 l1_state;
	int fifo_suspended;
	int ignore_first_timer_interrupt;

	struct {
		u8 m1;
		u8 m2;
		u8 fifo_en;
		u8 trm;
		u8 connect;
		u8 sctrl;
		u8 sctrl_r;
		u8 sctrl_e;
		u8 ctmt;
		u8 cirm;
	} regs;

	struct hfc_chan_duplex chans[3];
	int echo_enabled;



	int debug_event;

    spinlock_t lock;
    unsigned int irq;
    unsigned int iomem;
    int ticks;
    int clicks;
    unsigned char *pci_io;
    void *fifomem;		/* start of the shared mem */

    unsigned int pcibus;
    unsigned int pcidevfn;

    int	drecinframe;

    unsigned char cardno;
    struct hfc_card *next;

} hfc_card;

typedef struct dahdi_hfc {
    unsigned int usecount;
    struct dahdi_span span;
    struct dahdi_chan chans[3];
    struct dahdi_chan *_chans[3];
    struct hfc_card *card;

    /* pointer to the signalling channel for this span */
    struct dahdi_chan *sigchan;
    /* nonzero means we're in the middle of sending an HDLC frame */
    int sigactive;
    /* hdlc_hard_xmit() increments, hdlc_tx_frame() decrements */
    atomic_t hdlc_pending;
    int frames_out;
    int frames_in;

} dahdi_hfc;

static inline struct dahdi_hfc* dahdi_hfc_from_span(struct dahdi_span *span) {
	return container_of(span, struct dahdi_hfc, span);
}

static inline u8 hfc_inb(struct hfc_card *card, int offset)
{
 return readb(card->io_mem + offset);
}

static inline void hfc_outb(struct hfc_card *card, int offset, u8 value)
{
 writeb(value, card->io_mem + offset);
}

#endif
