<?php
/*
 * IRCPlanet Services for ircu
 * Copyright (c) 2025 Felix Alcantara.
 * All rights reserved.
 * * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:

 * 1. Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution.
 * 3. Neither the name of ircPlanet nor the names of its contributors may be
 * used to endorse or promote products derived from this software without
 * specific prior written permission.
 * * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

    // 30 Days expiration (configure as needed)
    $expire_time = time() - (30 * 86400);
    
    // 1. Find expired channels
    // Note: We check 'lastActivityTime' or 'update_date' depending on your schema preference.
    // Based on your schema, 'update_date' is a datetime, 'lastActivityTime' isn't a column in 'channels' table schema you showed earlier.
    // Let's assume we use 'update_date' or a custom field. If using standard schema:
    
    // Convert timestamp to DB format if needed, or use integer column if you added one.
    // Assuming 'update_date' is the tracker:
    $expire_date = date('Y-m-d H:i:s', $expire_time);
    
    // Select channels that are NOT permanent (no_purge = 0) and haven't been updated/used
    $res = db_query("SELECT channel_id, name FROM channels WHERE no_purge = 0 AND update_date < '$expire_date'");
    
    if ($res) {
        while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
            $chan_id = $row['channel_id'];
            $chan_name = $row['name'];
            
            // 2. Delete from DB (Manual cleanup for relations)
            db_query("DELETE FROM channel_access WHERE chan_id = ?", [$chan_id]);
            db_query("DELETE FROM channel_bans WHERE chan_id = ?", [$chan_id]);
            db_query("DELETE FROM channels WHERE channel_id = ?", [$chan_id]);
            
            // 3. Remove from Memory
            $this->removeChannelReg($chan_name);
            
            // 4. Log it
            debug("Expired channel $chan_name (ID: $chan_id) due to inactivity.");
            
            // Optional: Leave the channel if the bot is in it
            $bot = $this->default_bot;
            if ($bot->isOn($chan_name)) {
                $bot->part($chan_name, "Channel expired due to inactivity.");
            }
        }
    }
?>
