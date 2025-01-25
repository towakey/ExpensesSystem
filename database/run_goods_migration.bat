@echo off
cd %~dp0
sqlite3 expenses.db < migrations/alter_goods_foreign_key.sql
