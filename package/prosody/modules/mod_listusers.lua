function module.command(args)
  local action = table.remove(args, 1);
  if not action then -- Default, list registered users
    local data_path = CFG_DATADIR or "data";
    if not pcall(require, "luarocks.loader") then
      pcall(require, "luarocks.require");
    end
    local lfs = require "lfs";
    function decode(s)
      return s:gsub("%%([a-fA-F0-9][a-fA-F0-9])", function (c)
        return string.char(tonumber("0x"..c));
      end);
    end
    for host in lfs.dir(data_path) do
      local accounts = data_path.."/"..host.."/accounts";
      if lfs.attributes(accounts, "mode") == "directory" then
        for user in lfs.dir(accounts) do
          if user:sub(1,1) ~= "." then
            print(decode(user:gsub("%.dat$", "")).."@"..decode(host));
          end
        end
      end
    end
  elseif action == "--connected" then -- List connected users
    local socket = require "socket";
    local default_local_interfaces = { };
    if socket.tcp6 and config.get("*", "use_ipv6") ~= false then
      table.insert(default_local_interfaces, "::1");
    end
    if config.get("*", "use_ipv4") ~= false then
      table.insert(default_local_interfaces, "127.0.0.1");
    end

    local console_interfaces = config.get("*", "console_interfaces")
      or config.get("*", "local_interfaces")
      or default_local_interfaces
    console_interfaces = type(console_interfaces)~="table"
      and {console_interfaces} or console_interfaces;

    local console_ports = config.get("*", "console_ports") or 5582
    console_ports = type(console_ports) ~= "table" and { console_ports } or console_ports;

    local st, conn = pcall(assert,socket.connect(console_interfaces[1], console_ports[1]));
    if (not st) then print("Error"..(conn and ": "..conn or "")); return 1; end

    local banner = config.get("*", "console_banner");
    if (
      (not banner) or
      (
        (type(banner) == "string") and
        (banner:match("^|    (.+)$"))
      )
    ) then
      repeat
        local rec_banner = conn:receive()
      until
        rec_banner == "" or
        rec_banner == nil; -- skip banner
    end

    conn:send("c2s:show()\n");
    conn:settimeout(1); -- Only hit in case of failure

    repeat local line = conn:receive()
      if not line then break; end
      local jid = line:match("^|    (.+)$");
      if jid then
        jid = jid:gsub(" %- (%w+%(%d+%))$", "\t%1");
        print(jid);
      elseif line:match("^| OK:") then
        return 0;
      end
    until false;
  end
  return 0;
end
