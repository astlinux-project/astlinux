/*
 * Asterisk -- An open source telephony toolkit.
 *
 * Copyright (C) 2006, Jeremy McNamara
 *
 * Jeremy McNamara <jj@nufone.net>
 *
 * See http://www.asterisk.org for more information about
 * the Asterisk project. Please do not directly contact
 * any of the maintainers of this project for assistance;
 * the project provides a web site, mailing lists and IRC
 * channels for your use.
 *
 * This program is free software, distributed under the terms of
 * the GNU General Public License Version 2. See the LICENSE file
 * at the top of the source tree.
 */

/*! \file
 *
 * \brief Reload Application
 *
 * \author Jeremy McNamara <jj@nufone.net>
 * 
 * Allows one to reload asterisk modules from the dialplan 
 * \ingroup applications
 */

#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <string.h>

#include "asterisk.h"

ASTERISK_FILE_VERSION(__FILE__, "$Revision: 1.8 $")

#include "asterisk/file.h"
#include "asterisk/logger.h"
#include "asterisk/channel.h"
#include "asterisk/pbx.h"
#include "asterisk/module.h"
#include "asterisk/lock.h"
#include "asterisk/app.h"
#include "asterisk/options.h"

static char *app = "Reload";
static char *synopsis = "Reload Asterisk from dialplan.";
static char *descrip = "Allows one to reload Asterisk modules from the dialplan.\n";

STANDARD_LOCAL_USER;

LOCAL_USER_DECL;

static int reload_exec(struct ast_channel *chan, void *data)
{
	int res = 0;
	struct localuser *u;

	LOCAL_USER_ADD(u);

	if (chan->_state != AST_STATE_UP) {
		ast_answer(chan);
	}
    	if (ast_strlen_zero(data)) {
        	data = NULL;
	}
	res = ast_module_reload(data);
	ast_log(LOG_DEBUG, "ast_module_reload returned %d\n", res);

	LOCAL_USER_REMOVE(u);

	return res;
}

int unload_module(void)
{
	int res;
	res = ast_unregister_application(app);
	return res;	
}

int load_module(void)
{
	return ast_register_application(app, reload_exec, synopsis, descrip);
}

char *description(void)
{
        return descrip;
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

