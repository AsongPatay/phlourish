@echo off
cd /d "%~dp0"
echo Starting PHLOURISH call signaling on ws://localhost:8080
echo Keep this window open while using Call.
echo.
node server.js
if errorlevel 1 (
  echo.
  echo Could not start. Install Node.js, then run: npm install
  pause
)
