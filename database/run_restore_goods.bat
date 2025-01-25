@echo off
cd %~dp0
sqlite3 expenses.db < migrations/restore_goods.sql
