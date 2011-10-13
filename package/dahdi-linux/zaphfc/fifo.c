/*
 * fifo.c - HFC FIFO management routines
 *
 * Copyright (C) 2006 headissue GmbH; Jens Wilke
 * Copyright (C) 2004 Daniele Orlandi
 * Copyright (C) 2002, 2003, 2004, Junghanns.NET GmbH
 *
 * Original author of this code is
 * Daniele "Vihai" Orlandi <daniele@orlandi.com>
 *
 * This program is free software and may be modified and
 * distributed under the terms of the GNU Public License.
 *
 */

#include <linux/kernel.h>

#include <dahdi/kernel.h>

#include "fifo.h"

static void hfc_fifo_mem_read(struct hfc_chan_simplex *chan,
	int z_start,
	void *data, int size)
{
	int bytes_to_boundary = chan->z_max - z_start + 1;
	if (bytes_to_boundary >= size) {
		memcpy(data,
			chan->z_base + z_start,
			size);
	} else {
		/*
		 * Buffer wrap
		 */
		memcpy(data,
			chan->z_base + z_start,
			bytes_to_boundary);

		memcpy(data + bytes_to_boundary,
			chan->fifo_base,
			size - bytes_to_boundary);
	}
}

static void hfc_fifo_mem_write(struct hfc_chan_simplex *chan,
	void *data, int size)
{
	int bytes_to_boundary = chan->z_max - *Z1_F1(chan) + 1;
	if (bytes_to_boundary >= size) {
		memcpy(chan->z_base + *Z1_F1(chan),
			data,
			size);
	} else {
		/*
		 * FIFO wrap
		 */

		memcpy(chan->z_base + *Z1_F1(chan),
			data,
			bytes_to_boundary);

		memcpy(chan->fifo_base,
			data + bytes_to_boundary,
			size - bytes_to_boundary);
	}
}

int hfc_fifo_get(struct hfc_chan_simplex *chan,
		void *data, int size)
{
	int available_bytes;

	/*
	 * Some useless statistic
	 */
	chan->bytes += size;

	available_bytes = hfc_fifo_used_rx(chan);

	if (available_bytes < size && !chan->fifo_underrun++) {
		/*
		 * print the warning only once
		 */
		printk(KERN_WARNING hfc_DRIVER_PREFIX
			"card %d: "
			"chan %s: "
			"RX FIFO not enough (%d) bytes to receive!\n",
			chan->chan->card->cardnum,
			chan->chan->name,
			available_bytes);
		return -1;
	}

	hfc_fifo_mem_read(chan, *Z2_F2(chan), data, size);
	*Z2_F2(chan) = Z_inc(chan, *Z2_F2(chan), size);
	return available_bytes - size;
}

void hfc_fifo_put(struct hfc_chan_simplex *chan,
			void *data, int size)
{
	struct hfc_card *card = chan->chan->card;
	int used_bytes = hfc_fifo_used_tx(chan);
	int free_bytes = hfc_fifo_free_tx(chan);

	if (!used_bytes && !chan->fifo_underrun++) {
		/*
		 * print warning only once, to make timing not worse
		 */
		printk(KERN_WARNING hfc_DRIVER_PREFIX
			"card %d: "
			"chan %s: "
			"TX FIFO has become empty\n",
			card->cardnum,
			chan->chan->name);
	}
	if (free_bytes < size) {
		printk(KERN_CRIT hfc_DRIVER_PREFIX
			"card %d: "
			"chan %s: "
			"TX FIFO full!\n",
			chan->chan->card->cardnum,
			chan->chan->name);
		chan->fifo_full++;
		hfc_clear_fifo_tx(chan);
	}

	hfc_fifo_mem_write(chan, data, size);
	chan->bytes += size;
	*Z1_F1(chan) = Z_inc(chan, *Z1_F1(chan), size);
}

int hfc_fifo_get_frame(struct hfc_chan_simplex *chan, void *data, int max_size)
{
	int frame_size;
	u16 newz2 ;

	if (*chan->f1 == *chan->f2) {
		/*
		 * nothing received, strange uh?
		 */
		printk(KERN_WARNING hfc_DRIVER_PREFIX
			"card %d: "
			"chan %s: "
			"get_frame called with no frame in FIFO.\n",
			chan->chan->card->cardnum,
			chan->chan->name);

		return -1;
	}

	/*
	 * frame_size includes CRC+CRC+STAT
	 */
	frame_size = hfc_fifo_get_frame_size(chan);

#ifdef DEBUG
	if (debug_level == 3) {
		printk(KERN_DEBUG hfc_DRIVER_PREFIX
			"card %d: "
			"chan %s: "
			"RX len %2d: ",
			chan->chan->card->cardnum,
			chan->chan->name,
			frame_size);
	} else if (debug_level >= 4) {
		printk(KERN_DEBUG hfc_DRIVER_PREFIX
			"card %d: "
			"chan %s: "
			"RX (f1=%02x, f2=%02x, z1=%04x, z2=%04x) len %2d: ",
			chan->chan->card->cardnum,
			chan->chan->name,
			*chan->f1, *chan->f2, *Z1_F2(chan), *Z2_F2(chan),
			frame_size);
	}

	if (debug_level >= 3) {
		int i;
		for (i = 0; i < frame_size; i++) {
			printk("%02x", hfc_fifo_u8(chan,
				Z_inc(chan, *Z2_F2(chan), i)));
		}

		printk("\n");
	}
#endif

	if (frame_size <= 0) {
#ifdef DEBUG
		if (debug_level >= 2) {
			printk(KERN_DEBUG hfc_DRIVER_PREFIX
				"card %d: "
				"chan %s: "
				"invalid (empty) frame received.\n",
				chan->chan->card->cardnum,
				chan->chan->name);
		}
#endif

		hfc_fifo_drop_frame(chan);
		return -1;
	}

	/*
	 * STAT is not really received
	 */
	chan->bytes += frame_size - 1;

	/*
	 * Calculate beginning of the next frame
	 */
	newz2 = Z_inc(chan, *Z2_F2(chan), frame_size);

	/*
	 * We cannot use hfc_fifo_get because of different semantic of
	 * "available bytes" and to avoid useless increment of Z2
	 */
	hfc_fifo_mem_read(chan, *Z2_F2(chan), data,
		frame_size < max_size ? frame_size : max_size);

	if (hfc_fifo_u8(chan, Z_inc(chan, *Z2_F2(chan),
		frame_size - 1)) != 0x00) {
		/*
		 * CRC not ok, frame broken, skipping
		 */
#ifdef DEBUG
		if (debug_level >= 2) {
			printk(KERN_WARNING hfc_DRIVER_PREFIX
				"card %d: "
				"chan %s: "
				"Received frame with wrong CRC\n",
				chan->chan->card->cardnum,
				chan->chan->name);
		}
#endif

		chan->crc++;

		hfc_fifo_drop_frame(chan);
		return -1;
	}

	chan->frames++;

	*chan->f2 = F_inc(chan, *chan->f2, 1);

	/*
	 * Set Z2 for the next frame we're going to receive
	 */
	*Z2_F2(chan) = newz2;

	return frame_size;
}

void hfc_fifo_drop_frame(struct hfc_chan_simplex *chan)
{
	int available_bytes;
	u16 newz2;

	if (*chan->f1 == *chan->f2) {
		/*
		 * nothing received, strange eh?
		 */
		printk(KERN_WARNING hfc_DRIVER_PREFIX
			"card %d: "
			"chan %s: "
			"skip_frame called with no frame in FIFO.\n",
			chan->chan->card->cardnum,
			chan->chan->name);

		return;
	}

	available_bytes = hfc_fifo_used_rx(chan) + 1;

	/*
	 * Calculate beginning of the next frame
	 */
	newz2 = Z_inc(chan, *Z2_F2(chan), available_bytes);

	*chan->f2 = F_inc(chan, *chan->f2, 1);

	/*
	 * Set Z2 for the next frame we're going to receive
	 */
	*Z2_F2(chan) = newz2;
}

void hfc_fifo_put_frame(struct hfc_chan_simplex *chan,
		 void *data, int size)
{
	u16 newz1;
	int available_frames;

#ifdef DEBUG
	if (debug_level == 3) {
		printk(KERN_DEBUG hfc_DRIVER_PREFIX
			"card %d: "
			"chan %s: "
			"TX len %2d: ",
			chan->chan->card->cardnum,
			chan->chan->name,
			size);
	} else if (debug_level >= 4) {
		printk(KERN_DEBUG hfc_DRIVER_PREFIX
			"card %d: "
			"chan %s: "
			"TX (f1=%02x, f2=%02x, z1=%04x, z2=%04x) len %2d: ",
			chan->chan->card->cardnum,
			chan->chan->name,
			*chan->f1, *chan->f2, *Z1_F1(chan), *Z2_F1(chan),
			size);
	}

	if (debug_level >= 3) {
		int i;
		for (i = 0; i < size; i++)
			printk("%02x", ((u8 *)data)[i]);

		printk("\n");
	}
#endif

	available_frames = hfc_fifo_free_frames(chan);

	if (available_frames >= chan->f_num) {
		printk(KERN_CRIT hfc_DRIVER_PREFIX
			"card %d: "
			"chan %s: "
			"TX FIFO total number of frames exceeded!\n",
			chan->chan->card->cardnum,
			chan->chan->name);

		chan->fifo_full++;

		return;
	}

	hfc_fifo_put(chan, data, size);

	newz1 = *Z1_F1(chan);

	*chan->f1 = F_inc(chan, *chan->f1, 1);

	*Z1_F1(chan) = newz1;

	chan->frames++;
}

void hfc_clear_fifo_rx(struct hfc_chan_simplex *chan)
{
	*chan->f2 = *chan->f1;
	*Z2_F2(chan) = *Z1_F2(chan);
}

void hfc_clear_fifo_tx(struct hfc_chan_simplex *chan)
{
	*chan->f1 = *chan->f2;
	*Z1_F1(chan) = *Z2_F1(chan);

	if (chan->chan->status == open_voice) {
		/*
		 * Make sure that at least hfc_TX_FIFO_PRELOAD bytes are
		 * present in the TX FIFOs
		 * Create hfc_TX_FIFO_PRELOAD bytes of empty data
		 * (0x7f is mute audio)
		 */
		u8 empty_fifo[hfc_TX_FIFO_PRELOAD +
			DAHDI_CHUNKSIZE + hfc_RX_FIFO_PRELOAD];
		memset(empty_fifo, 0x7f, sizeof(empty_fifo));

		hfc_fifo_put(chan, empty_fifo, sizeof(empty_fifo));
	}
}

