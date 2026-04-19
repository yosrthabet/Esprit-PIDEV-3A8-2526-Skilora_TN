@echo off
REM Start the Mercure hub for Skilora dev (legacy binary)
echo Starting Mercure hub on http://localhost:3000...
mercure.exe --addr ":3000" --jwt-key "skilora_mercure_dev_secret_2024" --allow-anonymous --cors-allowed-origins "http://127.0.0.1:8000,http://localhost:8000" --publish-allowed-origins "*" --debug
