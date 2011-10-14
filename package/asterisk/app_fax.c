/*
 * Application to send or receive a TIFF FAX file
 * based on app_rxfax.c from: Copyright (C) 2003, Steve Underwood <steveu@coppice.org>
 * based on app_rxfax.c from www.callweaver.org, Massimo Cetra & more.
 * based on app_rxfax.c from Antonio Gallo
 * thanks to all people who contributed to this project, for credits check SourceForge project page
 * (C) 2009 by Antonio Gallo <agx@linux.it>
 */

/*** MODULEINFO
	<depend>spandsp</depend>
 ***/

#include "asterisk.h"

ASTERISK_FILE_VERSION(__FILE__, "$Revision:$")

#include <errno.h>

#define SPANDSP_EXPOSE_INTERNAL_STRUCTURES
#include <spandsp.h>
#include <spandsp/version.h>
#if SPANDSP_RELEASE_DATE < 20081212
#error YOU NEED SPANDSP 0.0.6 pre12 to compile this
#endif

// #include "../addon_version.h"
#define AGX_AST_ADDON_VERSION		"1.4.24.5"
#include "asterisk/version.h"
#include "asterisk/pbx.h"
#include "asterisk/file.h"
#include "asterisk/module.h"
#include "asterisk/manager.h"
#include "asterisk/options.h"
#include "asterisk/logger.h"
#include "asterisk/threadstorage.h"

#define fax_log(...) _fax_log(__VA_ARGS__)
#define _fax_log(msg, level, file, line, function, fmt, ...) \
	ast_log(level, file, line, function, fmt, ## __VA_ARGS__); \
	if (msg && msg->log) do { \
		fprintf(msg->log, "[%d] %s:%d/%s: " fmt, level, file, line, function, ## __VA_ARGS__); \
		fflush(msg->log); \
	} while(0)

#ifndef AST_MODULE
#define AST_MODULE "app_fax"
#endif

static char *rxfax_app = "RxFAX";
static char *txfax_app = "TxFAX";

static char *rxfax_synopsis = "Receive a FAX to a file";
static char *txfax_synopsis = "Send a FAX from a file";

#define FAX_PROTOCOL_DESC	""	\
	"     DISABLE_V17 to disable V.17 only\n" \
	"     FAX_FORCE_V17 to force V.17 only\n" \
	"     FAX_FORCE_V27 to force V.27 only\n" \
	"     FAX_FORCE_V29 to force V.29 only\n" \
	"     FAX_FORCE_V34 to force V.34 only\n" \
	"\n" 

#define FAX_RESULT_DESC		""	\
	"Sets REMOTESTATIONID to the sender CSID.\n"	\
	"     FAXPAGES to the number of pages received.\n"	\
	"     FAXBITRATE to the transmition rate.\n"	\
	"     FAXRESOLUTION to the resolution.\n"	\
	"     PHASEESTATUS to the phase E result status.\n"	\
	"     PHASEESTRING to the phase E result string.\n"	\
	"\n"


static char *rxfax_descrip = 
	"  RxFAX(filename[|debug][|log=logfile]): Receives a FAX from the channel into the\n"
	"given filename. If the file exists it will be overwritten. The file\n"
	"should be in TIFF/F format. Transfer log will be appended to logfile\n"
	"The \"ecm\" option enables ECM.\n"
	"\n"
	"Uses LOCALSTATIONID to identify itself to the remote end.\n"
	"     LOCALSUBADDRESS to specify a sub-address to the remote end.\n"
	"     LOCALHEADERINFO to generate a header line on each page.\n"
	FAX_PROTOCOL_DESC
	FAX_RESULT_DESC
	"Note that PHASEESTATUS=0 means that the fax was handled correctly. But that doesn't\n"
	"imply that any pages were sent. Actually you should also check FAXPAGES to be\n"
	"greater than zero.\n"
	"Returns -1 when the user hangs up.\n"
	"Returns 0 otherwise.\n";

static char *txfax_descrip = 
	"  TxFAX(filename[|verbose][|debug][|ecm][|log=logfile]):  Send a given TIFF file to the channel as a FAX.\n"
	"The \"ecm\" option enables ECM.  Transfer log will be appended to logfile\n"
	"\n"
	"Uses LOCALSTATIONID to identify itself to the remote end.\n"
	"     LOCALHEADERINFO to generate a header line on each page.\n"
	FAX_PROTOCOL_DESC
	FAX_RESULT_DESC
	"Returns -1 when the user hangs up, or if the file does not exist.\n"
	"Returns 0 otherwise.\n";

#define MAX_BLOCK_SIZE 240

typedef struct {
	struct ast_channel *chan;
	fax_state_t fax;
	volatile int sendfax;
	volatile int finished;
	FILE * log;
} t_session;


AST_THREADSTORAGE(cur_session, cur_session_init);

static void span_message(int level, const char *msg)
{
	t_session ** ppsession;
	t_session * psession = NULL;
	int ast_level;
	if (msg==NULL) return;
	if ((ppsession = ast_threadstorage_get(&cur_session, sizeof(*ppsession)))) psession = *ppsession;
	if ( (level == SPAN_LOG_ERROR) || (level == SPAN_LOG_PROTOCOL_ERROR) )
		ast_level = __LOG_ERROR;
	else if ( (level == SPAN_LOG_WARNING) || (level == SPAN_LOG_PROTOCOL_WARNING ) )
		ast_level = __LOG_WARNING;
	else if ( (level == SPAN_LOG_FLOW) || (level == SPAN_LOG_FLOW_2) || (level == SPAN_LOG_FLOW_3) ) {
		if (option_verbose>=255) {
			ast_verbose( VERBOSE_PREFIX_4 "%s", msg);
		}
		return;
	} else {
		if (option_verbose>=255) {
			ast_verbose( VERBOSE_PREFIX_4 VERBOSE_PREFIX_4 "%s", msg);
		}
		return;
	}
	fax_log(psession, ast_level, _A_, "%s", msg);
	ast_verbose( VERBOSE_PREFIX_3 "%s", msg);
}

/*- End of function --------------------------------------------------------*/

static int phase_b_handler(t30_state_t *s, void *user_data, int result)
{
	t_session *psession = (t_session *) user_data;
	char *appname = (psession->sendfax) ? "TXFAX" : "RXFAX";
	fax_log( psession, LOG_DEBUG, "[%s phase_b_handler] channel: %s\n", appname, psession->chan->name );
	return T30_ERR_OK;
}

/*- End of function --------------------------------------------------------*/

static void phase_e_handler(t30_state_t *s, void *user_data, int result)
{
	struct ast_channel *chan;
	const char *tx_ident;
	const char *rx_ident;
	char buf[128];
	t30_stats_t t;

	t_session *psession = (t_session *) user_data;
	chan = psession->chan;
	t30_get_transfer_statistics(s, &t);

	tx_ident = t30_get_tx_ident(s);
	if (tx_ident == NULL)
		tx_ident = "";
	rx_ident = t30_get_rx_ident(s);
	if (rx_ident == NULL)
		rx_ident = "";
	pbx_builtin_setvar_helper(chan, "REMOTESTATIONID", rx_ident);
	int tmp_pages = (psession->sendfax) ? t.pages_tx : t.pages_rx;
	snprintf(buf, sizeof(buf), "%d", tmp_pages);
	pbx_builtin_setvar_helper(chan, "FAXPAGES", buf);
	snprintf(buf, sizeof(buf), "%d", t.y_resolution);
	pbx_builtin_setvar_helper(chan, "FAXRESOLUTION", buf);
	snprintf(buf, sizeof(buf), "%d", t.bit_rate);
	pbx_builtin_setvar_helper(chan, "FAXBITRATE", buf);
	snprintf(buf, sizeof(buf), "%d", result);
	pbx_builtin_setvar_helper(chan, "PHASEESTATUS", buf);
	snprintf(buf, sizeof(buf), "%s", t30_completion_code_to_str(result));
	pbx_builtin_setvar_helper(chan, "PHASEESTRING", buf);

	// This is to tell asterisk later that the fax has finished (with or without error)
	char *direction = NULL;
	if (psession->sendfax) {
		psession->finished = TRUE; 
		direction = "FaxSent";
	} else {
		direction = "FaxReceived";
	}

	if (result == T30_ERR_OK)
	{
		int tmp_pages = (psession->sendfax) ? t.pages_tx : t.pages_rx;
		char *tmp_fname = (psession->sendfax) ? s->tx_file : s->rx_file;
		manager_event(EVENT_FLAG_CALL,
				direction, "Channel: %s\nExten: %s\nCallerID: %s\nRemoteStationID: %s\nLocalStationID: %s\nPagesTransferred: %i\nResolution: %i\nTransferRate: %i\nFileName: %s\n",
				chan->name,
				chan->exten,
				(chan->cid.cid_num)  ?  chan->cid.cid_num  :  "",
				rx_ident,
				tx_ident,
				tmp_pages,
				t.y_resolution,
				t.bit_rate,
				tmp_fname);
		fax_log(psession, LOG_NOTICE, "[%s OK] Remote: %s Local: %s Pages: %i Speed: %i \n", direction, rx_ident, tx_ident, tmp_pages, t.bit_rate );
		ast_verbose(VERBOSE_PREFIX_1 "[%s OK] Remote: %s Local: %s Pages: %i Speed: %i \n", direction, rx_ident, tx_ident, tmp_pages, t.bit_rate );
	}
	else
	{
		fax_log(psession, LOG_ERROR, "[%s ERROR] result (%d) %s.\n", direction, result, t30_completion_code_to_str(result));
		ast_verbose(VERBOSE_PREFIX_1 "[%s ERROR] result (%d) %s.\n", direction, result, t30_completion_code_to_str(result));
	}
}
/*- End of function --------------------------------------------------------*/

static int phase_d_handler(t30_state_t *s, void *user_data, int result)
{
	if (result)
	{
		t30_stats_t t;
		t_session *psession = (t_session *) user_data;
		t30_get_transfer_statistics(s, &t);
		char *direction = (psession->sendfax) ? "TXFAX" : "RXFAX";
		int tmp_pages = (psession->sendfax) ? t.pages_tx : t.pages_rx;
		t30_get_transfer_statistics(s, &t);
		fax_log(psession, LOG_NOTICE, "[%s NEW PAGE]: Channel: %s Pages: %i Speed: %i\n", direction, psession->chan->name, tmp_pages, t.bit_rate );
		fax_log(psession, LOG_NOTICE, "               Bad rows: %i - Longest bad row run: %i - Compression type: %s\n", t.bad_rows, t.longest_bad_row_run, t4_encoding_to_str(t.encoding));
		fax_log(psession, LOG_NOTICE, "               Image size bytes: %i - Image size: %i x %i - Image resolution: %i x %i\n",  t.image_size, t.width, t.length, t.x_resolution, t.y_resolution);
		ast_verbose(VERBOSE_PREFIX_3 "[%s NEW PAGE]: Channel: %s Pages: %i Speed: %i\n", direction, psession->chan->name, tmp_pages, t.bit_rate );
	}
	return T30_ERR_OK;
}
/*- End of function --------------------------------------------------------*/

static int fax_run(struct ast_channel *chan, void *data, int sendfax)
{
	int res = 0;
	char tiff_file[256];
	char template_file[256];
	int samples;
	char *s;
	char *t;
	char *v;
	const char *x;
	int option;
	int len;
	struct ast_frame *inf = NULL;
	struct ast_frame outf;
	int verbose;
	int ecm = FALSE;

	struct ast_module_user *u;

	int original_read_fmt;
	int original_write_fmt;
	int i;

	t_session session;
	t_session * psession;
	t_session ** ppsession;
	session.chan = chan;
	session.finished = FALSE;
	session.sendfax = sendfax;
	session.log = NULL;
	memset( &session.fax, 0, sizeof(fax_state_t));
	psession = &session;
	if ((ppsession = ast_threadstorage_get(&cur_session, sizeof(*ppsession)))) *ppsession = &session;

	// Indetify the app
	char *appname = (sendfax) ? "TXFAX" : "RXFAX";

	/* Basic initial checkings */

	if (chan == NULL) {
		ast_log(LOG_ERROR, "%s: channel is NULL. Giving up.\n", appname);
		return -1;
	}


	/* Resetting channel variables related to T38 */
	pbx_builtin_setvar_helper(chan, "REMOTESTATIONID", "");
	pbx_builtin_setvar_helper(chan, "FAXPAGES", "");
	pbx_builtin_setvar_helper(chan, "FAXRESOLUTION", "");
	pbx_builtin_setvar_helper(chan, "FAXBITRATE", "");
	pbx_builtin_setvar_helper(chan, "PHASEESTATUS", "");
	pbx_builtin_setvar_helper(chan, "PHASEESTRING", "");

	/* Parsig parameters */

	/* The next few lines of code parse out the filename and header from the input string */
	if (data == NULL)
	{
		/* No data implies no filename or anything is present */
		ast_log(LOG_ERROR, "%s: requires an argument (filename)\n", appname);
		return -1;
	}

	verbose = FALSE;
	tiff_file[0] = '\0';

	char tbuf[256];
	for (option = 0, v = s = data;  v;  option++, s++) {
		t = s;
		v = strchr(s, '|');
		s = (v)  ?  v  :  s + strlen(s);
		len = s - t;
		if (len > 255)
			len = 255;
		strncpy((char *) tbuf, t, len);
		tbuf[len] = '\0';
		if (option == 0) {
			/* The first option is always the file name */
			strncpy(tiff_file, t, len);
			tiff_file[len] = '\0';
			if (!sendfax) {
				/* Allow the use of %d in the file name for a wild card of sorts, to
				   create a new file with the specified name scheme */
				if ((x = strchr(tiff_file, '%'))  &&  x[1] == 'd') {
					strcpy(template_file, tiff_file);
					i = 0;
					do {
						snprintf(tiff_file, 256, template_file, 1);
						i++;
					} while (ast_fileexists(tiff_file, "", chan->language) != -1);
				}
			}
		} else if (strncmp("debug", t, len) == 0) {
			verbose = TRUE;
		} else if (strncmp("verbose", t, len) == 0) {
			verbose = TRUE;
		} else if (strncmp("ecm", t, len) == 0) {
			ecm = TRUE;
		} else if (strncmp("log=", t, 4) == 0) {
			session.log = fopen(tbuf+4, "a+");
			if (!session.log)
				ast_log(LOG_ERROR, "%s: Can't open log %s: %s\n", appname, tbuf+4, strerror(errno));
		}
	}
	/* Done parsing */

	u = ast_module_user_add(chan);

	// Answer the channel
	if (chan->_state != AST_STATE_UP)
	{
		fax_log(psession, LOG_DEBUG, "%s: TODO: answering channel '%s'\n", appname, chan->name);
		//res = ast_answer(chan);
		ast_answer(chan);
	}

	/* Setting read and write formats */

	original_read_fmt = chan->readformat;
	if (original_read_fmt != AST_FORMAT_SLINEAR)
	{
		res = ast_set_read_format(chan, AST_FORMAT_SLINEAR);
		if (res < 0)
		{
			fax_log(psession, LOG_WARNING, "%s: Unable to set to linear read mode, giving up\n", appname);
			ast_module_user_remove(u);
			return -1;
		}
	}

	original_write_fmt = chan->writeformat;
	if (original_write_fmt != AST_FORMAT_SLINEAR)
	{
		res = ast_set_write_format(chan, AST_FORMAT_SLINEAR);
		if (res < 0)
		{
			fax_log(psession, LOG_ERROR, "%s: Unable to set to linear write mode, giving up\n", appname);
			res = ast_set_read_format(chan, original_read_fmt);
			if (res)
				fax_log(psession, LOG_WARNING, "%s: Unable to restore read format on '%s'\n", appname, chan->name);
			ast_module_user_remove(u);
			return -1;
		}
	}

	/* Remove any app level gain adjustments and disable echo cancel. */
	signed char sc;
	sc = 0;
	ast_channel_setoption(chan, AST_OPTION_RXGAIN, &sc, sizeof(sc), 0);
	ast_channel_setoption(chan, AST_OPTION_TXGAIN, &sc, sizeof(sc), 0);
	ast_channel_setoption(chan, AST_OPTION_ECHOCAN, &sc, sizeof(sc), 0);

	/* This is the main loop */

	uint8_t __buf[sizeof(uint16_t)*MAX_BLOCK_SIZE + 2*AST_FRIENDLY_OFFSET];
	uint8_t *buf = __buf + AST_FRIENDLY_OFFSET;


	if (fax_init(&session.fax, sendfax) == NULL)
	{
		fax_log(psession, LOG_ERROR, "%s: fax_init() Unable to start\n", appname);
		ast_module_user_remove(u);
		return -1;
	}
	fax_set_transmit_on_idle(&session.fax, TRUE);
	span_log_set_message_handler(&session.fax.logging, span_message);
	span_log_set_message_handler(&session.fax.t30.logging, span_message);
	if (verbose)
	{
		span_log_set_level(&session.fax.logging, SPAN_LOG_SHOW_SEVERITY | SPAN_LOG_SHOW_PROTOCOL | SPAN_LOG_FLOW);
		span_log_set_level(&session.fax.t30.logging, SPAN_LOG_SHOW_SEVERITY | SPAN_LOG_SHOW_PROTOCOL | SPAN_LOG_FLOW);
	} else {
		span_log_set_level(&session.fax.logging, SPAN_LOG_ERROR | SPAN_LOG_WARNING | SPAN_LOG_PROTOCOL_ERROR | SPAN_LOG_PROTOCOL_WARNING );
		span_log_set_level(&session.fax.t30.logging, SPAN_LOG_ERROR | SPAN_LOG_WARNING | SPAN_LOG_PROTOCOL_ERROR | SPAN_LOG_PROTOCOL_WARNING );
	}
	x = pbx_builtin_getvar_helper(chan, "LOCALSTATIONID");
	if (x  &&  x[0])
		t30_set_tx_ident(&session.fax.t30, x);
	x = pbx_builtin_getvar_helper(chan, "LOCALSUBADDRESS");
	if (x  &&  x[0])
		t30_set_tx_sub_address(&session.fax.t30, x);
	x = pbx_builtin_getvar_helper(chan, "LOCALHEADERINFO");
	if (x  &&  x[0])
		t30_set_tx_page_header_info(&session.fax.t30, x);
	t30_set_phase_b_handler(&session.fax.t30, phase_b_handler, &session);
	t30_set_phase_d_handler(&session.fax.t30, phase_d_handler, &session);
	t30_set_phase_e_handler(&session.fax.t30, phase_e_handler, &session);
	if (!sendfax) {
		t30_set_rx_file(&session.fax.t30, tiff_file, -1);
	} else {
		t30_set_tx_file(&session.fax.t30, tiff_file, -1, -1);
	}

	// Default Support ALL
	t30_set_supported_modems(&(session.fax.t30), T30_SUPPORT_V29 | T30_SUPPORT_V27TER | T30_SUPPORT_V17 );

	x = pbx_builtin_getvar_helper(chan, "FAX_DISABLE_V17");
	if (x  &&  x[0])
		t30_set_supported_modems(&(session.fax.t30), T30_SUPPORT_V29 | T30_SUPPORT_V27TER);
	x = pbx_builtin_getvar_helper(chan, "FAX_FORCE_V17");
	if (x  &&  x[0])
		t30_set_supported_modems(&(session.fax.t30), T30_SUPPORT_V17);
	x = pbx_builtin_getvar_helper(chan, "FAX_FORCE_V27");
	if (x  &&  x[0])
		t30_set_supported_modems(&(session.fax.t30), T30_SUPPORT_V27TER);
	x = pbx_builtin_getvar_helper(chan, "FAX_FORCE_V29");
	if (x  &&  x[0])
		t30_set_supported_modems(&(session.fax.t30), T30_SUPPORT_V29);
	x = pbx_builtin_getvar_helper(chan, "FAX_FORCE_V34");
	if (x  &&  x[0])
		t30_set_supported_modems(&(session.fax.t30), T30_SUPPORT_V34);

	/* Support for different image sizes && resolutions*/
	t30_set_supported_image_sizes(&session.fax.t30, T30_SUPPORT_US_LETTER_LENGTH | T30_SUPPORT_US_LEGAL_LENGTH | T30_SUPPORT_UNLIMITED_LENGTH
			| T30_SUPPORT_215MM_WIDTH | T30_SUPPORT_255MM_WIDTH | T30_SUPPORT_303MM_WIDTH);
	t30_set_supported_resolutions(&session.fax.t30, T30_SUPPORT_STANDARD_RESOLUTION | T30_SUPPORT_FINE_RESOLUTION | T30_SUPPORT_SUPERFINE_RESOLUTION
			| T30_SUPPORT_R8_RESOLUTION | T30_SUPPORT_R16_RESOLUTION);
	if (ecm) {
		t30_set_ecm_capability(&(session.fax.t30), TRUE);
		t30_set_supported_compressions(&(session.fax.t30), T30_SUPPORT_T4_1D_COMPRESSION | T30_SUPPORT_T4_2D_COMPRESSION | T30_SUPPORT_T6_COMPRESSION);
	} else {
		t30_set_ecm_capability(&(session.fax.t30), FALSE);
		t30_set_supported_compressions(&(session.fax.t30), T30_SUPPORT_T4_1D_COMPRESSION | T30_SUPPORT_T4_2D_COMPRESSION );
		fax_log(psession, LOG_DEBUG, "%s: ECM mode is not enabled\n", appname  );
	}


	/* This is the main loop */

	res = 0;

	/* temporary workwaround vars */
	int donotspam=10;
	int watchdog=256;

	while ( (!session.finished) && chan )
	{
		// new from 0.0.6
		if (!t30_call_active(&session.fax.t30)) {
			fax_log(psession, LOG_WARNING, "%s: t30_call_active is FALSE.\n", appname);
			res = 0;
			break;
		}

		if ((session.fax.t30.current_rx_type == T30_MODEM_DONE)  ||  (session.fax.t30.current_tx_type == T30_MODEM_DONE)) {
			/* Avoid spamming debug info */
			if (donotspam>0) {
				fax_log(psession, LOG_WARNING, "%s: Channel T30 DONE < 0.\n", appname);
				donotspam--;
			}
			/*
			 * Workaround: let 256 more packet to pass thru then definitively hangup
			 */
			if (watchdog>0) {
				watchdog--;
			} else {
				break;
			}
		}

		if (ast_check_hangup(chan)) {
			fax_log(psession, LOG_WARNING, "%s: Channel has been hanged at fax.\n", appname);
			res = 0;
			break;
		}
#define TESTING
#ifdef TESTING
		if ((res = ast_waitfor(chan, 100)) < 0) {
#else
		/* STABLE CODE */
		if ((res = ast_waitfor(chan, 20)) < 0) {
#endif
			fax_log(psession, LOG_WARNING, "%s: Channel ast_waitfor < 0.\n", appname);
			res = 0;
			break;
		}

		/* 
		 * in asterisk 1.4.24 ast_waitfor has been changed
		 * ast_read generate a warning in channel.c since now ast_waitfor returning 0
		 * means "TIMEOUT"
		 * so if the previous function return 0 we have to loop and try again
		 */
		if (res == 0) {
#undef EXPERIMENTAL
#ifdef EXPERIMENTAL
//			fax_log(psession, LOG_WARNING, "%s: ast_waitfor returned 0, i will continue...\n", appname);
			samples = 20;
			// Queue empty frame?
			len = samples;
			memset(&outf, 0, sizeof(outf));
			outf.frametype = AST_FRAME_VOICE;
			outf.subclass = AST_FORMAT_SLINEAR;
			outf.datalen = len*sizeof(int16_t);
			outf.samples = len;
			outf.data = &buf[AST_FRIENDLY_OFFSET];
			outf.offset = AST_FRIENDLY_OFFSET;
			outf.src = appname;
			memset(&buf[AST_FRIENDLY_OFFSET], 0, outf.datalen);
			if (ast_write(chan, &outf) < 0)
			{
				fax_log(psession, LOG_WARNING, "%s: Unable to write frame to channel; %s\n", appname, strerror(errno));
			}
#endif
			continue;
		}

		inf = ast_read(chan);
		if (inf == NULL)
		{
			fax_log(psession, LOG_WARNING, "%s: Channel INF is NULL, i will continue...\n", appname);
			// PROBABLY: While trasmiitting i got: Received a DCN from remote after sending a page at last page
			continue;
		}

		/* We got a frame */
		/* Check the frame type. Format also must be checked because there is a chance
		   that a frame in old format was already queued before we set chanel format
		   to slinear so it will still be received by ast_read */
		if (inf->frametype == AST_FRAME_VOICE && inf->subclass == AST_FORMAT_SLINEAR) {
			if (fax_rx(&session.fax, inf->data, inf->samples)) {
				fax_log(psession, LOG_WARNING, "%s: fax_rx returned error\n", appname);
				res = -1;
				break;
			}

			samples = (inf->samples <= MAX_BLOCK_SIZE) ? inf->samples : MAX_BLOCK_SIZE;
			len = fax_tx(&session.fax, (int16_t *) &buf[AST_FRIENDLY_OFFSET], samples);
			if (len>0) {
				memset(&outf, 0, sizeof(outf));
				outf.frametype = AST_FRAME_VOICE;
				outf.subclass = AST_FORMAT_SLINEAR;
				outf.datalen = len*sizeof(int16_t);
				outf.samples = len;
				outf.data = &buf[AST_FRIENDLY_OFFSET];
				outf.offset = AST_FRIENDLY_OFFSET;
				outf.src = appname;
				if (ast_write(chan, &outf) < 0)
				{
					fax_log(psession, LOG_WARNING, "%s: Unable to write frame to channel; %s\n", appname, strerror(errno));
					res = -1;
					break;
				}
			} 
			else 
			{
				// Queue empty frame?
				len = samples;
				memset(&outf, 0, sizeof(outf));
				outf.frametype = AST_FRAME_VOICE;
				outf.subclass = AST_FORMAT_SLINEAR;
				outf.datalen = len*sizeof(int16_t);
				outf.samples = len;
				outf.data = &buf[AST_FRIENDLY_OFFSET];
				outf.offset = AST_FRIENDLY_OFFSET;
				outf.src = appname;
				// clear data before to write
				memset(&buf[AST_FRIENDLY_OFFSET], 0, outf.datalen);
				if (ast_write(chan, &outf) < 0)
				{
					fax_log(psession, LOG_WARNING, "%s: Unable to write frame to channel; %s\n", appname, strerror(errno));
					res = -1;
					break;
				}
			}
			// end if: len>0
		}
		ast_frfree(inf);
		inf = NULL;
		/* TODO put a Watchdog here */
	}

	if (inf != NULL)
	{
		ast_frfree(inf);
		inf = NULL;
	}

	t30_terminate(&session.fax.t30);
	fax_release(&session.fax);
	if (sendfax) {
		if (session.finished) {
			fax_log(psession, LOG_WARNING, "TXFAX: Fax Transmission complete, check return code\n");
			res = 0;
		} else {
			fax_log(psession, LOG_WARNING, "TXFAX: Fax Transmission INCOMPLETE, check error code\n");
			res = -1;
		}
		if (res!=0) {
			fax_log(psession, LOG_WARNING, "TXFAX: Transmission RES error = %i\n", res);
		}
	}

	/* Restoring initial channel formats. */

	if (original_read_fmt && original_read_fmt != AST_FORMAT_SLINEAR)
	{
		res = ast_set_read_format(chan, original_read_fmt);
		if (res)
			fax_log(psession, LOG_WARNING, "%s: Unable to restore read format on '%s'\n", appname, chan->name);
	}
	if (original_write_fmt && original_write_fmt != AST_FORMAT_SLINEAR)
	{
		res = ast_set_write_format(chan, original_write_fmt);
		if (res)
			fax_log(psession, LOG_WARNING, "%s: Unable to restore write format on '%s'\n", appname, chan->name);
	}
	ast_module_user_remove(u);
	if(session.log) fclose(session.log);
	return res;
}

/*- End of function --------------------------------------------------------*/

static int rxfax_exec(struct ast_channel *chan, void *data) {
	return fax_run(chan,data,FALSE);
}

static int txfax_exec(struct ast_channel *chan, void *data) {
	return fax_run(chan,data,TRUE);
}

/*- End of function --------------------------------------------------------*/

static int unload_module(void)
{
	int res = 0;
	ast_module_user_hangup_all();
	res = ast_unregister_application(rxfax_app);	
	res |= ast_unregister_application(txfax_app);
	return res;
}
/*- End of function --------------------------------------------------------*/

static int load_module(void)
{
	ast_log(LOG_NOTICE, "app_fax %s using spandsp %s\n", AGX_AST_ADDON_VERSION, SPANDSP_RELEASE_DATETIME_STRING );
	if (ASTERISK_VERSION_NUM != 10424)
		ast_log(LOG_WARNING, "app_fax is untested with asterisk headers different from ASTERISK_VERSION_NUM = 10424\n");
	int res = 0;
	res = ast_register_application(rxfax_app, rxfax_exec, rxfax_synopsis, rxfax_descrip);
	res |= ast_register_application(txfax_app, txfax_exec, txfax_synopsis, txfax_descrip);
	return res;
}

/*- End of function --------------------------------------------------------*/

AST_MODULE_INFO_STANDARD(ASTERISK_GPL_KEY, "FAX Application based on SpanDSP");

/*- End of file ------------------------------------------------------------*/
