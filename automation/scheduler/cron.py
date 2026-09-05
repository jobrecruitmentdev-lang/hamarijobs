import os
import sys
import json
import time
import signal
import datetime
import subprocess
from pathlib import Path

ROOT_DIR = str(Path(__file__).resolve().parent.parent.parent)
if ROOT_DIR not in sys.path:
    sys.path.insert(0, ROOT_DIR)

from automation.logger import logger

STATE_FILE = os.path.join(ROOT_DIR, "storage", "daemon_state.json")
DEFAULT_INTERVAL_SECONDS = int(os.getenv("HAMARIJOBS_CRON_INTERVAL_SECONDS", 4 * 3600))  # 4 Hours

def is_pid_alive(pid: int) -> bool:
    """Check if process with given PID is currently active on Windows/Unix."""
    if not pid or pid <= 0:
        return False
    try:
        if sys.platform == "win32":
            # tasklist check for Windows
            res = subprocess.run(
                ["tasklist", "/FI", f"PID eq {pid}", "/FO", "CSV", "/NH"],
                capture_output=True,
                text=True,
                timeout=5
            )
            return str(pid) in res.stdout
        else:
            os.kill(pid, 0)
            return True
    except Exception:
        return False

def read_state(verify_pid: bool = True) -> dict:
    if os.path.exists(STATE_FILE):
        try:
            with open(STATE_FILE, "r", encoding="utf-8") as f:
                state = json.load(f)
                # Verify if recorded PID is still alive
                if verify_pid and state.get("status") == "RUNNING":
                    if not is_pid_alive(state.get("pid", 0)):
                        state["status"] = "STOPPED"
                        state["note"] = "Process exited"
                return state
        except Exception as e:
            logger.warning(f"Failed to read daemon state: {e}")
    return {
        "status": "STOPPED",
        "pid": None,
        "started_at": None,
        "interval_hours": 4,
        "last_run_at": None,
        "next_run_at": None,
        "last_run_status": None
    }

def write_state(state: dict):
    os.makedirs(os.path.dirname(STATE_FILE), exist_ok=True)
    with open(STATE_FILE, "w", encoding="utf-8") as f:
        json.dump(state, f, indent=2)

def run_single_cycle(trigger_source: str = "SCHEDULED_DAEMON") -> bool:
    """Executes a full live ingestion pipeline cycle."""
    logger.info(f"⏰ [CronDaemon] Executing pipeline run (Trigger: {trigger_source})...")
    try:
        from automation.live_ingestion_pipeline import run_live_ingestion
        run_live_ingestion(trigger_source=trigger_source)
        logger.info("✅ [CronDaemon] Cycle completed successfully.")
        return True
    except Exception as e:
        logger.error(f"❌ [CronDaemon] Cycle encountered error: {e}", exc_info=True)
        return False

def daemon_loop():
    """Persistent 4-hour background loop."""
    pid = os.getpid()
    interval = DEFAULT_INTERVAL_SECONDS
    started_at = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")

    logger.info(f"🚀 [CronDaemon] Started Background Autonomous Daemon (PID: {pid}, Interval: {interval // 3600}h)")

    state = read_state()
    state["status"] = "RUNNING"
    state["pid"] = pid
    state["started_at"] = started_at
    state["interval_hours"] = interval // 3600
    write_state(state)

    def handle_exit(signum, frame):
        logger.info(f"🛑 [CronDaemon] Received shutdown signal ({signum}). Exiting cleanly...")
        cur_state = read_state()
        cur_state["status"] = "STOPPED"
        cur_state["pid"] = None
        write_state(cur_state)
        sys.exit(0)

    try:
        signal.signal(signal.SIGINT, handle_exit)
        signal.signal(signal.SIGTERM, handle_exit)
    except Exception:
        pass

    while True:
        cycle_start = time.time()
        last_run_str = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        next_run_dt = datetime.datetime.now() + datetime.timedelta(seconds=interval)
        next_run_str = next_run_dt.strftime("%Y-%m-%d %H:%M:%S")

        # Update next run in state
        state = read_state(verify_pid=False)
        state["last_run_at"] = last_run_str
        state["next_run_at"] = next_run_str
        state["status"] = "RUNNING"
        write_state(state)

        # Run pipeline
        success = run_single_cycle(trigger_source="SCHEDULED_DAEMON")

        state = read_state(verify_pid=False)
        state["last_run_status"] = "SUCCESS" if success else "FAILED"
        state["status"] = "RUNNING"
        write_state(state)

        # Sleep in small slices so we can detect stop requests or signals quickly
        elapsed = time.time() - cycle_start
        sleep_needed = max(0, interval - elapsed)
        logger.info(f"💤 [CronDaemon] Sleeping until next cycle at {next_run_str} ({int(sleep_needed)}s)...")

        slept = 0
        while slept < sleep_needed:
            time.sleep(2)
            slept += 2
            # Check if external stop requested in state file
            try:
                cur_state = read_state(verify_pid=False)
                if cur_state.get("status") == "STOP_REQUESTED":
                    logger.info("🛑 [CronDaemon] Stop request detected in state file. Terminating.")
                    cur_state["status"] = "STOPPED"
                    cur_state["pid"] = None
                    write_state(cur_state)
                    sys.exit(0)
            except Exception:
                pass

def start_daemon():
    state = read_state()
    if state.get("status") == "RUNNING" and is_pid_alive(state.get("pid", 0)):
        print(f"[ALREADY RUNNING] Daemon is already active with PID {state['pid']}.")
        return

    # Spawn detached background process on Windows/Unix with logged output
    script_path = os.path.abspath(__file__)
    creationflags = 0
    if sys.platform == "win32":
        creationflags = subprocess.CREATE_NO_WINDOW | getattr(subprocess, 'DETACHED_PROCESS', 0x00000008)

    daemon_log_path = os.path.join(ROOT_DIR, "storage", "daemon.log")
    os.makedirs(os.path.dirname(daemon_log_path), exist_ok=True)
    log_file = open(daemon_log_path, "a", encoding="utf-8")

    proc = subprocess.Popen(
        [sys.executable, script_path, "--daemon"],
        cwd=ROOT_DIR,
        creationflags=creationflags,
        stdin=subprocess.DEVNULL,
        stdout=log_file,
        stderr=log_file,
        close_fds=(sys.platform != "win32")
    )
    print(f"[STARTED] Autonomous Scheduler Daemon launched in background with PID: {proc.pid}")

def stop_daemon():
    state = read_state()
    pid = state.get("pid")
    if not pid or not is_pid_alive(pid):
        state["status"] = "STOPPED"
        state["pid"] = None
        write_state(state)
        print("[STOPPED] Daemon was not active.")
        return

    # Request stop via file first
    state["status"] = "STOPPED"
    state["pid"] = None
    write_state(state)

    # Force terminate if still running
    try:
        if sys.platform == "win32":
            subprocess.run(["taskkill", "/F", "/PID", str(pid)], capture_output=True)
        else:
            os.kill(pid, signal.SIGTERM)
        print(f"[TERMINATED] Daemon process #{pid} successfully stopped.")
    except Exception as e:
        print(f"[NOTICE] Process termination: {e}")

def main():
    arg = sys.argv[1] if len(sys.argv) > 1 else "--status"
    if arg == "--start":
        start_daemon()
    elif arg == "--daemon":
        daemon_loop()
    elif arg == "--stop":
        stop_daemon()
    elif arg == "--run-now":
        success = run_single_cycle(trigger_source="MANUAL_CLI")
        print(json.dumps({"success": success, "time": datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")}))
    elif arg == "--status":
        state = read_state()
        print(json.dumps(state, indent=2))
    else:
        print(f"Usage: {sys.argv[0]} [--start | --stop | --status | --run-now]")

if __name__ == "__main__":
    main()
