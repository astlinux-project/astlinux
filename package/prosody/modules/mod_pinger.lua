local new_watchdog = require "util.watchdog".new;
local filters = require "util.filters";
local st = require "util.stanza";

local idle_timeout = module:get_option_number("c2s_idle_timeout", 300);
local ping_timeout = module:get_option_number("c2s_ping_timeout",  30);

function update_watchdog(data, session)
	session.idle_watchdog:reset();
	session.idle_pinged = nil;
	return data;
end

function check_session(watchdog)
	local session = watchdog.session;
	if not session.idle_pinged then
		session.idle_pinged = true;
		if session.smacks then
			if not session.awaiting_ack then
				session.send(st.stanza("r", { xmlns = session.smacks }))
			end
		else
			session.send(st.iq({ type = "get", from = module.host, id = "idle-check" })
					:tag("ping", { xmlns = "urn:xmpp:ping" }));
		end
		return ping_timeout; -- Call us again after ping_timeout
	else
		module:log("info", "Client %q silent for too long, closing...", session.full_jid);
		session:close("connection-timeout");
	end
end


function watch_session(session)
	if not session.idle_watchdog
	and not session.requests then -- Don't watch BOSH connections (BOSH already has timeouts)
		session.idle_watchdog = new_watchdog(idle_timeout, check_session);
		session.idle_watchdog.session = session;
		filters.add_filter(session, "bytes/in", update_watchdog);
	end
end

function unwatch_session(session)
	if session.idle_watchdog then
		session.idle_watchdog:cancel();
		session.idle_watchdog = nil;
		filters.remove_filter(session, "bytes/in", update_watchdog);
	end
end

module:hook("resource-bind", function (event) watch_session(event.session); end);
module:hook("resource-unbind", function (event) unwatch_session(event.session); end);

-- handle smacks sessions properly (not pinging in hibernated state)
module:hook("smacks-hibernation-start", function (event) unwatch_session(event.origin); end);
module:hook("smacks-hibernation-end", function (event) watch_session(event.resumed); end);
