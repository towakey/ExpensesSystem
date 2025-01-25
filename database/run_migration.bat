@echo off
sqlite3 expenses.db < migrations/alter_foreign_keys.sql
