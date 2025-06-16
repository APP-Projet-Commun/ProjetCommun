#!/usr/bin/env python3
import serial
import sys
import time

# --- Configuration ---
SERIAL_PORT = 'COM6' 
BAUD_RATE = 9600
# ---------------------

def send_command(command):
    """Ouvre le port série, envoie la commande et referme."""
    try:
        if not command.endswith('\n'):
            command += '\n'
        ser = serial.Serial(SERIAL_PORT, BAUD_RATE, timeout=2)
        time.sleep(2) 
        ser.write(command.encode('utf-8'))
        ser.close()
        print(f"Success: Sent '{command.strip()}' to {SERIAL_PORT}")
    except serial.SerialException as e:
        print(f"Error: Could not open or write to serial port {SERIAL_PORT}.")
        print(f"Details: {e}")
        sys.exit(1)

if __name__ == "__main__":
    if len(sys.argv) > 1:
        command_from_php = sys.argv[1]
        send_command(command_from_php)
    else:
        print("Error: No command provided.")
        print("Usage: python3 send_to_serial.py \"T:val,H:val\"")
        sys.exit(1)