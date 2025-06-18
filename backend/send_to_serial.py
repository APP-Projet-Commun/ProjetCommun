#!/usr/bin/env python3
import serial
import sys
import time

# --- Configuration ---
SERIAL_PORT = 'COM6' 
BAUD_RATE = 9600
# ---------------------

def send_command_to_serial(command_string):
    """Ouvre le port série, envoie la commande formatée et referme."""
    try:
        # Assurer que la commande se termine par un retour à la ligne
        if not command_string.endswith('\n'):
            command_string += '\n'
            
        with serial.Serial(SERIAL_PORT, BAUD_RATE, timeout=2) as ser:
            time.sleep(2) # Laisse le temps à la connexion de s'établir
            ser.write(command_string.encode('utf-8'))
            
        print(f"Success: Sent '{command_string.strip()}' to {SERIAL_PORT}")
        
    except serial.SerialException as e:
        print(f"Error: Could not open or write to serial port {SERIAL_PORT}.")
        print(f"Details: {e}")
        sys.exit(1)

if __name__ == "__main__":
    if len(sys.argv) > 1:
        # On reçoit la commande complète de PHP, ex: "DATA:T:21.5,H:45.3,G:300"
        command_from_php = sys.argv[1]
        send_command_to_serial(command_from_php)
    else:
        print("Error: No command provided.")
        # Mise à jour des exemples d'usage
        print("Usage examples:")
        print("  python send_to_serial.py \"MANUAL:T:22.0,H:50\"")
        print("  python send_to_serial.py \"DATA:T:21.5,H:45.3,G:300,B:1\"")
        sys.exit(1)