# Ready-to-Post LinkedIn Case Study Draft

```text
What do you do when a live UK Airport Transfer platform processing 25+ bookings per day gets hit by a self-healing rootkit—and deleting the malware files only makes them regenerate 5 minutes later? 🚕✈️🛡️

Wiping the server wasn't an option: the platform runs a custom dispatch & booking engine alongside WordPress, and every single live airport transfer booking had to be preserved with zero data loss.

The initial symptoms were wild:
• Legitimate Admins could still log in, but their dashboard had no Admin menu (demoted to Author).
• cPanel File Manager refused to delete the rogue plugin folders ("Directory not empty").
• Even after disabling index.php, a 221 KB web shell and a 4.01 MB wp-config.php regenerated themselves every 5 minutes!

Here is what I uncovered under the hood—and how I eradicated it:

1️⃣ The Hidden Linux Cron Jobs Outside public_html:
By deobfuscating an 8.73 MB standalone payload, I discovered why disabling WordPress didn't stop the infection. The malware had installed 3 hidden Linux Crontab jobs running every 5 minutes (`*/5 * * * *`), executing two 8.73 MB standalone PHP daemons hidden OUTSIDE the webroot (`~/.4cf380a5/196bea15.php` in the cPanel home directory and `/tmp/400440af`).

2️⃣ Multi-Layered Mutual Resurrection:
• A 4.01 MB self-healing block injected directly into wp-config.php
• A 15.36 MB base64 filesystem backup blob hidden inside wp_options
• 9 hidden dot-folders (.thumbnails, .objects, .cache-dir, etc.) across wp-content/
• Automatic Admin Demotion: A routine (`_cg_rdd_335c4f46`) backed up real Admin roles into a hidden option, demoted real Admins to "Author" (user_level = 2) in wp_usermeta, and promoted a rogue account ("content_operator") to sole Administrator.

3️⃣ Why cPanel File Manager & Trash Were Locked (`0555` / `0444`):
The malware locked its files to 0444 (read-only) and parent folders to 0555 (stripping write permissions from the directory). Even moving folders to cPanel Trash locked the `~/.trash/` directory!

4️⃣ A Second Concurrent Rootkit (wPanel v2.0.6):
Simultaneously, a second rootkit dropped wp-content/db.php & advanced-cache.php, injected 35 KB into the theme's functions.php, hooked wp_authenticate to intercept passwords, and appended a 13-line loader to 12 active plugins.

🛠️ The Engineering Fix (Server-Side Remediation Script):
To restore the booking platform without downtime or data loss, I engineered a custom Server-Side Remediation Script in PHP that:
✅ Hunted down & wiped the malicious Linux crontab jobs and killed active background daemons
✅ Overwrote malware files with `<?php exit;` before unlinking so cron triggers became harmless no-ops
✅ Recursively unlocked all 0555/0444 directories (including emptying the locked cPanel `~/.trash/` folder to free 500+ MB of disk quota)
✅ Surgically stripped the injected hooks from wp-config.php and 12 active plugins while keeping the custom airport booking engine 100% intact
✅ Restored Admin capabilities in MySQL and pruned 586 MB of database bloat down to a 1.65 MB archive

Result: All 5 malware families eradicated, Admin access restored, and all 25+ daily airport transfer bookings flowing smoothly again! ✅

I’ve published the full sanitized forensic breakdown and remediation toolkit on GitHub:
🔗 https://github.com/mHussamFaisal/wordpress-multi-rootkit-case-study

#CyberSecurity #WordPressSecurity #IncidentResponse #PHP #FullStackDeveloper #MalwareAnalysis #DevOps #WebSecurity
```
