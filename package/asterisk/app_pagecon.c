/*
 * Asterisk -- An open source telephony toolkit.
 *
 * Copyright (c) 2004 - 2006 Digium, Inc.  All rights reserved.
 *
 * Mark Spencer <markster@digium.com>
 * Nick D'Amato <ndamato@star2star.com>
 * Kristian Kielhofner <kris@krisk.org>
 *
 * This code is released under the GNU General Public License
 * version 2.0.  See LICENSE for more information.
 *
 * See http://www.asterisk.org for more information about
 * the Asterisk project. Please do not directly contact
 * any of the maintainers of this project for assistance;
 * the project provides a web site, mailing lists and IRC
 * channels for your use.
 *
 */

/*! \file
 *
 * \brief page() - Paging application
 *
 * \ingroup applications
 */

#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <string.h>
#include <errno.h>

#include "asterisk.h"

ASTERISK_FILE_VERSION(__FILE__, "$Revision: 19812 $")

#include "asterisk/options.h"
#include "asterisk/logger.h"
#include "asterisk/channel.h"
#include "asterisk/pbx.h"
#include "asterisk/module.h"
#include "asterisk/file.h"
#include "asterisk/app.h"
#include "asterisk/chanvars.h"


static const char *tdesc = "Page Multiple Phones";

static const char *app_pagecon = "PageCon";

static const char *pagecon_synopsis = "Pages phones";

static const char *pagecon_descrip =
"PageCon(Technology/Resource&Technology2/Resource2[|options])\n"
"  Places outbound calls to the given technology / resource and dumps\n"
"them into a conference bridge as muted participants.  The original\n"
"caller is dumped into the conference as a speaker and the room is\n"
"destroyed when the original caller leaves.  Valid options are:\n"
"        d - full duplex audio\n"
"        q - quiet, do not play beep to caller\n"
"        e - exclude channels in use\n";

STANDARD_LOCAL_USER;

LOCAL_USER_DECL;

enum {
	PAGE_DUPLEX = (1 << 0),
	PAGE_QUIET = (1 << 1),
	PAGE_EXCLUDE = (1 << 2),
} page_opt_flags;

AST_APP_OPTIONS(page_opts, {
	AST_APP_OPTION('d', PAGE_DUPLEX),
	AST_APP_OPTION('q', PAGE_QUIET),
	AST_APP_OPTION('e', PAGE_EXCLUDE)
});

struct calloutdata {
	char cidnum[64];
	char cidname[64];
	char tech[64];
	char resource[256];
	char conferenceopts[64];
	struct ast_variable *variables;
};

static void *page_thread(void *data)
{
	struct calloutdata *cd = data;
	ast_pbx_outgoing_app(cd->tech, AST_FORMAT_SLINEAR, cd->resource, 30000,
		"Conference", cd->conferenceopts, NULL, 0, cd->cidnum, cd->cidname, cd->variables, NULL, NULL);
	free(cd);
	return NULL;
}

static void launch_page(struct ast_channel *chan, const char *conferenceopts, const char *tech, const char *resource)
{
	struct calloutdata *cd;
	const char *varname;
	struct ast_variable *lastvar = NULL;
	struct ast_var_t *varptr;
	pthread_t t;
	pthread_attr_t attr;
	cd = malloc(sizeof(struct calloutdata));
	if (cd) {
		memset(cd, 0, sizeof(struct calloutdata));
		ast_copy_string(cd->cidnum, chan->cid.cid_num ? chan->cid.cid_num : "", sizeof(cd->cidnum));
		ast_copy_string(cd->cidname, chan->cid.cid_name ? chan->cid.cid_name : "", sizeof(cd->cidname));
		ast_copy_string(cd->tech, tech, sizeof(cd->tech));
		ast_copy_string(cd->resource, resource, sizeof(cd->resource));
		ast_copy_string(cd->conferenceopts, conferenceopts, sizeof(cd->conferenceopts));

		AST_LIST_TRAVERSE(&chan->varshead, varptr, entries) {
			if (!(varname = ast_var_full_name(varptr)))
				continue;
			if (varname[0] == '_') {
				struct ast_variable *newvar = NULL;

				if (varname[1] == '_') {
					newvar = ast_variable_new(varname, ast_var_value(varptr));
				} else {
					newvar = ast_variable_new(&varname[1], ast_var_value(varptr));
				}

				if (newvar) {
					if (lastvar)
						lastvar->next = newvar;
					else
						cd->variables = newvar;
					lastvar = newvar;
				}
			}
		}

		pthread_attr_init(&attr);
		pthread_attr_setdetachstate(&attr, PTHREAD_CREATE_DETACHED);
		if (ast_pthread_create(&t, &attr, page_thread, cd)) {
			ast_log(LOG_WARNING, "Unable to create paging thread: %s\n", strerror(errno));
			free(cd);
		}
	}
}

static int pagecon_exec(struct ast_channel *chan, void *data)
{
	struct localuser *u;
	char *options;
	char *tech, *resource;
	char conferenceopts[80];
	struct ast_flags flags = { 0 };
	unsigned int confid = rand();
	struct ast_app *app;
	char *tmp;
	int res=0;
	char originator[AST_CHANNEL_NAME];
	char exclude_list[1024] = "";
	struct ast_channel *c = NULL, *bc = NULL;
        char *cnameT;
        int numchans = 0;


	if (ast_strlen_zero(data)) {
		ast_log(LOG_WARNING, "This application requires at least one argument (destination(s) to page)\n");
		return -1;
	}

	LOCAL_USER_ADD(u);

	if (!(app = pbx_findapp("Conference"))) {
		ast_log(LOG_WARNING, "There is no Conference application available!\n");
		LOCAL_USER_REMOVE(u);
		return -1;
	};

	options = ast_strdupa(data);
	if (!options) {
		ast_log(LOG_ERROR, "Out of memory\n");
		LOCAL_USER_REMOVE(u);
		return -1;
	}

	ast_copy_string(originator, chan->name, sizeof(originator));
	if ((tmp = strchr(originator, '-')))
		*tmp = '\0';

	tmp = strsep(&options, "|");
	if (options)
		ast_app_parse_options(page_opts, &flags, NULL, options);

	snprintf(conferenceopts, sizeof(conferenceopts), "%u|%sq", confid, ast_test_flag(&flags, PAGE_DUPLEX) ? "" : "L");
       
	if (ast_test_flag(&flags, PAGE_EXCLUDE)) { 
        	while ((c = ast_channel_walk_locked(c)) != NULL) {
                	bc = ast_bridged_channel(c);
			cnameT = c->name;
			while((*cnameT != '-') && (*cnameT != '\0')) cnameT++;
			*cnameT = '\0';

			strcat(exclude_list, c->name);
			strcat(exclude_list, "&");

			numchans++;
                	ast_mutex_unlock(&c->lock);
        	}
	
		strcat(exclude_list, originator);
	}
	else
	{
	strcpy(exclude_list, originator);
	}

	while ((tech = strsep(&tmp, "&"))) {
		/* don't call the originating device */
		if (!strcasecmp(tech, exclude_list))
			continue;

		if ((resource = strchr(tech, '/'))) {
			*resource++ = '\0';
			if (!strstr(exclude_list, resource)) {
				launch_page(chan, conferenceopts, tech, resource);
			}
		} else {
			ast_log(LOG_WARNING, "Incomplete destination '%s' supplied.\n", tech);
		}
	}

	if (!ast_test_flag(&flags, PAGE_QUIET)) {
		res = ast_streamfile(chan, "beep", chan->language);
		if (!res)
			res = ast_waitstream(chan, "");
	}
	if (!res) {
		snprintf(conferenceopts, sizeof(conferenceopts), "%u|%sq", confid, ast_test_flag(&flags, PAGE_DUPLEX) ? "" : "M");
		pbx_exec(chan, app, conferenceopts, 1);
	}

	LOCAL_USER_REMOVE(u);

	return -1;
}

int unload_module(void)
{
	int res;

	res =  ast_unregister_application(app_pagecon);

	STANDARD_HANGUP_LOCALUSERS;

	return res;
}

int load_module(void)
{
	return ast_register_application(app_pagecon, pagecon_exec, pagecon_synopsis, pagecon_descrip);
}

char *description(void)
{
	return (char *) tdesc;
}

int usecount(void)
{
	int res;

	STANDARD_USECOUNT(res);

	return res;
}

char *key()
{
	return ASTERISK_GPL_KEY;
}
