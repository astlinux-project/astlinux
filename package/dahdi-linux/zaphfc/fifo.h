/*
 * fifo.h - Dahdi driver for HFC-S PCI A based ISDN BRI cards
 *
 * Copyright (C) 2004 Daniele Orlandi
 * Copyright (C) 2002, 2003, 2004, Junghanns.NET GmbH
 *
 * Daniele "Vihai" Orlandi <daniele@orlandi.com>
 *
 * Major rewrite of the driver made by
 * Klaus-Peter Junghanns <kpj@junghanns.net>
 *
 * This program is free software and may be modified and
 * distributed under the terms of the GNU Public License.
 *
 */

#ifndef _HFC_FIFO_H
#define _HFC_FIFO_H

#include "zaphfc.h"

static inline u16 *Z1_F1(struct hfc_chan_simplex *chan)
{
	return chan->z1_base + (*chan->f1 * 4);
}

static inline u16 *Z2_F1(struct hfc_chan_simplex *chan)
{
	return chan->z2_base + (*chan->f1 * 4);
}

static inline u16 *Z1_F2(struct hfc_chan_simplex *chan)
{
	return chan->z1_base + (*chan->f2 * 4);
}

static inline u16 *Z2_F2(struct hfc_chan_simplex *chan)
{
	return chan->z2_base + (*chan->f2 * 4);
}

static inline u16 Z_inc(struct hfc_chan_simplex *chan, u16 z, u16 inc)
{
	/*
	 * declared as u32 in order to manage overflows
	 */
	u32 newz = z + inc;
	if (newz > chan->z_max)
		newz -= chan->fifo_size;

	return newz;
}

static inline u8 F_inc(struct hfc_chan_simplex *chan, u8 f, u8 inc)
{
	/*
	 * declared as u16 in order to manage overflows
	 */
	u16 newf = f + inc;
	if (newf > chan->f_max)
		newf -= chan->f_num;

	return newf;
}

static inline u16 hfc_fifo_used_rx(struct hfc_chan_simplex *chan)
{
	return (*Z1_F2(chan) - *Z2_F2(chan) +
			chan->fifo_size) % chan->fifo_size;
}

static inline u16 hfc_fifo_get_frame_size(struct hfc_chan_simplex *chan)
{
 /*
  * This +1 is needed because in frame mode the available bytes are Z2-Z1+1
  * while in transparent mode I wouldn't consider the byte pointed by Z2 to
  * be available, otherwise, the FIFO would always contain one byte, even
  * when Z1==Z2
  */

	return hfc_fifo_used_rx(chan) + 1;
}

static inline u8 hfc_fifo_u8(struct hfc_chan_simplex *chan, u16 z)
{
	return *((u8 *)(chan->z_base + z));
}

static inline u16 hfc_fifo_used_tx(struct hfc_chan_simplex *chan)
{
	return (*Z1_F1(chan) - *Z2_F1(chan) +
			chan->fifo_size) % chan->fifo_size;
}

static inline u16 hfc_fifo_free_rx(struct hfc_chan_simplex *chan)
{
	u16 free_bytes = *Z2_F1(chan) - *Z1_F1(chan);

	if (free_bytes > 0)
		return free_bytes;
	else
		return free_bytes + chan->fifo_size;
}

static inline u16 hfc_fifo_free_tx(struct hfc_chan_simplex *chan)
{
	u16 free_bytes = *Z2_F1(chan) - *Z1_F1(chan);

	if (free_bytes > 0)
		return free_bytes;
	else
		return free_bytes + chan->fifo_size;
}

static inline int hfc_fifo_has_frames(struct hfc_chan_simplex *chan)
{
	return *chan->f1 != *chan->f2;
}

static inline u8 hfc_fifo_used_frames(struct hfc_chan_simplex *chan)
{
	return (*chan->f1 - *chan->f2 + chan->f_num) % chan->f_num;
}

static inline u8 hfc_fifo_free_frames(struct hfc_chan_simplex *chan)
{
	return (*chan->f2 - *chan->f1 + chan->f_num) % chan->f_num;
}

int hfc_fifo_get(struct hfc_chan_simplex *chan, void *data, int size);
void hfc_fifo_put(struct hfc_chan_simplex *chan, void *data, int size);
void hfc_fifo_drop(struct hfc_chan_simplex *chan, int size);
int hfc_fifo_get_frame(struct hfc_chan_simplex *chan, void *data, int max_size);
void hfc_fifo_drop_frame(struct hfc_chan_simplex *chan);
void hfc_fifo_put_frame(struct hfc_chan_simplex *chan, void *data, int size);
void hfc_clear_fifo_rx(struct hfc_chan_simplex *chan);
void hfc_clear_fifo_tx(struct hfc_chan_simplex *chan);

#endif
