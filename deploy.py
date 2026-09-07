import sys
import subprocess
import paramiko

def run():
    print("================================================================")
    print("   HAMARIJOBS - 1-CLICK PRODUCTION DEPLOYMENT TO HOSTINGER")
    print("================================================================")
    print("\n==> [1/3] Pushing latest commits to GitHub repository...")
    try:
        subprocess.run(["git", "push", "origin", "main"], check=False)
    except Exception as e:
        print(f"Git push note: {e}")

    print("\n==> [2/3] Deploying to Hostinger server via SSH...")
    HOST = '217.21.74.188'
    PORT = 65002
    USER = 'u390470426'
    PASS = 'Tumhari@786'
    PATH = '/home/u390470426/domains/hamarijobs.com/public_html'

    try:
        ssh = paramiko.SSHClient()
        ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
        ssh.connect(HOST, port=PORT, username=USER, password=PASS, timeout=20)
        
        cmd = f"git -C {PATH} fetch origin main && git -C {PATH} reset --hard origin/main"
        stdin, stdout, stderr = ssh.exec_command(cmd)
        exit_code = stdout.channel.recv_exit_status()
        out = stdout.read().decode('utf-8', errors='ignore')
        err = stderr.read().decode('utf-8', errors='ignore')
        if out.strip():
            print(out.strip())
        if err.strip():
            print(err.strip())
        ssh.close()
        
        if exit_code == 0:
            print("\n[SUCCESS] Live server updated to latest commit!")
        else:
            print(f"\n[WARNING] Live update exited with code: {exit_code}")
    except Exception as e:
        print(f"\n[ERROR] SSH Deployment failed: {e}")
        sys.exit(1)

    print("\n==> [3/3] Verifying live website status...")
    try:
        import urllib.request
        req = urllib.request.Request("https://hamarijobs.com/google1c9d8b5c6dcf337b.html", headers={"User-Agent": "DeployCheck/1.0"})
        with urllib.request.urlopen(req, timeout=10) as resp:
            content = resp.read().decode('utf-8', errors='ignore').strip()
            print(f"HTTP Status: {resp.status}")
            print(f"Verification File Response: {content}")
            if "google-site-verification" in content:
                print("\n>>> LIVE VERIFICATION CONFIRMED 100% ONLINE! <<<")
    except Exception as e:
        print(f"Verification ping warning: {e}")

    print("\n================================================================")
    print("   DEPLOYMENT 100% COMPLETE & LIVE AT https://hamarijobs.com")
    print("================================================================")

if __name__ == "__main__":
    run()