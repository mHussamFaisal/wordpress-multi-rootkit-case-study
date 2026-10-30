# Natural LinkedIn Post Drafts (Human / Developer Voice)

## Option 1: Raw "From the Trenches" Story (Best Engagement — Zero AI Vibes)

```text
Just finished a pretty crazy malware cleanup on a UK airport transfer website that takes 25+ bookings a day.

Because the site runs our custom booking system (/eto) and dispatch plugin alongside WordPress, wiping the server and starting fresh wasn't an option. We couldn't afford to lose bookings or take the booking form down for hours.

It started with a weird issue: the admin accounts could still log in, but the WordPress dashboard treated us like normal authors with no admin menu.

When I checked the database and files, it turned into a rabbit hole:

- A fake Wordfence plugin (wordfence-335c4f46) had a function that automatically demoted all real admins to "author" (user_level 2) in wp_usermeta and created its own hidden admin user called "content_operator".
- wp-config.php had bloated to 4.01 MB because the malware injected a huge base64 backup of itself inside the file.
- Every time I deleted the malware folders in cPanel File Manager, they came right back 5 minutes later. Even after I renamed index.php to indexdisable.php, the files still regenerated at 10:22 PM!

Why? Because the malware had set up 3 cron jobs in cPanel running every 5 minutes from two 8.7 MB PHP scripts hidden outside public_html (one in a hidden folder in the home directory and another in /tmp).

To make it worse, it locked all its folders to 0555 permissions and files to 0444. In cPanel File Manager, you can't delete files inside a 0555 folder. Even the folders we deleted earlier got stuck inside cPanel's .trash folder ("Directory not empty") and ate up 100% of the hosting disk quota, which then caused Elementor and Wordfence to write 0-byte CSS and config files!

Since phpMyAdmin also blocked the 598 MB database upload, we wrote a custom server-side PHP cleanup script and ran it directly via URL to:
1. Kill the Linux cron jobs and delete the hidden scripts in /tmp and the home directory
2. Recursively unlock all 0555/0444 permissions (including emptying the stuck cPanel .trash folder)
3. Strip the injected code out of wp-config.php and 12 active plugins without touching our custom /eto booking system
4. Restore our admin roles in MySQL and shrink the 598 MB database down to 12 MB by clearing out 360 MB of old revisions and malware blobs

Site is back up, images and Elementor styles are restored, and airport transfer bookings are coming through normally again.

I documented the whole breakdown and the sanitized PHP cleanup script on GitHub if anyone ever runs into this 335c4f46 or wPanel rootkit:
https://github.com/mHussamFaisal/wordpress-multi-rootkit-case-study

#wordpress #php #webdevelopment #cybersecurity #cpanel #debugging
```

---

## Option 2: Short & Direct Version

```text
Had a wild debugging session tonight on a live airport transfer site (25+ bookings/day).

The client's admin accounts were suddenly demoted to "author", wp-config.php was 4 MB in size, and deleting the malware folders in cPanel didn't work because they kept regenerating every 5 minutes even with index.php renamed.

Turned out to be two rootkits running at the same time (335c4f46 + wPanel v2.0.6):
- It hid two 8.7 MB PHP scripts outside public_html (in /tmp and a hidden home folder) triggered by cPanel cron jobs every 5 minutes.
- It locked its own directories to 0555 permissions so cPanel File Manager couldn't delete them or even empty the Trash folder.
- That filled up 100% of the hosting disk quota, which caused Elementor to generate 0-byte CSS files and broke the layout.
- Meanwhile we had to keep the custom /eto booking system and plugin untouched.

Ended up writing a standalone PHP remediation script on the server to kill the cron jobs, unlock all 0555 permissions, empty the stuck .trash folder, clean the 12 infected plugin files in-place, and restore admin access in the database.

Put the full write-up and sanitized cleanup script on GitHub in case it helps another dev dealing with this infection:
https://github.com/mHussamFaisal/wordpress-multi-rootkit-case-study

#wordpress #php #cybersecurity #webdev #debugging
```
