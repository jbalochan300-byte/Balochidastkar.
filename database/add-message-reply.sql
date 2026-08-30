-- Balochi Dastkar — adds the ability to reply to contact messages
-- Run this in phpMyAdmin (SQL tab) on your existing balochi_dastar database.
-- Safe to run once. If you get "Duplicate column name", it means you
-- already ran this and there is nothing more to do.

ALTER TABLE contact_messages
    ADD COLUMN admin_reply TEXT NULL AFTER status,
    ADD COLUMN replied_at DATETIME NULL AFTER admin_reply;
