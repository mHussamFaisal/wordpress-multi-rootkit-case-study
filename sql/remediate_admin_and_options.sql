-- ============================================================================
-- WordPress Multi-Rootkit Database Remediation SQL Template
-- Replace `wp_` with your actual table prefix (e.g. `wp_`, `wp2a_`, etc.)
-- ============================================================================

-- 1. Restore Administrator Role & User Level 10 to Legitimate Admin Users (e.g. IDs 1, 4)
UPDATE `wp_usermeta`
SET `meta_value` = 'a:1:{s:13:"administrator";b:1;}'
WHERE `user_id` IN (1, 4)
  AND `meta_key` = 'wp_capabilities';

UPDATE `wp_usermeta`
SET `meta_value` = '10'
WHERE `user_id` IN (1, 4)
  AND `meta_key` = 'wp_user_level';

-- 2. Remove Rogue Attacker Account ('content_operator')
DELETE FROM `wp_usermeta`
WHERE `user_id` IN (SELECT `ID` FROM `wp_users` WHERE `user_login` = 'content_operator');

DELETE FROM `wp_users`
WHERE `user_login` = 'content_operator';

-- 3. Purge 335c4f46 Self-Healing Blobs, wPanel C2 Options & starter_* Spam Options
DELETE FROM `wp_options`
WHERE `option_name` LIKE '_wp_cg_335c4f46_%'
   OR `option_name` LIKE '%335c4f46%'
   OR `option_name` LIKE 'wpanel_%'
   OR `option_name` LIKE '_transient_wpanel_%'
   OR `option_name` LIKE 'starter_%'
   OR `option_name` LIKE '_transient_starter_%'
   OR `option_name` LIKE 'theme_mods_fvyejxc%'
   OR `option_name` LIKE 'theme_mods_nunolrm%';

-- 4. Delete Injected SEO Spam Posts & Associated Postmeta
DELETE FROM `wp_posts`
WHERE `ID` IN (
    SELECT `post_id` FROM `wp_postmeta` WHERE `meta_key` LIKE '_wp_cg_335c4f46_%'
);

DELETE FROM `wp_postmeta`
WHERE `meta_key` LIKE '_wp_cg_335c4f46_%';

