/*
 * Asterisk -- A telephony toolkit for Linux.
 *
 * Playback a file with audio detect
 * 
 * Copyright (C) 2004-2005, Newman Telecom, Inc. and Newman Ventures, Inc.
 *
 * Justin Newman <jnewman@newmantelecom.com>
 *
 * We would like to thank Newman Ventures, Inc. for funding this
 * Asterisk project.
 *
 * Newman Ventures <info@newmanventures.com>
 *
 * Portions Copyright:
 * Copyright (C) 2001, Linux Support Services, Inc.
 * Copyright (C) 2004, Digium, Inc.
 *
 * Matthew Fredrickson <creslin@linux-support.net>
 * Mark Spencer <markster@digium.com>
 *
 * This program is free software, distributed under the terms of
 * the GNU General Public License
 */

#include "asterisk.h"

#include <stdio.h>
#include <asterisk/lock.h>
#include <asterisk/file.h>
#include <asterisk/logger.h>
#include <asterisk/channel.h>
#include <asterisk/pbx.h>
#include <asterisk/module.h>
#include <asterisk/translate.h>
#include <asterisk/utils.h>
#include <asterisk/dsp.h>

#ifndef DSP_FEATURE_DIGIT_DETECT
// To permit compiling on both Asterisk 1.4 and 1.6
#define NV_ASTERISK_1_4 TRUE
#define DSP_FEATURE_DIGIT_DETECT DSP_FEATURE_DTMF_DETECT 
#endif

static char *tdesc = "Newman's playback with talk and fax detection";

static char *app = "NVBackgroundDetect";

static char *synopsis = "Background a file with talk and fax detect (IAX and SIP too)";

static char *descrip = 
"  NVBackgroundDetect(filename[|options[|sildur[|mindur|[maxdur]]]]):\n"
"Plays filename, waiting for interruption from fax tones (on IAX and SIP too),\n"
"a digit, or non-silence. Audio is monitored in the receive direction. If\n"
"digits interrupt, they must be the start of a valid extension unless the\n"
"option is included to ignore. If fax is detected, it will jump to the\n"
"'fax' extension. If a period of non-silence is greater than 'mindur' ms,\n"
"yet less than 'maxdur' ms is followed by silence at least 'sildur' ms\n"
"then the app is aborted and processing jumps to the 'talk' extension.\n"
"If all undetected, control will continue at the next priority.\n"
"      options:\n"
"        'n':  Attempt on-hook if unanswered (default=no)\n"
"        'x':  DTMF digits terminate without extension (default=no)\n"
"        'd':  Ignore DTMF digit detection (default=no)\n"
"        'f':  Ignore fax detection (default=no)\n"
"        't':  Ignore talk detection (default=no)\n"
"      sildur:  Silence ms after mindur/maxdur before aborting (default=1000)\n"
"      mindur:  Minimum non-silence ms needed (default=100)\n"
"      maxdur:  Maximum non-silence ms allowed (default=0/forever)\n"
"Returns -1 on hangup, and 0 on successful completion with no exit conditions.\n\n"
"For questions or comments, please e-mail support@newmantelecom.com.\n";

// Use the second one for recent Asterisk releases
#define CALLERID_FIELD cid.cid_num
//#define CALLERID_FIELD callerid

//STANDARD_LOCAL_USER;

//LOCAL_USER_DECL;

static int nv_background_detect_exec(struct ast_channel *chan, void *data)
{
	int res = 0;
//	struct localuser *u;
	char tmp[256] = "\0";
	char *p = NULL;
	char *filename = NULL;
	char *options = NULL;
	char *silstr = NULL;
	char *minstr = NULL;
	char *maxstr = NULL;
	struct ast_frame *fr = NULL;
	struct ast_frame *fr2 = NULL;
	int notsilent = 0;
	struct timeval start = {0, 0}, end = {0, 0};
	int sildur = 1000;
	int mindur = 100;
	int maxdur = -1;
	int skipanswer = 0;
	int noextneeded = 0;
	int ignoredtmf = 0;
	int ignorefax = 0;
	int ignoretalk = 0;
	int x = 0;
	int origrformat = 0;
	int features = 0;
	struct ast_dsp *dsp = NULL;
	
	pbx_builtin_setvar_helper(chan, "FAX_DETECTED", "");
	pbx_builtin_setvar_helper(chan, "FAXEXTEN", "");
	pbx_builtin_setvar_helper(chan, "DTMF_DETECTED", "");
	pbx_builtin_setvar_helper(chan, "TALK_DETECTED", "");
	
	if (!data || ast_strlen_zero((char *)data)) {
		ast_log(LOG_WARNING, "NVBackgroundDetect requires an argument (filename)\n");
		return -1;
	}	
	
	strncpy(tmp, (char *)data, sizeof(tmp)-1);
	p = tmp;
	
	filename = strsep(&p, "|");
	options = strsep(&p, "|");
	silstr = strsep(&p, "|");
	minstr = strsep(&p, "|");	
	maxstr = strsep(&p, "|");	
	
	if (options) {
		if (strchr(options, 'n'))
			skipanswer = 1;
		if (strchr(options, 'x'))
			noextneeded = 1;
		if (strchr(options, 'd'))
			ignoredtmf = 1;
		if (strchr(options, 'f'))
			ignorefax = 1;
		if (strchr(options, 't'))
			ignoretalk = 1;
	}
	
	if (silstr) {
		if ((sscanf(silstr, "%d", &x) == 1) && (x > 0))
			sildur = x;
	}
	
	if (minstr) {
		if ((sscanf(minstr, "%d", &x) == 1) && (x > 0))
			mindur = x;
	}
	
	if (maxstr) {
		if ((sscanf(maxstr, "%d", &x) == 1) && (x > 0))
			maxdur = x;
	}
	
	ast_log(LOG_DEBUG, "Preparing detect of '%s' (sildur=%dms, mindur=%dms, maxdur=%dms)\n", 
						tmp, sildur, mindur, maxdur);
						
	//LOCAL_USER_ADD(u);
	if (chan->_state != AST_STATE_UP && !skipanswer) {
		/* Otherwise answer unless we're supposed to send this while on-hook */
		res = ast_answer(chan);
	}
	if (!res) {
		origrformat = chan->readformat;
		if ((res = ast_set_read_format(chan, AST_FORMAT_SLINEAR))) 
			ast_log(LOG_WARNING, "Unable to set read format to linear!\n");
	}
	if (!(dsp = ast_dsp_new())) {
		ast_log(LOG_WARNING, "Unable to allocate DSP!\n");
		res = -1;
	}

	if (dsp) {	
		if (!ignoretalk)
			; /* features |= DSP_FEATURE_SILENCE_SUPPRESS; */
		if (!ignorefax)
			features |= DSP_FEATURE_FAX_DETECT;
		//if (!ignoredtmf)
			features |= DSP_FEATURE_DIGIT_DETECT;
			
		ast_dsp_set_threshold(dsp, 256);
		ast_dsp_set_features(dsp, features | DSP_DIGITMODE_RELAXDTMF);
#ifdef NV_ASTERISK_1_4
		ast_dsp_digitmode(dsp, DSP_DIGITMODE_DTMF); /* asterisk 1.4 */
#else
		ast_dsp_set_digitmode(dsp, DSP_DIGITMODE_DTMF); /* asterisk 1.6 */
#endif
	}
	
	if (!res) {
		ast_stopstream(chan);
		res = ast_streamfile(chan, tmp, chan->language);
		if (!res) {
			while(chan->stream) {
				res = ast_sched_wait(chan->sched);
				if ((res < 0) && !chan->timingfunc) {
					res = 0;
					break;
				}
				if (res < 0)
					res = 1000;
				res = ast_waitfor(chan, res);
				if (res < 0) {
					ast_log(LOG_WARNING, "Waitfor failed on %s\n", chan->name);
					break;
				} else if (res > 0) {
					fr = ast_read(chan);
					if (!fr) {
						ast_log(LOG_DEBUG, "Got hangup\n");
						res = -1;
						break;
					}
					
					fr2 = ast_dsp_process(chan, dsp, fr);
					if (!fr2) {
						ast_log(LOG_WARNING, "Bad DSP received (what happened??)\n");
						fr2 = fr;
					} 
					
					if (fr2->frametype == AST_FRAME_DTMF) {
						if (fr2->subclass == 'f' && !ignorefax) {
							/* Fax tone -- Handle and return NULL */
							ast_log(LOG_DEBUG, "Fax detected on %s\n", chan->name);
							if (strcmp(chan->exten, "fax")) {
								ast_log(LOG_NOTICE, "Redirecting %s to fax extension\n", chan->name);
								pbx_builtin_setvar_helper(chan, "FAX_DETECTED", "1");
								pbx_builtin_setvar_helper(chan,"FAXEXTEN",chan->exten);								
								if (ast_exists_extension(chan, chan->context, "fax", 1, chan->CALLERID_FIELD)) {
									/* Save the DID/DNIS when we transfer the fax call to a "fax" extension */
									strncpy(chan->exten, "fax", sizeof(chan->exten)-1);
									chan->priority = 0;									
								} else
									ast_log(LOG_WARNING, "Fax detected, but no fax extension\n");
							} else
								ast_log(LOG_WARNING, "Already in a fax extension, not redirecting\n");

							res = 0;
							ast_frfree(fr);
							break;
						} else if (!ignoredtmf) {
							char t[2];
							t[0] = fr2->subclass;
							t[1] = '\0';
							ast_log(LOG_DEBUG, "DTMF detected on %s\n", chan->name);
							if (noextneeded || ast_canmatch_extension(chan, chan->context, t, 1, chan->CALLERID_FIELD)) {
								pbx_builtin_setvar_helper(chan, "DTMF_DETECTED", "1");
								/* They entered a valid extension, or might be anyhow */
								if (noextneeded) {
									ast_log(LOG_NOTICE, "DTMF received (not matching to exten)\n");
									res = 0;
								} else {
									ast_log(LOG_NOTICE, "DTMF received (matching to exten)\n");
									res = fr2->subclass;
								}
								ast_frfree(fr);
								break;
							} else
								ast_log(LOG_DEBUG, "Valid extension requested and DTMF did not match\n");
						}
					} else if ((fr->frametype == AST_FRAME_VOICE) && (fr->subclass == AST_FORMAT_SLINEAR) && !ignoretalk) {
						int totalsilence;
						int ms;
						res = ast_dsp_silence(dsp, fr, &totalsilence);
						if (res && (totalsilence > sildur)) {
							/* We've been quiet a little while */
							if (notsilent) {
								/* We had heard some talking */
								gettimeofday(&end, NULL);
								ms = (end.tv_sec - start.tv_sec) * 1000;
								ms += (end.tv_usec - start.tv_usec) / 1000;
								ms -= sildur;
								if (ms < 0)
									ms = 0;
								if ((ms > mindur) && ((maxdur < 0) || (ms < maxdur))) {
									char ms_str[10];
									ast_log(LOG_DEBUG, "Found qualified token of %d ms\n", ms);
									ast_log(LOG_NOTICE, "Redirecting %s to talk extension\n", chan->name);

									/* Save detected talk time (in milliseconds) */ 
									sprintf(ms_str, "%d", ms);	
									pbx_builtin_setvar_helper(chan, "TALK_DETECTED", ms_str);
									
									if (ast_exists_extension(chan, chan->context, "talk", 1, chan->CALLERID_FIELD)) {
										strncpy(chan->exten, "talk", sizeof(chan->exten) - 1);
										chan->priority = 0;
									} else
										ast_log(LOG_WARNING, "Talk detected, but no talk extension\n");
									res = 0;
									ast_frfree(fr);
									break;
								} else
									ast_log(LOG_DEBUG, "Found unqualified token of %d ms\n", ms);
								notsilent = 0;
							}
						} else {
							if (!notsilent) {
								/* Heard some audio, mark the begining of the token */
								gettimeofday(&start, NULL);
								ast_log(LOG_DEBUG, "Start of voice token!\n");
								notsilent = 1;
							}
						}						
					}
					ast_frfree(fr);
				}
				ast_sched_runq(chan->sched);
			}
			ast_stopstream(chan);
		} else {
			ast_log(LOG_WARNING, "ast_streamfile failed on %s for %s\n", chan->name, (char *)data);
			res = 0;
		}
	} else
		ast_log(LOG_WARNING, "Could not answer channel '%s'\n", chan->name);
	
	if (res > -1) {
		if (origrformat && ast_set_read_format(chan, origrformat)) {
			ast_log(LOG_WARNING, "Failed to restore read format for %s to %s\n", 
				chan->name, ast_getformatname(origrformat));
		}
	}
	
	if (dsp)
		ast_dsp_free(dsp);
	
	//LOCAL_USER_REMOVE(u);
	
	return res;
}

static int unload_module(void)
{
	//STANDARD_HANGUP_LOCALUSERS;
	return ast_unregister_application(app);
}

static int load_module(void)
{
	return ast_register_application(app, nv_background_detect_exec, synopsis, descrip);
}

static char *description(void)
{
	return tdesc;
}

AST_MODULE_INFO_STANDARD(ASTERISK_GPL_KEY, "NV BG Detection Application");

