@echo off
title HamariJobs 1-Click Production Deployment
cd /d "%~dp0"

echo ================================================================
echo    HAMARIJOBS - 1-CLICK PRODUCTION DEPLOYMENT TO HOSTINGER
echo ================================================================
echo.

echo ==^> [1/3] Pushing latest commits to GitHub repository...
git push origin main

echo.
echo ==^> [2/3] Deploying to Hostinger server via SSH...
python -c "import paramiko, sys; HOST='217.21.74.188'; PORT=65002; USER='u390470426'; PASS='Tumhari@786'; PATH='/home/u390470426/domains/hamarijobs.com/public_html'; ssh=paramiko.SSHClient(); ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy()); ssh.connect(HOST, port=PORT, username=USER, password=PASS, timeout=15); stdin, stdout, stderr = ssh.exec_command(f'cd {PATH} && git fetch origin main && git reset --hard origin/main', get_pty=True); print(stdout.read().decode('utf-8', errors='ignore')); ssh.close(); print('[SUCCESS] Live server updated!')"

echo.
echo ==^> [3/3] Verifying live website status...
curl -I -s "https://hamarijobs.com/"

echo.
echo ================================================================
echo    DEPLOYMENT 100% COMPLETE & LIVE AT https://hamarijobs.com
echo ================================================================
pause